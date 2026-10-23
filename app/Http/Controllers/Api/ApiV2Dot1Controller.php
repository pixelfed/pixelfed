<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Models\OauthClient;
use App\Models\UserSetting;
use App\Services\AccountService;
use App\Services\AppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    /**
     * GET /api/v2.1/account/timeline/settings
     *
     * Get timeline settings for non-webUI clients, allowing them to view reblog state
     */
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

    public function accountPermanentDelete(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);
        abort_unless($request->user()->tokenCan('security:write'), 403);

        $this->validate($request, [
            'current_password' => 'required|current_password:api',
        ]);

        $user = $request->user();
        abort_if(! config('pixelfed.account_deletion'), 403);
        abort_if($user->is_admin, 403);
        $profile = $user->profile;
        abort_if($profile->status != null, 400);

        $ts = now()->addMonth();
        $user->email = $user->id;
        $user->password = '';
        $user->status = 'delete';
        $profile->status = 'delete';
        $user->delete_after = $ts;
        $profile->delete_after = $ts;
        $user->save();
        $profile->save();

        Cache::forget('profiles:private');
        AccountService::del($profile->id);
        $accessTokenIds = DB::table('oauth_access_tokens')
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();
        DB::table('oauth_refresh_tokens')
            ->whereIn('access_token_id', $accessTokenIds)
            ->delete();
        DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
        OauthClient::where('user_id', $user->id)->delete();
        DeleteAccountPipeline::dispatch($user)->onQueue('low');

        return $this->json(['message' => 'Account deleted']);
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
