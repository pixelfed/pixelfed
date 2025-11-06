<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Instance;
use App\Services\InstanceService;
use App\Util\Localization\Localization;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class InstanceController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v1/instance
     *
     * @return Instance
     */
    public function instance(Request $request)
    {
        $res = Cache::remember('api:v1:instance-data-response-v1', 1800, function () {
            $contact = Cache::remember('api:v1:instance-data:contact', 604800, function () {
                if (config_cache('instance.admin.pid')) {
                    return \App\Services\AccountService::getMastodon(config_cache('instance.admin.pid'), true);
                }
            });
            $rules = Cache::remember('api:v1:instance-data:rules', 604800, function () {
                return config_cache('app.rules') ? collect(json_decode(config_cache('app.rules'), true))
                    ->map(function ($rule, $key) {
                        return [
                            'id' => $key,
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
                'version' => config('pixelfed.version'),
                'urls' => [
                    'streaming_api' => 'wss://'.config('pixelfed.domain.app'),
                ],
                'stats' => InstanceService::stats(),
                'thumbnail' => config_cache('app.banner_image') ?? url(Storage::url('public/headers/default.jpg')),
                'languages' => Localization::languages(),
                'registrations' => (bool) config_cache('pixelfed.open_registration'),
                'approval_required' => false,
                'invites_enabled' => false,
                'configuration' => [
                    'statuses' => [
                        'max_characters' => (int) config_cache('pixelfed.max_caption_length'),
                        'max_media_attachments' => (int) config_cache('pixelfed.max_album_length'),
                        'characters_reserved_per_url' => 23,
                    ],
                    'media_attachments' => [
                        'supported_mime_types' => explode(',', config_cache('pixelfed.media_types')),
                        'image_size_limit' => config_cache('pixelfed.max_photo_size') * 1024,
                        'image_matrix_limit' => 16777216,
                        'video_size_limit' => config_cache('pixelfed.max_photo_size') * 1024,
                        'video_frame_rate_limit' => 60,
                        'video_matrix_limit' => 2304000,
                    ],
                    'polls' => [
                        'max_options' => 4,
                        'max_characters_per_option' => 50,
                        'min_expiration' => 300,
                        'max_expiration' => 2629746,
                    ],
                ],
                'contact_account' => $contact,
                'rules' => $rules,
            ];
        });

        return $this->json($res);
    }

    /**
     * GET /api/v1/instance/peers
     *
     * @return array
     */
    public function instancePeers(Request $request)
    {
        abort_if(! config_cache('federation.network_timeline'), 404);
        $res = Cache::remember('api:v1:instance:peers:list', 3600, function () {
            return Instance::whereNotNull('nodeinfo_last_fetched')
                ->orderBy('id')
                ->pluck('domain')
                ->toArray();
        });

        return $this->json($res);
    }
}