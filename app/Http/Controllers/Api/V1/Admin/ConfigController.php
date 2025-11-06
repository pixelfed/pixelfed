<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConfigCacheService;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function getConfiguration(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:read'), 404);

        abort_unless(config('instance.enable_cc'), 400);

        return collect([
            [
                'name' => 'ActivityPub Federation',
                'description' => 'Enable activitypub federation support, compatible with Pixelfed, Mastodon and other platforms.',
                'key' => 'federation.activitypub.enabled',
            ],

            [
                'name' => 'Open Registration',
                'description' => 'Allow new account registrations.',
                'key' => 'pixelfed.open_registration',
            ],

            [
                'name' => 'Stories',
                'description' => 'Enable the ephemeral Stories feature.',
                'key' => 'instance.stories.enabled',
            ],

            [
                'name' => 'Require Email Verification',
                'description' => 'Require new accounts to verify their email address.',
                'key' => 'pixelfed.enforce_email_verification',
            ],

            [
                'name' => 'AutoSpam Detection',
                'description' => 'Detect and remove spam from public timelines.',
                'key' => 'pixelfed.bouncer.enabled',
            ],
        ])
            ->map(function ($s) {
                $s['state'] = (bool) config_cache($s['key']);

                return $s;
            });
    }

    public function updateConfiguration(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 404);

        abort_unless($request->user()->is_admin == 1, 404);
        abort_unless($request->user()->tokenCan('admin:write'), 404);

        abort_unless(config('instance.enable_cc'), 400);

        $this->validate($request, [
            'key' => 'required',
            'value' => 'required',
        ]);

        $allowedKeys = [
            'federation.activitypub.enabled',
            'pixelfed.open_registration',
            'instance.stories.enabled',
            'pixelfed.enforce_email_verification',
            'pixelfed.bouncer.enabled',
        ];

        $key = $request->input('key');
        $value = (bool) filter_var($request->input('value'), FILTER_VALIDATE_BOOLEAN);
        abort_if(! in_array($key, $allowedKeys), 400, 'Invalid cache key.');

        ConfigCacheService::put($key, $value);

        return collect([
            [
                'name' => 'ActivityPub Federation',
                'description' => 'Enable activitypub federation support, compatible with Pixelfed, Mastodon and other platforms.',
                'key' => 'federation.activitypub.enabled',
            ],

            [
                'name' => 'Open Registration',
                'description' => 'Allow new account registrations.',
                'key' => 'pixelfed.open_registration',
            ],

            [
                'name' => 'Stories',
                'description' => 'Enable the ephemeral Stories feature.',
                'key' => 'instance.stories.enabled',
            ],

            [
                'name' => 'Require Email Verification',
                'description' => 'Require new accounts to verify their email address.',
                'key' => 'pixelfed.enforce_email_verification',
            ],

            [
                'name' => 'AutoSpam Detection',
                'description' => 'Detect and remove spam from public timelines.',
                'key' => 'pixelfed.bouncer.enabled',
            ],
        ])
            ->map(function ($s) {
                $s['state'] = (bool) config_cache($s['key']);

                return $s;
            });
    }
}