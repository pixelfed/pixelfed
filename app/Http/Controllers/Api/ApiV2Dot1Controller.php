<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use App\Services\AppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiV2Dot1Controller extends Controller
{
    const PF_API_ENTITY_KEY = '_pe';

    public function json($res, $code = 200, $headers = []): JsonResponse
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v2.1/config
     *
     * Optimized configuration endpoint for the new mobile app and webUI
     */
    public function getConfig(Request $request)
    {
        return $this->json(app(AppService::class)->getConfig());
    }

    public function accountTimelineSettings(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $userSettings = UserSetting::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return $this->json($this->formatTimelineSettings($userSettings));
    }

    public function updateAccountTimelineSettings(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $this->validate($request, [
            'reblogs_enabled' => 'sometimes|boolean',
            'photo_reblogs_only' => 'sometimes|boolean',
        ]);

        $userSettings = UserSetting::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        $other = is_array($userSettings->other) ? $userSettings->other : [];

        if ($request->has('reblogs_enabled')) {
            $other['enable_reblogs'] = $request->boolean('reblogs_enabled');
        }

        if ($request->has('photo_reblogs_only')) {
            $other['photo_reblogs_only'] = $request->boolean('photo_reblogs_only');
        }

        $userSettings->other = $other;
        $userSettings->save();

        return $this->json($this->formatTimelineSettings($userSettings));
    }

    protected function formatTimelineSettings(UserSetting $userSettings): array
    {
        $other = is_array($userSettings->other) ? $userSettings->other : [];

        return [
            'reblogs_enabled' => (bool) data_get($other, 'enable_reblogs', false),
            'photo_reblogs_only' => (bool) data_get($other, 'photo_reblogs_only', false),
        ];
    }
}
