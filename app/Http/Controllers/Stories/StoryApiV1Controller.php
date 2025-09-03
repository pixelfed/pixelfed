<?php

namespace App\Http\Controllers\Stories;

use App\DirectMessage;
use App\Follower;
use App\Http\Controllers\Controller;
use App\Http\Resources\StoryView as StoryViewResource;
use App\Jobs\StoryPipeline\StoryDelete;
use App\Jobs\StoryPipeline\StoryFanout;
use App\Jobs\StoryPipeline\StoryReplyDeliver;
use App\Jobs\StoryPipeline\StoryViewDeliver;
use App\Models\Conversation;
use App\Notification;
use App\Services\AccountService;
use App\Services\MediaPathService;
use App\Services\StoryIndexService;
use App\Services\StoryService;
use App\Status;
use App\Story;
use App\StoryView;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class StoryApiV1Controller extends Controller
{
    const RECENT_KEY = 'pf:stories:recent-by-id:';

    const RECENT_TTL = 300;

    public function carousel(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);
        $pid = $request->user()->profile_id;

        if (config('database.default') == 'pgsql') {
            $s = Cache::remember(self::RECENT_KEY.$pid, self::RECENT_TTL, function () use ($pid) {
                return Story::select('stories.*', 'followers.following_id')
                    ->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
                    ->where('followers.profile_id', $pid)
                    ->where('stories.active', true)
                    ->map(function ($s) {
                        $r = new \StdClass;
                        $r->id = $s->id;
                        $r->profile_id = $s->profile_id;
                        $r->type = $s->type;
                        $r->path = $s->path;

                        return $r;
                    })
                    ->unique('profile_id');
            });
        } else {
            $s = Cache::remember(self::RECENT_KEY.$pid, self::RECENT_TTL, function () use ($pid) {
                return Story::select('stories.*', 'followers.following_id')
                    ->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
                    ->where('followers.profile_id', $pid)
                    ->where('stories.active', true)
                    ->orderBy('id')
                    ->get();
            });
        }

        $nodes = $s->map(function ($s) use ($pid) {
            $profile = AccountService::get($s->profile_id, true);
            if (! $profile || ! isset($profile['id'])) {
                return false;
            }

            return [
                'id' => (string) $s->id,
                'pid' => (string) $s->profile_id,
                'type' => $s->type,
                'src' => url(Storage::url($s->path)),
                'duration' => $s->duration ?? 3,
                'seen' => StoryService::hasSeen($pid, $s->id),
                'created_at' => $s->created_at->format('c'),
            ];
        })
            ->filter()
            ->groupBy('pid')
            ->map(function ($item) use ($pid) {
                $profile = AccountService::get($item[0]['pid'], true);
                $url = $profile['local'] ? url("/stories/{$profile['username']}") :
                    url("/i/rs/{$profile['id']}");

                return [
                    'id' => 'pfs:'.$profile['id'],
                    'user' => [
                        'id' => (string) $profile['id'],
                        'username' => $profile['username'],
                        'username_acct' => $profile['acct'],
                        'avatar' => $profile['avatar'],
                        'local' => $profile['local'],
                        'is_author' => $profile['id'] == $pid,
                    ],
                    'nodes' => $item,
                    'url' => $url,
                    'seen' => StoryService::hasSeen($pid, StoryService::latest($profile['id'])),
                ];
            })
            ->sortBy('seen')
            ->values();

        $res = [
            'self' => [],
            'nodes' => $nodes,
        ];

        if (Story::whereProfileId($pid)->whereActive(true)->exists()) {
            $selfStories = Story::whereProfileId($pid)
                ->whereActive(true)
                ->get()
                ->map(function ($s) {
                    return [
                        'id' => (string) $s->id,
                        'type' => $s->type,
                        'src' => url(Storage::url($s->path)),
                        'duration' => $s->duration,
                        'seen' => true,
                        'created_at' => $s->created_at->format('c'),
                    ];
                })
                ->sortBy('id')
                ->values();
            $selfProfile = AccountService::get($pid, true);
            $res['self'] = [
                'user' => [
                    'id' => (string) $selfProfile['id'],
                    'username' => $selfProfile['acct'],
                    'avatar' => $selfProfile['avatar'],
                    'local' => $selfProfile['local'],
                    'is_author' => true,
                ],

                'nodes' => $selfStories,
            ];
        }

        return response()->json($res, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function selfCarousel(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);
        $pid = $request->user()->profile_id;

        if (config('database.default') == 'pgsql') {
            $s = Cache::remember(self::RECENT_KEY.$pid, self::RECENT_TTL, function () use ($pid) {
                return Story::select('stories.*', 'followers.following_id')
                    ->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
                    ->where('followers.profile_id', $pid)
                    ->where('stories.active', true)
                    ->map(function ($s) {
                        $r = new \StdClass;
                        $r->id = $s->id;
                        $r->profile_id = $s->profile_id;
                        $r->type = $s->type;
                        $r->path = $s->path;

                        return $r;
                    })
                    ->unique('profile_id');
            });
        } else {
            $s = Cache::remember(self::RECENT_KEY.$pid, self::RECENT_TTL, function () use ($pid) {
                return Story::select('stories.*', 'followers.following_id')
                    ->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
                    ->where('followers.profile_id', $pid)
                    ->where('stories.active', true)
                    ->orderBy('id')
                    ->get();
            });
        }

        $nodes = $s->map(function ($s) use ($pid) {
            $profile = AccountService::get($s->profile_id, true);
            if (! $profile || ! isset($profile['id'])) {
                return false;
            }

            return [
                'id' => (string) $s->id,
                'pid' => (string) $s->profile_id,
                'type' => $s->type,
                'src' => url(Storage::url($s->path)),
                'duration' => $s->duration ?? 3,
                'seen' => StoryService::hasSeen($pid, $s->id),
                'created_at' => $s->created_at->format('c'),
            ];
        })
            ->filter()
            ->groupBy('pid')
            ->map(function ($item) use ($pid) {
                $profile = AccountService::get($item[0]['pid'], true);
                $url = $profile['local'] ? url("/stories/{$profile['username']}") :
                    url("/i/rs/{$profile['id']}");

                return [
                    'id' => 'pfs:'.$profile['id'],
                    'user' => [
                        'id' => (string) $profile['id'],
                        'username' => $profile['username'],
                        'username_acct' => $profile['acct'],
                        'avatar' => $profile['avatar'],
                        'local' => $profile['local'],
                        'is_author' => $profile['id'] == $pid,
                    ],
                    'nodes' => $item,
                    'url' => $url,
                    'seen' => StoryService::hasSeen($pid, StoryService::latest($profile['id'])),
                ];
            })
            ->sortBy('seen')
            ->values();

        $selfProfile = AccountService::get($pid, true);
        $res = [
            'self' => [
                'user' => [
                    'id' => (string) $selfProfile['id'],
                    'username' => $selfProfile['acct'],
                    'avatar' => $selfProfile['avatar'],
                    'local' => $selfProfile['local'],
                    'is_author' => true,
                ],

                'nodes' => [],
            ],
            'nodes' => $nodes,
        ];

        if (Story::whereProfileId($pid)->whereActive(true)->exists()) {
            $selfStories = Story::whereProfileId($pid)
                ->whereActive(true)
                ->get()
                ->map(function ($s) {
                    return [
                        'id' => (string) $s->id,
                        'type' => $s->type,
                        'src' => url(Storage::url($s->path)),
                        'duration' => $s->duration,
                        'seen' => true,
                        'created_at' => $s->created_at->format('c'),
                    ];
                })
                ->sortBy('id')
                ->values();
            $res['self']['nodes'] = $selfStories;
        }

        return response()->json($res, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function add(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'file' => function () {
                return [
                    'required',
                    'mimetypes:image/jpeg,image/jpg,image/png,video/mp4',
                    'max:'.config_cache('pixelfed.max_photo_size'),
                ];
            },
            'duration' => 'sometimes|integer|min:0|max:30',
        ]);

        $user = $request->user();

        $count = Story::whereProfileId($user->profile_id)
            ->whereActive(true)
            ->where('expires_at', '>', now())
            ->count();

        if ($count >= Story::MAX_PER_DAY) {
            abort(418, 'You have reached your limit for new Stories today.');
        }

        $photo = $request->file('file');
        $path = $this->storeMedia($photo, $user);

        $story = new Story;
        $story->duration = $request->input('duration', 3);
        $story->profile_id = $user->profile_id;
        $story->type = Str::endsWith($photo->getMimeType(), 'mp4') ? 'video' : 'photo';
        $story->mime = $photo->getMimeType();
        $story->path = $path;
        $story->local = true;
        $story->size = $photo->getSize();
        $story->bearcap_token = str_random(64);
        $story->expires_at = now()->addMinutes(1440);
        $story->save();

        $url = $story->path;

        $res = [
            'code' => 200,
            'msg' => 'Successfully added',
            'media_id' => (string) $story->id,
            'media_url' => url(Storage::url($url)).'?v='.time(),
            'media_type' => $story->type,
        ];

        return $res;
    }

    public function publish(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'media_id' => 'required',
            'duration' => 'required|integer|min:0|max:30',
            'can_reply' => 'required|boolean',
            'can_react' => 'required|boolean',
        ]);

        $id = $request->input('media_id');
        $user = $request->user();
        $story = Story::whereProfileId($user->profile_id)
            ->findOrFail($id);

        $story->active = true;
        $story->duration = $request->input('duration', 10);
        $story->can_reply = $request->input('can_reply');
        $story->can_react = $request->input('can_react');
        $story->save();

        $index = app(StoryIndexService::class);
        $index->indexStory($story);

        StoryService::delLatest($story->profile_id);
        StoryFanout::dispatch($story)->onQueue('story');
        StoryService::addRotateQueue($story->id);

        return [
            'code' => 200,
            'msg' => 'Successfully published',
        ];
    }

    public function carouselNext(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);
        $pid = (int) $request->user()->profile_id;

        $index = app(StoryIndexService::class);

        $profileHydrator = function (array $ids) {
            $out = [];
            foreach ($ids as $id) {
                $p = AccountService::get($id, true);
                if ($p && isset($p['id'])) {
                    $out[(int) $p['id']] = $p;
                }
            }

            return $out;
        };

        $nodes = $index->fetchCarouselNodes($pid, $profileHydrator);

        return response()->json(
            [
                'nodes' => array_values($nodes),
            ],
            200,
            [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    public function publishNext(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $validated = $this->validate($request, [
            'image' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png',
                File::image()
                    ->min(10)
                    ->max(((int) config_cache('pixelfed.max_photo_size')) ?: (6 * 1024))
                    ->dimensions(Rule::dimensions()->width(1080)->height(1920)),
            ],
            'overlays' => 'nullable|array|min:0|max:4',
            'overlays.*.absoluteScale' => 'numeric|min:0.1|max:5',
            'overlays.*.absoluteX' => 'numeric',
            'overlays.*.absoluteY' => 'numeric',
            'overlays.*.color' => 'hex_color',
            'overlays.*.backgroundColor' => 'string|in:transparent,#FFFFFF,#000000',
            'overlays.*.content' => 'string|min:1|max:250',
            'overlays.*.fontSize' => 'numeric|min:10|max:180',
            'overlays.*.fontFamily' => 'string|in:default,serif,mono,rounded,bold',
            'overlays.*.rotation' => 'numeric|min:-360|max:360',
            'overlays.*.scale' => 'numeric|min:0.1|max:5',
            'overlays.*.x' => 'numeric',
            'overlays.*.y' => 'numeric',
            'overlays.*.type' => 'string|in:text,mention,url,hashtag',
        ]);

        $user = $request->user();
        $pid = $user->profile_id;

        $count = Story::whereProfileId($user->profile_id)
            ->whereActive(true)
            ->where('expires_at', '>', now())
            ->count();

        if ($count >= Story::MAX_PER_DAY) {
            return response()->json([
                'code' => 418,
                'error' => 'You’ve reached your daily limit of '.Story::MAX_PER_DAY.' Stories.',
            ], 418, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        DB::beginTransaction();

        try {
            $photo = $validated['image'];
            $path = $this->storeMedia($photo, $user);

            $allowedOverlayFields = [
                'absoluteScale', 'absoluteX', 'absoluteY', 'color',
                'content', 'fontSize', 'rotation', 'scale', 'x', 'y', 'type',
            ];

            $filteredOverlays = [];
            if (isset($validated['overlays'])) {
                foreach ($validated['overlays'] as $index => $overlay) {
                    $filteredOverlay = Arr::only($overlay, $allowedOverlayFields);

                    if (isset($filteredOverlay['type']) && isset($filteredOverlay['content'])) {
                        $content = $filteredOverlay['content'];
                        $type = $filteredOverlay['type'];

                        switch ($type) {
                            case 'text':
                                if (! preg_match('/^[a-zA-Z0-9\s\p{P}\p{S}]*$/u', $content)) {
                                    throw ValidationException::withMessages([
                                        "overlays.{$index}.content" => 'Text overlays contain unsupported characters.',
                                    ]);
                                }
                                break;

                            case 'hashtag':
                                if (! preg_match('/^#[A-Za-z0-9_]{1,29}$/', $content)) {
                                    throw ValidationException::withMessages([
                                        "overlays.{$index}.content" => 'Invalid hashtag overlay.',
                                    ]);
                                }
                                break;

                            case 'mention':
                                $username = ltrim($content, '@');

                                $doesFollow = DB::table('followers as f')
                                    ->where('f.following_id', $pid)
                                    ->whereExists(function ($q) use ($username) {
                                        $q->select(DB::raw(1))
                                            ->from('profiles as p')
                                            ->whereColumn('p.id', 'f.profile_id')
                                            ->where('p.username', $username);
                                    })
                                    ->exists();

                                if (! $doesFollow) {
                                    throw ValidationException::withMessages([
                                        "overlays.{$index}.content" => 'The mentioned user does not exist.',
                                    ]);
                                }

                                $filteredOverlay['content'] = $username;
                                break;

                            case 'url':
                                if (! filter_var($content, FILTER_VALIDATE_URL)) {
                                    throw ValidationException::withMessages([
                                        "overlays.{$index}.content" => 'Invalid URL format.',
                                    ]);
                                }

                                $parsedUrl = parse_url($content);
                                if (! in_array($parsedUrl['scheme'] ?? '', ['https'])) {
                                    throw ValidationException::withMessages([
                                        "overlays.{$index}.content" => 'Only HTTP and HTTPS URLs are allowed.',
                                    ]);
                                }
                                break;

                            default:
                                throw ValidationException::withMessages([
                                    "overlays.{$index}.type" => 'Invalid overlay type.',
                                ]);
                        }
                    }

                    $filteredOverlays[] = $filteredOverlay;
                }
            }

            $story = new Story;
            $story->duration = 7;
            $story->profile_id = $user->profile_id;
            $story->type = 'photo';
            $story->mime = $photo->getMimeType();
            $story->path = $path;
            $story->local = true;
            $story->size = $photo->getSize();
            $story->bearcap_token = Str::random(64);
            $story->expires_at = now()->addDay();
            $story->active = true;
            $story->story = ['overlays' => $filteredOverlays];
            $story->can_reply = false;
            $story->can_react = false;
            $story->save();

            StoryService::delLatest($story->profile_id);
            StoryFanout::dispatch($story)->onQueue('story');
            StoryService::addRotateQueue($story->id);

            DB::commit();

            $index = app(StoryIndexService::class);
            $index->indexStory($story);

            $res = [
                'code' => 200,
                'msg' => 'Successfully added',
            ];

            return response()->json($res);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Story creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            $res = [
                'code' => 500,
                'msg' => 'Failed to create story',
            ];

            return response()->json($res, 500);
        }
    }

    public function mentionAutocomplete(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $data = $request->validate([
            'q' => ['required', 'string', 'max:120'],
        ]);

        $pid = $request->user()->profile_id;

        $q = str_starts_with($data['q'], '@') ? substr($data['q'], 1) : $data['q'];

        $rows = DB::table('profiles as p')
            ->select('p.id', 'p.username')
            ->where('p.username', 'like', $q.'%')
            ->whereExists(function ($sub) use ($pid) {
                $sub->select(DB::raw(1))
                    ->from('followers as f')
                    ->whereColumn('f.profile_id', 'p.id')
                    ->where('f.following_id', $pid);
            })
            ->orderBy('p.username')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                if ($item && $item->id) {
                    return AccountService::get($item->id, true);
                }

            })
            ->filter()
            ->values();

        return response()->json($rows);
    }

    public function delete(Request $request, $id)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $user = $request->user();

        $story = Story::whereProfileId($user->profile_id)
            ->findOrFail($id);
        $story->active = false;
        $story->save();

        $index = app(StoryIndexService::class);
        $index->removeStory($id, $story->profile_id);

        StoryDelete::dispatch($story)->onQueue('story');

        return [
            'code' => 200,
            'msg' => 'Successfully deleted',
        ];
    }

    public function viewed(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'id' => 'required|min:1',
        ]);
        $id = $request->input('id');
        $pid = $request->user()->profile_id;
        $authed = $request->user()->profile;

        $story = Story::whereActive(true)->findOrFail($id);

        $profile = $story->profile;

        if ($story->profile_id == $pid) {
            return [];
        }

        $following = Follower::whereProfileId($pid)->whereFollowingId($story->profile_id)->exists();
        abort_if(! $following, 403, 'Invalid permission');

        $v = StoryView::firstOrCreate([
            'story_id' => $id,
            'profile_id' => $pid,
        ]);

        $index = app(StoryIndexService::class);
        $index->markSeen($pid, $story->profile_id, $story->id, $story->created_at);

        if ($v->wasRecentlyCreated) {

            Story::findOrFail($story->id)->increment('view_count');

            if ($story->local == false) {
                StoryViewDeliver::dispatch($story, $authed)->onQueue('story');
            }
            Cache::forget('stories:recent:by_id:'.$pid);
            StoryService::addSeen($pid, $story->id);
        }

        return ['code' => 200];
    }

    public function comment(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);
        $this->validate($request, [
            'sid' => 'required',
            'caption' => 'required|string',
        ]);
        $pid = $request->user()->profile_id;
        $text = $request->input('caption');

        $story = Story::findOrFail($request->input('sid'));

        abort_if(! $story->can_reply, 422);

        $status = new Status;
        $status->type = 'story:reply';
        $status->profile_id = $pid;
        $status->caption = $text;
        $status->scope = 'direct';
        $status->visibility = 'direct';
        $status->in_reply_to_profile_id = $story->profile_id;
        $status->entities = json_encode([
            'story_id' => $story->id,
        ]);
        $status->save();

        $dm = new DirectMessage;
        $dm->to_id = $story->profile_id;
        $dm->from_id = $pid;
        $dm->type = 'story:comment';
        $dm->status_id = $status->id;
        $dm->meta = json_encode([
            'story_username' => $story->profile->username,
            'story_actor_username' => $request->user()->username,
            'story_id' => $story->id,
            'story_media_url' => url(Storage::url($story->path)),
            'caption' => $text,
        ]);
        $dm->save();

        Conversation::updateOrInsert(
            [
                'to_id' => $story->profile_id,
                'from_id' => $pid,
            ],
            [
                'type' => 'story:comment',
                'status_id' => $status->id,
                'dm_id' => $dm->id,
                'is_hidden' => false,
            ]
        );

        if ($story->local) {
            $n = new Notification;
            $n->profile_id = $dm->to_id;
            $n->actor_id = $dm->from_id;
            $n->item_id = $dm->id;
            $n->item_type = 'App\DirectMessage';
            $n->action = 'story:comment';
            $n->save();
        } else {
            StoryReplyDeliver::dispatch($story, $status)->onQueue('story');
        }

        return [
            'code' => 200,
            'msg' => 'Sent!',
        ];
    }

    protected function storeMedia($photo, $user)
    {
        $mimes = explode(',', config_cache('pixelfed.media_types'));
        if (in_array($photo->getMimeType(), [
            'image/jpeg',
            'image/png',
            'video/mp4',
        ]) == false) {
            abort(400, 'Invalid media type');

            return;
        }

        $storagePath = MediaPathService::story($user->profile);
        $path = $photo->storePubliclyAs($storagePath, Str::random(random_int(2, 12)).'_'.Str::random(random_int(32, 35)).'_'.Str::random(random_int(1, 14)).'.'.$photo->extension());

        return $path;
    }

    public function viewers(Request $request)
    {
        abort_if(! (bool) config_cache('instance.stories.enabled') || ! $request->user(), 404);

        $this->validate($request, [
            'sid' => 'required|string|min:1|max:50',
        ]);

        $pid = $request->user()->profile_id;
        $sid = $request->input('sid');

        $story = Story::whereProfileId($pid)
            ->whereActive(true)
            ->findOrFail($sid);

        $viewers = StoryView::whereStoryId($story->id)
            ->orderByDesc('id')
            ->cursorPaginate(10)
            ->withQueryString();

        return StoryViewResource::collection($viewers);
    }
}
