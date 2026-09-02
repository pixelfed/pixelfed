<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Instance;
use App\Models\User;
use App\Services\AccountService;
use App\Services\CustomEmojiService;
use App\Services\InstanceService;
use App\Services\StatusService;
use App\Services\UserRoleService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

trait InstanceMeta
{
    /**
     * GET /api/v1/custom_emojis
     *
     * Return custom emoji
     *
     * @return array
     */
    public function customEmojis(): Response
    {
        return response(CustomEmojiService::all())->header('Content-Type', 'application/json');
    }

    /**
     * GET /api/v1/instance
     *
     *   Information about the server.
     *
     * @return Instance
     */
    public function instance(Request $request)
    {
        $res = Cache::remember('api:v1:instance-data-response-v1', 1800, function () {
            $contact = Cache::remember('api:v1:instance-data:contact', 604800, function () {
                if (config_cache('instance.admin.pid')) {
                    return AccountService::getMastodon(config_cache('instance.admin.pid'), true);
                }
                $admin = User::whereIsAdmin(true)->first();

                return $admin && isset($admin->profile_id) ?
                    AccountService::getMastodon($admin->profile_id, true) :
                    null;
            });

            $stats = Cache::remember('api:v1:instance-data:stats:v0', 43200, function () {
                return [
                    'user_count' => (int) User::count(),
                    'status_count' => (int) StatusService::totalLocalStatuses(),
                    'domain_count' => (int) Instance::count(),
                ];
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

            return [
                'uri' => config('pixelfed.domain.app'),
                'title' => config_cache('app.name'),
                'short_description' => config_cache('app.short_description'),
                'description' => config_cache('app.description'),
                'email' => config('instance.email'),
                'version' => '3.5.3 (compatible; Pixelfed '.config('pixelfed.version').')',
                'urls' => [
                    'streaming_api' => null,
                ],
                'stats' => $stats,
                'thumbnail' => config_cache('app.banner_image') ?? url(Storage::url('public/headers/default.jpg')),
                'languages' => [config('app.locale')],
                'registrations' => (bool) config_cache('pixelfed.open_registration'),
                'approval_required' => (bool) config_cache('instance.curated_registration.enabled'),
                'contact_account' => $contact,
                'rules' => $rules,
                'mobile_registration' => (bool) config_cache('pixelfed.open_registration') && config('auth.in_app_registration'),
                'configuration' => [
                    'media_attachments' => [
                        'image_matrix_limit' => 2073600,
                        'image_size_limit' => config_cache('pixelfed.max_photo_size') * 1024,
                        'supported_mime_types' => explode(',', config_cache('pixelfed.media_types')),
                        'video_frame_rate_limit' => 120,
                        'video_matrix_limit' => 2073600,
                        'video_size_limit' => config_cache('pixelfed.max_photo_size') * 1024,
                    ],
                    'polls' => [
                        'max_characters_per_option' => 50,
                        'max_expiration' => 2629746,
                        'max_options' => 4,
                        'min_expiration' => 300,
                    ],
                    'statuses' => [
                        'characters_reserved_per_url' => 23,
                        'max_characters' => (int) config_cache('pixelfed.max_caption_length'),
                        'max_media_attachments' => (int) config_cache('pixelfed.max_album_length'),
                    ],
                ],
            ];
        });

        return $this->json($res);
    }

    /**
     * GET /api/v1/conversations
     *
     *   Not implemented
     *
     * @return array
     */
    public function conversations(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1|max:40',
            'scope' => 'nullable|in:inbox,sent,requests',
            'min_id' => 'nullable|integer',
            'max_id' => 'nullable|integer',
            'since_id' => 'nullable|integer',
        ]);

        $limit = $request->input('limit', 20);
        if ($limit > 20) {
            $limit = 20;
        }
        $scope = $request->input('scope', 'inbox');
        $user = $request->user();
        $min_id = $request->input('min_id');
        $max_id = $request->input('max_id');
        $since_id = $request->input('since_id');

        if ($user->has_roles && ! UserRoleService::can('can-direct-message', $user->id)) {
            return [];
        }

        $pid = $user->profile_id;

        $isPgsql = config('database.default') == 'pgsql';

        if ($isPgsql) {
            $dms = DirectMessage::when($scope === 'inbox', function ($q) use ($pid) {
                return $q->whereIsHidden(false)
                    ->where(function ($query) use ($pid) {
                        $query->where('to_id', $pid)
                            ->orWhere('from_id', $pid);
                    });
            })
                ->when($scope === 'sent', function ($q) use ($pid) {
                    return $q->whereFromId($pid)
                        ->groupBy(['to_id', 'id']);
                })
                ->when($scope === 'requests', function ($q) use ($pid) {
                    return $q->whereToId($pid)
                        ->whereIsHidden(true);
                });
        } else {
            $dms = Conversation::when($scope === 'inbox', function ($q) use ($pid) {
                return $q->whereIsHidden(false)
                    ->where(function ($query) use ($pid) {
                        $query->where('to_id', $pid)
                            ->orWhere('from_id', $pid);
                    })
                    ->orderByDesc('status_id')
                    ->groupBy(['to_id', 'from_id']);
            })
                ->when($scope === 'sent', function ($q) use ($pid) {
                    return $q->whereFromId($pid)
                        ->groupBy('to_id');
                })
                ->when($scope === 'requests', function ($q) use ($pid) {
                    return $q->whereToId($pid)
                        ->whereIsHidden(true);
                });
        }

        if ($min_id) {
            $dms = $dms->where('id', '>', $min_id);
        }
        if ($max_id) {
            $dms = $dms->where('id', '<', $max_id);
        }
        if ($since_id) {
            $dms = $dms->where('id', '>', $since_id);
        }

        $dms = $dms->orderByDesc('status_id')->orderBy('id');

        $dmResults = $dms->limit($limit + 1)->get();

        $hasNextPage = $dmResults->count() > $limit;

        if ($hasNextPage) {
            $dmResults = $dmResults->take($limit);
        }

        $transformedDms = $dmResults->map(function ($dm) use ($pid) {
            $from = $pid == $dm->to_id ? $dm->from_id : $dm->to_id;

            return [
                'id' => $dm->id,
                'unread' => false,
                'accounts' => [
                    AccountService::getMastodon($from, true),
                ],
                'last_status' => StatusService::getDirectMessage($dm->status_id),
            ];
        })
            ->filter(function ($dm) {
                return $dm
                    && ! empty($dm['last_status'])
                    && isset($dm['accounts'])
                    && count($dm['accounts'])
                    && isset($dm['accounts'][0])
                    && isset($dm['accounts'][0]['id']);
            })
            ->unique(function ($item) {
                return $item['accounts'][0]['id'];
            })
            ->values();

        $links = [];

        if (! $transformedDms->isEmpty()) {
            $baseUrl = url()->current().'?'.http_build_query(array_merge(
                $request->except(['min_id', 'max_id', 'since_id']),
                ['limit' => $limit]
            ));

            $firstId = $transformedDms->first()['id'];
            $lastId = $transformedDms->last()['id'];

            $firstLink = $baseUrl;
            $links[] = '<'.$firstLink.'>; rel="first"';

            if ($hasNextPage) {
                $nextLink = $baseUrl.'&max_id='.$lastId;
                $links[] = '<'.$nextLink.'>; rel="next"';
            }

            if ($max_id || $since_id) {
                $prevLink = $baseUrl.'&min_id='.$firstId;
                $links[] = '<'.$prevLink.'>; rel="prev"';
            }
        }

        if (! empty($links)) {
            return response()->json($transformedDms->toArray())
                ->header('Link', implode(', ', $links));
        }

        return $this->json($transformedDms);
    }

    /**
     * GET /api/v1/instance/peers
     *
     *
     * @return array
     */
    public function instancePeers(Request $request)
    {
        if ((bool) config('instance.show_peers') == false) {
            return $this->json([]);
        }

        return $this->json(
            Cache::remember(InstanceService::CACHE_KEY_API_PEERS_LIST, now()->addHours(24), function () {
                return Instance::whereNotNull('nodeinfo_last_fetched')
                    ->whereBanned(false)
                    ->where('nodeinfo_last_fetched', '>', now()->subDays(8))
                    ->pluck('domain');
            })
        );
    }
}
