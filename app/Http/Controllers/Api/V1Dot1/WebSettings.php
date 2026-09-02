<?php

namespace App\Http\Controllers\Api\V1Dot1;

use App\Models\UserSetting;
use App\Services\NotificationAppGatewayService;
use Illuminate\Http\Request;

trait WebSettings
{
    public function getWebSettings(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $uid = $request->user()->id;
        $settings = UserSetting::firstOrCreate([
            'user_id' => $uid,
        ]);
        if (! $settings->other) {
            return [];
        }

        return $settings->other;
    }

    public function setWebSettings(Request $request): array
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $this->validate($request, [
            'field' => 'required|in:enable_reblogs,hide_reblog_banner',
            'value' => 'required',
        ]);
        $field = $request->input('field');
        $value = $request->input('value');
        $settings = UserSetting::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);
        if (! $settings->other) {
            $other = [];
        } else {
            $other = $settings->other;
        }
        $other[$field] = $value;
        $settings->other = $other;
        $settings->save();

        return [200];
    }

    public function nagState(Request $request): array
    {
        abort_unless((bool) config_cache('pixelfed.oauth_enabled'), 404);

        return [
            'active' => NotificationAppGatewayService::enabled(),
        ];
    }
}
