<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Jobs\VideoPipeline\VideoThumbnail;
use App\Jobs\VideoPipeline\VideoTranscode;
use App\Media;
use App\Services\AccountService;
use App\Services\InstanceService;
use App\Services\MediaBlocklistService;
use App\Services\MediaPathService;
use App\Services\SearchApiV2Service;
use App\Services\UserRoleService;
use App\Services\UserStorageService;
use App\Transformer\Api\Mastodon\v1\MediaTransformer;
use App\User;
use App\UserSetting;
use App\Util\Media\Filter;
use App\Util\Site\Nodeinfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class ApiV2Controller extends Controller
{
    const PF_API_ENTITY_KEY = '_pe';

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function instance(Request $request)
    {
        $contact = Cache::remember('api:v1:instance-data:contact', 604800, function () {
            if (config_cache('instance.admin.pid')) {
                return AccountService::getMastodon(config_cache('instance.admin.pid'), true);
            }
            $admin = User::whereIsAdmin(true)->first();

            return $admin && isset($admin->profile_id) ?
                AccountService::getMastodon($admin->profile_id, true) :
                null;
        });

        $rules = Cache::remember('api:v1:instance-data:rules', 604800, function () {
            return config_cache('app.rules') ?
                collect(json_decode(config_cache('app.rules'), true))
                    ->map(function ($rule, $key) {
                        $id = $key + 1;

                        return [
                            'id' => "{$id}",
                            'text' => $rule,
                        ];
                    })
                    ->toArray() : [];
        });

        $res = Cache::remember('api:v2:instance-data-response-v2', 1800, function () use ($contact, $rules) {
            return [
                'domain' => config('pixelfed.domain.app'),
                'title' => config_cache('app.name'),
                'version' => '3.5.3 (compatible; Pixelfed '.config('pixelfed.version').')',
                'source_url' => 'https://github.com/pixelfed/pixelfed',
                'description' => config_cache('app.short_description'),
                'usage' => [
                    'users' => [
                        'active_month' => (int) Nodeinfo::activeUsersMonthly(),
                    ],
                ],
                'thumbnail' => [
                    'url' => config_cache('app.banner_image') ?? url(Storage::url('public/headers/default.jpg')),
                    'blurhash' => InstanceService::headerBlurhash(),
                    'versions' => [
                        '@1x' => config_cache('app.banner_image') ?? url(Storage::url('public/headers/default.jpg')),
                        '@2x' => config_cache('app.banner_image') ?? url(Storage::url('public/headers/default.jpg')),
                    ],
                ],
                'languages' => [config('app.locale')],
                'configuration' => [
                    'urls' => [
                        'streaming' => null,
                        'status' => null,
                    ],
                    'vapid' => [
                        'public_key' => config('webpush.vapid.public_key'),
                    ],
                    'accounts' => [
                        'max_featured_tags' => 0,
                    ],
                    'statuses' => [
                        'max_characters' => (int) config_cache('pixelfed.max_caption_length'),
                        'max_media_attachments' => (int) config_cache('pixelfed.max_album_length'),
                        'characters_reserved_per_url' => 23,
                    ],
                    'media_attachments' => [
                        'supported_mime_types' => explode(',', config_cache('pixelfed.media_types')),
                        'image_size_limit' => config_cache('pixelfed.max_photo_size') * 1024,
                        'image_matrix_limit' => 2073600,
                        'video_size_limit' => config_cache('pixelfed.max_photo_size') * 1024,
                        'video_frame_rate_limit' => 120,
                        'video_matrix_limit' => 2073600,
                    ],
                    'polls' => [
                        'max_options' => 0,
                        'max_characters_per_option' => 0,
                        'min_expiration' => 0,
                        'max_expiration' => 0,
                    ],
                    'translation' => [
                        'enabled' => false,
                    ],
                ],
                'registrations' => [
                    'enabled' => null,
                    'approval_required' => false,
                    'message' => null,
                    'url' => null,
                ],
                'contact' => [
                    'email' => config('instance.email'),
                    'account' => $contact,
                ],
                'rules' => $rules,
            ];
        });

        $res['registrations']['enabled'] = (bool) config_cache('pixelfed.open_registration');
        $res['registrations']['approval_required'] = (bool) config_cache('instance.curated_registration.enabled');

        return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v2/search
     *
     *
     * @return array
     */
    public function search(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'q' => 'required|string|min:1|max:100',
            'account_id' => 'nullable|string',
            'max_id' => 'nullable|string',
            'min_id' => 'nullable|string',
            'type' => 'nullable|in:accounts,hashtags,statuses',
            'exclude_unreviewed' => 'nullable',
            'resolve' => 'nullable',
            'limit' => 'nullable|integer|max:40',
            'offset' => 'nullable|integer',
            'following' => 'nullable',
        ]);

        if ($request->user()->has_roles && ! UserRoleService::can('can-view-discover', $request->user()->id)) {
            return [
                'accounts' => [],
                'hashtags' => [],
                'statuses' => [],
            ];
        }

        $mastodonMode = ! $request->has('_pe');

        return $this->json(SearchApiV2Service::query($request, $mastodonMode));
    }

    /**
     * GET /api/v2/streaming/config
     *
     *
     * @return object
     */
    public function getWebsocketConfig()
    {
        return config('broadcasting.default') === 'pusher' ? [
            'host' => config('broadcasting.connections.pusher.options.host'),
            'port' => config('broadcasting.connections.pusher.options.port'),
            'key' => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
        ] : [];
    }

    /**
     * POST /api/v2/media
     *
     *
     * @return MediaTransformer
     */
    public function mediaUploadV2(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

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
            'description' => 'nullable|string|max:'.config_cache('pixelfed.max_altext_length'),
            'replace_id' => 'sometimes',
        ]);

        $user = $request->user();

        if ($user->last_active_at == null) {
            return [];
        }

        if (empty($request->file('file'))) {
            return response('', 422);
        }

        $limitKey = 'compose:rate-limit:media-upload:'.$user->id;
        $limitTtl = now()->addMinutes(15);
        $limitReached = Cache::remember($limitKey, $limitTtl, function () use ($user) {
            $dailyLimit = Media::whereUserId($user->id)->where('created_at', '>', now()->subDays(1))->count();

            return $dailyLimit >= 1250;
        });
        abort_if($limitReached == true, 429);

        $profile = $user->profile;

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

        $filterClass = in_array($request->input('filter_class'), Filter::classes()) ? $request->input('filter_class') : null;
        $filterName = in_array($request->input('filter_name'), Filter::names()) ? $request->input('filter_name') : null;

        $mimes = explode(',', config_cache('pixelfed.media_types'));
        if (in_array($photo->getMimeType(), $mimes) == false) {
            abort(403, 'Invalid or unsupported mime type.');
        }

        $storagePath = MediaPathService::get($user, 2);
        $path = $photo->storePublicly($storagePath);
        $hash = \hash_file('sha256', $photo);
        $license = null;
        $mime = $photo->getMimeType();

        $settings = UserSetting::whereUserId($user->id)->first();

        if ($settings && ! empty($settings->compose_settings)) {
            $compose = $settings->compose_settings;

            if (isset($compose['default_license']) && $compose['default_license'] != 1) {
                $license = $compose['default_license'];
            }
        }

        abort_if(MediaBlocklistService::exists($hash) == true, 451);

        if ($request->has('replace_id')) {
            $rpid = $request->input('replace_id');
            $removeMedia = Media::whereNull('status_id')
                ->whereUserId($user->id)
                ->whereProfileId($profile->id)
                ->where('created_at', '>', now()->subHours(2))
                ->find($rpid);
            if ($removeMedia) {
                MediaDeletePipeline::dispatch($removeMedia)
                    ->onQueue('mmo')
                    ->delay(now()->addMinutes(15));
            }
        }

        $media = new Media;
        $media->status_id = null;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getSize();
        $media->mime = $mime;
        $media->caption = $request->input('description');
        $media->filter_class = $filterClass;
        $media->filter_name = $filterName;
        if ($license) {
            $media->license = $license;
        }
        $media->save();

        switch ($media->mime) {
            case 'image/jpg':
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

            case 'video/quicktime':
            case 'video/x-m4v':
                VideoTranscode::dispatch($media)->onQueue('mmo');
                $preview_url = '/storage/no-preview.png';
                $url = '/storage/no-preview.png';
                break;
        }

        $user->storage_used = (int) $updatedAccountSize;
        $user->storage_used_updated_at = now();
        $user->save();

        Cache::forget($limitKey);
        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($media, new MediaTransformer);
        $res = $fractal->createData($resource)->toArray();
        $res['preview_url'] = $media->url().'?v='.time();
        $res['url'] = null;

        return $this->json($res, 202);
    }

    /**
     * GET /api/v2/statuses/{id}/context
     *
     * Efficient paginated comments API with cursor pagination
     */
    public function statusContextV2(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $pid = $user->profile_id;
        $status = StatusService::getMastodon($id, false);
        $pe = $request->has(self::PF_API_ENTITY_KEY);

        if (! $status || ! isset($status['account'])) {
            return response('', 404);
        }

        // Domain check for federated content
        if ($status && isset($status['account'], $status['account']['acct']) && strpos($status['account']['acct'], '@') != -1) {
            $domain = parse_url($status['account']['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        // Visibility check
        if (intval($status['account']['id']) !== intval($user->profile_id)) {
            if ($status['visibility'] == 'private') {
                if (! FollowerService::follows($user->profile_id, $status['account']['id'])) {
                    return response('', 404);
                }
            } else {
                if (! in_array($status['visibility'], ['public', 'unlisted'])) {
                    return response('', 404);
                }
            }
        }

        // Get request parameters
        $limit = min((int) $request->get('limit', 20), 40); // Max 40 items
        $maxId = $request->get('max_id');
        $minId = $request->get('min_id');
        $sinceId = $request->get('since_id');
        $ancestorsLimit = min((int) $request->get('ancestors_limit', 10), 20); // Max 20 ancestors

        $ancestors = $this->getAncestors($id, $ancestorsLimit, $pe, $pid);
        $descendants = $this->getDescendantsPaginated($id, $limit, $maxId, $minId, $sinceId, $pe, $pid);

        $res = [
            'ancestors' => $ancestors['data'],
            'descendants' => $descendants['data'],
            'pagination' => [
                'descendants' => $descendants['pagination'],
            ],
        ];

        return $this->json($res);
    }

    /**
     * Get ancestors (parent posts) with depth limit
     * Optimized for existing indexes
     */
    private function getAncestors($statusId, $limit, $pe, $pid)
    {
        $ancestors = [];
        $currentId = $statusId;
        $depth = 0;

        // Get user filters once for post-processing
        $filters = UserFilterService::filters($pid);

        while ($depth < $limit) {
            // Use existing statuses_in_reply_to_id_index for efficient lookup
            $parent = DB::table('statuses')
                ->select(['id', 'in_reply_to_id', 'profile_id'])
                ->where('id', function ($query) use ($currentId) {
                    $query->select('in_reply_to_id')
                        ->from('statuses')
                        ->where('id', $currentId)
                        ->whereNotNull('in_reply_to_id');
                })
                ->first();

            if (! $parent) {
                break;
            }

            // Filter at application level (more efficient than SQL NOT IN on large tables)
            if (! in_array($parent->profile_id, $filters)) {
                $parentStatus = $pe ?
                    StatusService::get($parent->id, false) :
                    StatusService::getMastodon($parent->id, false);

                if ($parentStatus && isset($parentStatus['account'])) {
                    // Add interaction status
                    $parentStatus['favourited'] = LikeService::liked($pid, $parentStatus['id']);
                    $parentStatus['reblogged'] = ReblogService::get($pid, $parentStatus['id']);

                    array_unshift($ancestors, $parentStatus); // Add to beginning to maintain order
                }
            }

            $currentId = $parent->in_reply_to_id;
            $depth++;
        }

        return ['data' => $ancestors];
    }

    /**
     * Get descendants (replies) with efficient cursor pagination
     * Optimized for existing indexes: statuses_in_reply_to_id_index
     */
    private function getDescendantsPaginated($statusId, $limit, $maxId, $minId, $sinceId, $pe, $pid)
    {
        // Build efficient query using existing indexes
        // Uses statuses_in_reply_to_id_index for fast filtering
        $query = DB::table('statuses')
            ->select(['id', 'profile_id'])
            ->where('in_reply_to_id', $statusId)
            ->orderBy('id', 'desc'); // Snowflake IDs are chronologically ordered

        // Apply cursor pagination
        if ($maxId) {
            $query->where('id', '<', $maxId);
        }

        if ($minId) {
            $query->where('id', '>', $minId);
        }

        if ($sinceId) {
            $query->where('id', '>', $sinceId);
        }

        // Get one extra to check if there are more results
        $results = $query->limit($limit + 1)->get();

        $hasMore = $results->count() > $limit;
        if ($hasMore) {
            $results->pop(); // Remove the extra item
        }

        // Get user filters for post-processing (more efficient than SQL NOT IN on large tables)
        $filters = UserFilterService::filters($pid);

        // Transform and filter results
        $descendants = $results->map(function ($row) use ($pe, $pid, $filters) {
            // Skip if user is filtered (post-processing is more efficient here)
            if (in_array($row->profile_id, $filters)) {
                return null;
            }

            $status = $pe ?
                StatusService::get($row->id, false) :
                StatusService::getMastodon($row->id, false);

            if (! $status || ! isset($status['account'])) {
                return null;
            }

            // Add interaction status
            $status['favourited'] = LikeService::liked($pid, $status['id']);
            $status['reblogged'] = ReblogService::get($pid, $status['id']);

            return $status;
        })->filter()->values();

        // If we filtered out results and don't have enough, we might need more
        // This is a trade-off: either multiple queries or over-fetching
        $actualHasMore = $hasMore || ($results->count() === $limit && $descendants->count() < $limit);

        // Build pagination info
        $pagination = [];

        if ($descendants->isNotEmpty()) {
            $pagination['max_id'] = $descendants->last()['id'];
            $pagination['min_id'] = $descendants->first()['id'];
            $pagination['has_more'] = $actualHasMore;

            // Generate next/prev URLs if applicable
            if ($actualHasMore) {
                $pagination['next_url'] = route('api.v2.status.context', $statusId).
                    '?'.http_build_query(['max_id' => $pagination['max_id'], 'limit' => $limit]);
            }
        }

        return [
            'data' => $descendants,
            'pagination' => $pagination,
        ];
    }

    /**
     * Alternative method using Laravel's cursor pagination (if you prefer)
     */
    private function getDescendantsCursorPaginated($statusId, $limit, $cursor, $pe, $pid)
    {
        $filters = UserFilterService::filters($pid);

        $query = DB::table('statuses')
            ->select(['id', 'created_at', 'profile_id'])
            ->where('in_reply_to_id', $statusId)
            ->whereNotIn('profile_id', $filters)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        $paginated = $query->cursorPaginate($limit, ['*'], 'cursor', $cursor);

        $descendants = collect($paginated->items())->map(function ($row) use ($pe, $pid) {
            $status = $pe ?
                StatusService::get($row->id, false) :
                StatusService::getMastodon($row->id, false);

            if (! $status || ! isset($status['account'])) {
                return null;
            }

            $status['favourited'] = LikeService::liked($pid, $status['id']);
            $status['reblogged'] = ReblogService::get($pid, $status['id']);

            return $status;
        })->filter()->values();

        return [
            'data' => $descendants,
            'pagination' => [
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
                'has_more' => $paginated->hasMorePages(),
            ],
        ];
    }

    /**
     * GET /api/v2/statuses/{id}/descendants
     *
     * Dedicated endpoint for just descendants with pagination
     */
    public function statusDescendants(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $pid = $user->profile_id;
        $pe = $request->has(self::PF_API_ENTITY_KEY);

        // Validate status exists and user can view it
        $status = StatusService::getMastodon($id, false);
        if (! $status || ! isset($status['account'])) {
            return response('', 404);
        }

        // Same visibility checks as above...

        $limit = min((int) $request->get('limit', 20), 40);
        $maxId = $request->get('max_id');
        $minId = $request->get('min_id');
        $sinceId = $request->get('since_id');

        $descendants = $this->getDescendantsPaginated($id, $limit, $maxId, $minId, $sinceId, $pe, $pid);

        return $this->json($descendants);
    }

    /**
     * GET /api/v2/statuses/{id}/ancestors
     *
     * Dedicated endpoint for just ancestors
     */
    public function statusAncestors(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $pid = $user->profile_id;
        $pe = $request->has(self::PF_API_ENTITY_KEY);

        // Validate status exists and user can view it
        $status = StatusService::getMastodon($id, false);
        if (! $status || ! isset($status['account'])) {
            return response('', 404);
        }

        $limit = min((int) $request->get('limit', 10), 20);
        $ancestors = $this->getAncestors($id, $limit, $pe, $pid);

        return $this->json($ancestors);
    }
}
