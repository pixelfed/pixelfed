<?php

namespace App\Http\Controllers\Api\V2Api;

use App\Models\User;
use App\Services\AccountService;
use App\Services\InstanceService;
use App\Services\SearchApiV2Service;
use App\Services\UserRoleService;
use App\Util\Site\Nodeinfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

trait InstanceSearch
{
    public function instance(Request $request): JsonResponse
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
}
