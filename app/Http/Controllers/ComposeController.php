<?php

namespace App\Http\Controllers;

use App\Collection;
use App\CollectionItem;
use App\Hashtag;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Jobs\VideoPipeline\VideoThumbnail;
use App\Media;
use App\MediaTag;
use App\Models\Poll;
use App\Notification;
use App\Profile;
use App\Services\AccountService;
use App\Services\CollectionService;
use App\Services\MediaBlocklistService;
use App\Services\MediaPathService;
use App\Services\MediaStorageService;
use App\Services\MediaTagService;
use App\Services\PlaceService;
use App\Services\SnowflakeService;
use App\Services\UserRoleService;
use App\Services\UserStorageService;
use App\Status;
use App\Transformer\Api\MediaTransformer;
use App\UserFilter;
use App\Util\Media\Filter;
use App\Util\Media\License;
use Auth;
use Cache;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class ComposeController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->middleware('auth');
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function show(Request $request)
    {
        return view('status.compose');
    }

    public function mediaUpload(Request $request)
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'file.*' => [
                'required_without:file',
                'mimetypes:'.config_cache('pixelfed.media_types'),
                'max:'.config_cache('pixelfed.max_photo_size'),
            ],
            'file' => [
                'required_without:file.*',
                'mimetypes:'.config_cache('pixelfed.media_types'),
                'max:'.config_cache('pixelfed.max_photo_size'),
            ],
            'filter_name' => 'nullable|string|max:24',
            'filter_class' => 'nullable|alpha_dash|max:24',
        ]);

        $user = $request->user();
        $profile = $user->profile;
        abort_if($user->has_roles && ! UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');

        $limitKey = 'compose:rate-limit:media-upload:'.$user->id;
        $limitTtl = now()->addMinutes(15);
        $limitReached = Cache::remember($limitKey, $limitTtl, function () use ($user) {
            $dailyLimit = Media::whereUserId($user->id)->where('created_at', '>', now()->subDays(1))->count();

            return $dailyLimit >= 1250;
        });

        abort_if($limitReached == true, 429);

        $filterClass = in_array($request->input('filter_class'), Filter::classes()) ? $request->input('filter_class') : null;
        $filterName = in_array($request->input('filter_name'), Filter::names()) ? $request->input('filter_name') : null;
        $accountSize = UserStorageService::get($user->id);
        abort_if($accountSize === -1, 403, 'Invalid request.');
        $photo = $request->file('file');
        $fileSize = $photo->getSize();
        $sizeInKbs = (int) ceil($fileSize / 1000);
        $updatedAccountSize = (int) $accountSize + (int) $sizeInKbs;

        if ((bool) config_cache('pixelfed.enforce_account_limit') == true) {
            $limit = (int) config_cache('pixelfed.max_account_size');
            if ($updatedAccountSize >= $limit) {
                abort(403, 'Account size limit reached.');
            }
        }

        $mimes = explode(',', config_cache('pixelfed.media_types'));

        abort_if(in_array($photo->getMimeType(), $mimes) == false, 400, 'Invalid media format');

        $storagePath = MediaPathService::get($user, 2);
        $path = $photo->storePublicly($storagePath);
        $hash = \hash_file('sha256', $photo);
        $mime = $photo->getMimeType();

        abort_if(MediaBlocklistService::exists($hash) == true, 451);

        $media = new Media;
        $media->status_id = null;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getSize();
        $media->caption = '';
        $media->mime = $mime;
        $media->filter_class = $filterClass;
        $media->filter_name = $filterName;
        $media->version = '3';
        $media->save();

        $preview_url = $media->url().'?v='.time();
        $url = $media->url().'?v='.time();

        switch ($media->mime) {
            case 'image/jpeg':
            case 'image/png':
            case 'image/webp':
            case 'image/heic':
            case 'image/avif':
                ImageOptimize::dispatch($media)->onQueue('mmo');
                break;

            case 'video/mp4':
                VideoThumbnail::dispatch($media)->onQueue('mmo');
                $preview_url = '/storage/no-preview.png';
                $url = '/storage/no-preview.png';
                break;

            default:
                break;
        }

        $user->storage_used = (int) $updatedAccountSize;
        $user->storage_used_updated_at = now();
        $user->save();

        Cache::forget($limitKey);
        $resource = new Fractal\Resource\Item($media, new MediaTransformer);
        $res = $this->fractal->createData($resource)->toArray();
        $res['preview_url'] = $preview_url;
        $res['url'] = $url;

        return response()->json($res);
    }

    public function mediaUpdate(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'file' => function () {
                return [
                    'required',
                    'mimetypes:'.config_cache('pixelfed.media_types'),
                    'max:'.config_cache('pixelfed.max_photo_size'),
                ];
            },
        ]);

        $user = Auth::user();
        abort_if($user->has_roles && ! UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');

        $limitKey = 'compose:rate-limit:media-updates:'.$user->id;
        $limitTtl = now()->addMinutes(15);
        $limitReached = Cache::remember($limitKey, $limitTtl, function () use ($user) {
            $dailyLimit = Media::whereUserId($user->id)->where('created_at', '>', now()->subDays(1))->count();

            return $dailyLimit >= 1500;
        });

        abort_if($limitReached == true, 429);

        $photo = $request->file('file');
        $id = $request->input('id');

        $media = Media::whereUserId($user->id)
            ->whereProfileId($user->profile_id)
            ->whereNull('status_id')
            ->findOrFail($id);

        $media->save();

        $fragments = explode('/', $media->media_path);
        $name = last($fragments);
        array_pop($fragments);
        $dir = implode('/', $fragments);
        $path = $photo->storePubliclyAs($dir, $name);
        $res = [
            'url' => $media->url().'?v='.time(),
        ];
        ImageOptimize::dispatch($media)->onQueue('mmo');
        Cache::forget($limitKey);
        UserStorageService::recalculateUpdateStorageUsed($request->user()->id);

        return $res;
    }

    public function mediaDelete(Request $request)
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'id' => 'required|integer|min:1|exists:media,id',
        ]);

        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');

        $media = Media::whereNull('status_id')
            ->whereUserId(Auth::id())
            ->findOrFail($request->input('id'));

        MediaStorageService::delete($media, true);

        UserStorageService::recalculateUpdateStorageUsed($request->user()->id);

        return response()->json([
            'msg' => 'Successfully deleted',
            'code' => 200,
        ]);
    }

    public function searchTag(Request $request)
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'q' => [
                'required',
                'string',
                'min:1',
                'max:300',
            ],
        ]);

        $q = $request->input('q');

        $cleanQuery = Str::of($q)->startsWith('@') ? Str::substr($q, 1) : $q;

        if (strlen($cleanQuery) < 2) {
            return [];
        }

        $user = $request->user();
        $currentUserId = $request->user()->profile_id;

        abort_if($user->has_roles && ! UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');

        $blocked = UserFilter::whereFilterableType('App\Profile')
            ->whereFilterType('block')
            ->whereFilterableId($request->user()->profile_id)
            ->pluck('user_id');

        $blocked->push($request->user()->profile_id);

        $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';
        $results = Profile::select([
            'profiles.id',
            'profiles.domain',
            'profiles.username',
            'profiles.followers_count',
        ])
            ->selectRaw('MAX(CASE WHEN followers.following_id IS NOT NULL THEN 1 ELSE 0 END) as is_followed')
            ->leftJoin('followers', function ($join) use ($currentUserId) {
                $join->on('followers.following_id', '=', 'profiles.id')
                    ->where('followers.profile_id', '=', $currentUserId);
            })
            ->whereNotIn('profiles.id', $blocked)
            ->where(function ($query) use ($cleanQuery, $operator) {
                $query->where('profiles.username', $operator, $cleanQuery.'%')
                    ->orWhere('profiles.username', $operator, '%'.$cleanQuery.'%');
            })
            ->groupBy('profiles.id', 'profiles.domain', 'profiles.username', 'profiles.followers_count')
            ->orderByDesc('is_followed')
            ->orderByDesc('profiles.followers_count')
            ->orderBy('profiles.username')
            ->limit(15)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => (string) $r->id,
                    'name' => $r->username,
                    'privacy' => true,
                    'avatar' => $r->avatarUrl(),
                ];
            });

        return $results;
    }

    public function searchUntag(Request $request)
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'status_id' => 'required',
            'profile_id' => 'required',
        ]);

        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');

        $user = $request->user();
        $status_id = $request->input('status_id');
        $profile_id = (int) $request->input('profile_id');

        abort_if((int) $user->profile_id !== $profile_id, 400);

        $tag = MediaTag::whereStatusId($status_id)
            ->whereProfileId($profile_id)
            ->first();

        if (! $tag) {
            return [];
        }
        Notification::whereItemType('App\MediaTag')
            ->whereItemId($tag->id)
            ->whereProfileId($profile_id)
            ->whereAction('tagged')
            ->delete();

        MediaTagService::untag($status_id, $profile_id);

        return [200];
    }

    public function searchLocation(Request $request)
    {
        abort_if(! $request->user(), 403);
        $this->validate($request, [
            'q' => 'required|string|max:100',
        ]);
        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');
        $pid = $request->user()->profile_id;
        abort_if(! $pid, 400);
        $q = e($request->input('q'));

        $popular = Cache::remember('pf:search:location:v1:popular', 1209600, function () {
            $minId = SnowflakeService::byDate(now()->subDays(290));
            if (config('database.default') == 'pgsql') {
                return Status::selectRaw('id, place_id, count(place_id) as pc')
                    ->whereNotNull('place_id')
                    ->where('id', '>', $minId)
                    ->orderByDesc('pc')
                    ->groupBy(['place_id', 'id'])
                    ->limit(400)
                    ->get()
                    ->filter(function ($post) {
                        return $post;
                    })
                    ->map(function ($place) {
                        return [
                            'id' => $place->place_id,
                            'count' => $place->pc,
                        ];
                    })
                    ->unique('id')
                    ->values();
            }

            return Status::selectRaw('id, place_id, count(place_id) as pc')
                ->whereNotNull('place_id')
                ->where('id', '>', $minId)
                ->groupBy('place_id')
                ->orderByDesc('pc')
                ->limit(400)
                ->get()
                ->filter(function ($post) {
                    return $post;
                })
                ->map(function ($place) {
                    return [
                        'id' => $place->place_id,
                        'count' => $place->pc,
                    ];
                });
        });
        $q = '%'.$q.'%';
        $wildcard = config('database.default') === 'pgsql' ? 'ilike' : 'like';

        $places = DB::table('places')
            ->where('name', $wildcard, $q)
            ->limit((strlen($q) > 5 ? 360 : 30))
            ->get()
            ->sortByDesc(function ($place, $key) use ($popular) {
                return $popular->filter(function ($p) use ($place) {
                    return $p['id'] == $place->id;
                })->map(function ($p) use ($place) {
                    return in_array($place->country, ['Canada', 'USA', 'France', 'Germany', 'United Kingdom']) ? $p['count'] : 1;
                })->values();
            })
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'country' => $r->country,
                    'url' => url('/discover/places/'.$r->id.'/'.$r->slug),
                ];
            })
            ->values()
            ->all();

        return $places;
    }

    public function searchMentionAutocomplete(Request $request)
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'q' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[@]?[a-zA-Z0-9._-]+$/',
            ],
        ]);

        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');

        $q = $request->input('q');

        $cleanQuery = Str::of($q)->startsWith('@') ? Str::substr($q, 1) : $q;

        if (strlen($cleanQuery) < 2) {
            return [];
        }

        $blocked = UserFilter::whereFilterableType('App\Profile')
            ->whereFilterType('block')
            ->whereFilterableId($request->user()->profile_id)
            ->pluck('user_id')
            ->push($request->user()->profile_id);

        $currentUserId = $request->user()->profile_id;
        $operator = config('database.default') === 'pgsql' ? 'ilike' : 'like';

        $results = Profile::select([
            'profiles.id',
            'profiles.domain',
            'profiles.username',
            'profiles.followers_count',
        ])
            ->selectRaw('MAX(CASE WHEN followers.following_id IS NOT NULL THEN 1 ELSE 0 END) as is_followed')
            ->leftJoin('followers', function ($join) use ($currentUserId) {
                $join->on('followers.following_id', '=', 'profiles.id')
                    ->where('followers.profile_id', '=', $currentUserId);
            })
            ->whereNotIn('profiles.id', $blocked)
            ->where(function ($query) use ($cleanQuery, $operator) {
                $query->where('profiles.username', $operator, $cleanQuery.'%')
                    ->orWhere('profiles.username', $operator, '%'.$cleanQuery.'%');
            })
            ->groupBy('profiles.id', 'profiles.domain', 'profiles.username', 'profiles.followers_count')
            ->orderByDesc('is_followed')
            ->orderByDesc('profiles.followers_count')
            ->orderBy('profiles.username')
            ->limit(15)
            ->get()
            ->map(function ($profile) {
                $username = $profile->domain ? substr($profile->username, 1) : $profile->username;

                return [
                    'key' => '@'.Str::limit($username, 30),
                    'value' => $username,
                    'is_followed' => (bool) $profile->is_followed,
                ];
            });

        return $results;
    }

    public function searchHashtagAutocomplete(Request $request)
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'q' => 'required|string|min:2|max:50',
        ]);

        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');

        $q = $request->input('q');

        $results = Hashtag::select('slug')
            ->where('slug', 'like', '%'.$q.'%')
            ->whereIsNsfw(false)
            ->whereIsBanned(false)
            ->limit(5)
            ->get()
            ->map(function ($tag) {
                return [
                    'key' => '#'.$tag->slug,
                    'value' => $tag->slug,
                ];
            });

        return $results;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'caption' => 'nullable|string|max:'.config_cache('pixelfed.max_caption_length'),
            'media.*' => 'required',
            'media.*.id' => 'required|integer|min:1',
            'media.*.filter_class' => 'nullable|alpha_dash|max:30',
            'media.*.license' => 'nullable|string|max:140',
            'media.*.alt' => 'nullable|string|max:'.config_cache('pixelfed.max_altext_length'),
            'cw' => 'nullable|boolean',
            'visibility' => 'required|string|in:public,private,unlisted|min:2|max:10',
            'place' => 'nullable',
            'comments_disabled' => 'nullable',
            'tagged' => 'nullable',
            'license' => 'nullable|integer|min:1|max:16',
            'collections' => 'sometimes|array|min:1|max:5',
            'spoiler_text' => 'nullable|string|max:140',
            // 'optimize_media' => 'nullable'
        ]);

        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');

        if (config('costar.enabled') == true) {
            $blockedKeywords = config('costar.keyword.block');
            if ($blockedKeywords !== null && $request->caption) {
                $keywords = config('costar.keyword.block');
                foreach ($keywords as $kw) {
                    if (Str::contains($request->caption, $kw) == true) {
                        abort(400, 'Invalid object');
                    }
                }
            }
        }

        $user = $request->user();
        $profile = $user->profile;

        $limitKey = 'compose:rate-limit:store:'.$user->id;
        $limitTtl = now()->addMinutes(15);
        // $limitReached = Cache::remember($limitKey, $limitTtl, function () use ($user) {
        //     $dailyLimit = Status::whereProfileId($user->profile_id)
        //         ->whereNull('in_reply_to_id')
        //         ->whereNull('reblog_of_id')
        //         ->where('created_at', '>', now()->subDays(1))
        //         ->count();

        //     return $dailyLimit >= 1000;
        // });

        // abort_if($limitReached == true, 429);

        $license = in_array($request->input('license'), License::keys()) ? $request->input('license') : null;

        $visibility = $request->input('visibility');
        $medias = $request->input('media');
        $attachments = [];
        $status = new Status;
        $mimes = [];
        $place = $request->input('place');
        $cw = $request->input('cw');
        $tagged = $request->input('tagged');
        $optimize_media = (bool) $request->input('optimize_media');

        foreach ($medias as $k => $media) {
            if ($k + 1 > config_cache('pixelfed.max_album_length')) {
                continue;
            }
            $m = Media::findOrFail($media['id']);
            if ($m->profile_id !== $profile->id || $m->status_id) {
                abort(403, 'Invalid media id');
            }
            $m->filter_class = in_array($media['filter_class'], Filter::classes()) ? $media['filter_class'] : null;
            $m->license = $license;
            $m->caption = isset($media['alt']) ? strip_tags($media['alt']) : null;
            $m->order = isset($media['cursor']) && is_int($media['cursor']) ? (int) $media['cursor'] : $k;

            if ($cw == true || $profile->cw == true) {
                $m->is_nsfw = $cw;
                $status->is_nsfw = $cw;
            }
            $m->save();
            $attachments[] = $m;
            array_push($mimes, $m->mime);
        }

        abort_if(empty($attachments), 422);

        $mediaType = StatusController::mimeTypeCheck($mimes);

        if (in_array($mediaType, ['photo', 'video', 'photo:album']) == false) {
            abort(400, __('exception.compose.invalid.album'));
        }

        if ($place && is_array($place)) {
            $status->place_id = $place['id'];
            PlaceService::clearStatusesByPlaceId($place['id']);
        }

        if ($request->filled('comments_disabled')) {
            $status->comments_disabled = (bool) $request->input('comments_disabled');
        }

        if ($request->filled('spoiler_text') && $cw) {
            $status->cw_summary = $request->input('spoiler_text');
        }

        $defaultCaption = '';
        $status->caption = strip_tags($request->input('caption')) ?? $defaultCaption;
        $status->rendered = $defaultCaption;
        $status->scope = 'draft';
        $status->visibility = 'draft';
        $status->profile_id = $profile->id;
        $status->save();

        foreach ($attachments as $media) {
            $media->status_id = $status->id;
            $media->save();
        }

        $visibility = $profile->unlisted == true && $visibility == 'public' ? 'unlisted' : $visibility;
        $visibility = $profile->is_private ? 'private' : $visibility;
        $cw = $profile->cw == true ? true : $cw;
        $status->is_nsfw = $cw;
        $status->visibility = $visibility;
        $status->scope = $visibility;
        $status->type = $mediaType;
        $status->save();

        foreach ($tagged as $tg) {
            $mt = new MediaTag;
            $mt->status_id = $status->id;
            $mt->media_id = $status->media->first()->id;
            $mt->profile_id = $tg['id'];
            $mt->tagged_username = $tg['name'];
            $mt->is_public = true;
            $mt->metadata = json_encode([
                '_v' => 1,
            ]);
            $mt->save();
            MediaTagService::set($mt->status_id, $mt->profile_id);
            MediaTagService::sendNotification($mt);
        }

        if ($request->filled('collections')) {
            $collections = Collection::whereProfileId($profile->id)
                ->find($request->input('collections'))
                ->each(function ($collection) use ($status) {
                    $count = $collection->items()->count();
                    CollectionItem::firstOrCreate([
                        'collection_id' => $collection->id,
                        'object_type' => 'App\Status',
                        'object_id' => $status->id,
                    ], [
                        'order' => $count,
                    ]);

                    CollectionService::addItem(
                        $collection->id,
                        $status->id,
                        $count
                    );

                    $collection->updated_at = now();
                    $collection->save();
                    CollectionService::setCollection($collection->id, $collection);
                });
        }

        Cache::forget('user:account:id:'.$profile->user_id);
        Cache::forget('_api:statuses:recent_9:'.$profile->id);
        Cache::forget('profile:status_count:'.$profile->id);
        Cache::forget('status:transformer:media:attachments:'.$status->id);
        Cache::forget('profile:embed:'.$status->profile_id);
        Cache::forget($limitKey);
        NewStatusPipeline::dispatch($status);

        return $status->url();
    }

    public function storeText(Request $request)
    {
        abort_unless(config('exp.top'), 404);
        $this->validate($request, [
            'caption' => 'nullable|string|max:'.config_cache('pixelfed.max_caption_length'),
            'cw' => 'nullable|boolean',
            'visibility' => 'required|string|in:public,private,unlisted|min:2|max:10',
            'place' => 'nullable',
            'comments_disabled' => 'nullable',
            'tagged' => 'nullable',
        ]);

        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');

        if (config('costar.enabled') == true) {
            $blockedKeywords = config('costar.keyword.block');
            if ($blockedKeywords !== null && $request->caption) {
                $keywords = config('costar.keyword.block');
                foreach ($keywords as $kw) {
                    if (Str::contains($request->caption, $kw) == true) {
                        abort(400, 'Invalid object');
                    }
                }
            }
        }

        $user = $request->user();
        $profile = $user->profile;
        $visibility = $request->input('visibility');
        $status = new Status;
        $place = $request->input('place');
        $cw = $request->input('cw');
        $tagged = $request->input('tagged');
        $defaultCaption = config_cache('database.default') === 'mysql' ? null : '';

        if ($place && is_array($place)) {
            $status->place_id = $place['id'];
        }

        if ($request->filled('comments_disabled')) {
            $status->comments_disabled = (bool) $request->input('comments_disabled');
        }

        $status->caption = $request->filled('caption') ? strip_tags($request->caption) : $defaultCaption;
        $status->rendered = $defaultCaption;
        $status->profile_id = $profile->id;
        $entities = [];
        $visibility = $profile->unlisted == true && $visibility == 'public' ? 'unlisted' : $visibility;
        $cw = $profile->cw == true ? true : $cw;
        $status->is_nsfw = $cw;
        $status->visibility = $visibility;
        $status->scope = $visibility;
        $status->type = 'text';
        $status->entities = json_encode(array_merge([
            'timg' => [
                'version' => 0,
                'bg_id' => 1,
                'font_size' => strlen($status->caption) <= 140 ? 'h1' : 'h3',
                'length' => strlen($status->caption),
            ],
        ], $entities), JSON_UNESCAPED_SLASHES);
        $status->save();

        foreach ($tagged as $tg) {
            $mt = new MediaTag;
            $mt->status_id = $status->id;
            $mt->media_id = $status->media->first()->id;
            $mt->profile_id = $tg['id'];
            $mt->tagged_username = $tg['name'];
            $mt->is_public = true;
            $mt->metadata = json_encode([
                '_v' => 1,
            ]);
            $mt->save();
            MediaTagService::set($mt->status_id, $mt->profile_id);
            MediaTagService::sendNotification($mt);
        }

        Cache::forget('user:account:id:'.$profile->user_id);
        Cache::forget('_api:statuses:recent_9:'.$profile->id);
        Cache::forget('profile:status_count:'.$profile->id);

        return $status->url();
    }

    public function mediaProcessingCheck(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer|min:1',
        ]);

        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');

        $media = Media::whereUserId($request->user()->id)
            ->whereNull('status_id')
            ->findOrFail($request->input('id'));

        if (config('pixelfed.media_fast_process')) {
            return [
                'finished' => true,
            ];
        }

        $finished = false;

        switch ($media->mime) {
            case 'image/jpeg':
            case 'image/png':
            case 'video/mp4':
                $finished = (bool) config_cache('pixelfed.cloud_storage') ? (bool) $media->cdn_url : (bool) $media->processed_at;
                break;

            default:
                // code...
                break;
        }

        return [
            'finished' => $finished,
        ];
    }

    public function composeSettings(Request $request)
    {
        $uid = $request->user()->id;
        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');

        $types = config_cache('pixelfed.media_types');
        if (str_contains($types, ',')) {
            $types = explode(',', $types);
        }
        $default = [
            'allowed_media_types' => $types,
            'max_caption_length' => (int) config_cache('pixelfed.max_caption_length'),
            'default_license' => 1,
            'media_descriptions' => false,
            'max_file_size' => (int) config_cache('pixelfed.max_photo_size'),
            'max_media_attachments' => (int) config_cache('pixelfed.max_album_length'),
            'max_altext_length' => (int) config_cache('pixelfed.max_altext_length'),
        ];
        $settings = AccountService::settings($uid);
        if (isset($settings['other']) && isset($settings['other']['scope'])) {
            $s = $settings['compose_settings'];
            $s['default_scope'] = $settings['other']['scope'];
            $settings['compose_settings'] = $s;
        }

        $res = array_merge($default, $settings['compose_settings']);

        return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function createPoll(Request $request)
    {
        $this->validate($request, [
            'caption' => 'nullable|string|max:'.config_cache('pixelfed.max_caption_length'),
            'cw' => 'nullable|boolean',
            'visibility' => 'required|string|in:public,private',
            'comments_disabled' => 'nullable',
            'expiry' => 'required|in:60,360,1440,10080',
            'pollOptions' => 'required|array|min:1|max:4',
        ]);
        abort(404);
        abort_if(config('instance.polls.enabled') == false, 404, 'Polls not enabled');
        abort_if($request->user()->has_roles && ! UserRoleService::can('can-post', $request->user()->id), 403, 'Invalid permissions for this action');

        abort_if(Status::whereType('poll')
            ->whereProfileId($request->user()->profile_id)
            ->whereCaption($request->input('caption'))
            ->where('created_at', '>', now()->subDays(2))
            ->exists(), 422, 'Duplicate detected.');

        $status = new Status;
        $status->profile_id = $request->user()->profile_id;
        $status->caption = $request->input('caption');
        $status->visibility = 'draft';
        $status->scope = 'draft';
        $status->type = 'poll';
        $status->local = true;
        $status->save();

        $poll = new Poll;
        $poll->status_id = $status->id;
        $poll->profile_id = $status->profile_id;
        $poll->poll_options = $request->input('pollOptions');
        $poll->expires_at = now()->addMinutes($request->input('expiry'));
        $poll->cached_tallies = collect($poll->poll_options)->map(function ($o) {
            return 0;
        })->toArray();
        $poll->save();

        $status->visibility = $request->input('visibility');
        $status->scope = $request->input('visibility');
        $status->save();

        NewStatusPipeline::dispatch($status);

        return ['url' => $status->url()];
    }
}
