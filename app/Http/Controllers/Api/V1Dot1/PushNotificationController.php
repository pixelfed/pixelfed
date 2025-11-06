<?php

namespace App\Http\Controllers\Api\V1Dot1;

use App\Http\Controllers\Controller;
use App\Rules\ExpoPushTokenRule;
use App\Services\NotificationAppGatewayService;
use App\Services\PushNotificationService;
use App\User;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class PushNotificationController extends Controller
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

    public function error($msg, $code = 400, $extra = [], $headers = [])
    {
        $res = [
            'msg' => $msg,
            'code' => $code,
        ];

        return response()->json(array_merge($res, $extra), $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function getPushState(Request $request)
    {
        abort_unless($request->hasHeader('X-PIXELFED-APP'), 404, 'Not found');
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('push'), 403);
        abort_unless(NotificationAppGatewayService::enabled(), 404, 'Push notifications are not supported on this server.');
        $user = $request->user();
        abort_if($user->status, 422, 'Cannot access this resource at this time');
        $res = [
            'version' => PushNotificationService::PUSH_GATEWAY_VERSION,
            'username' => (string) $user->username,
            'profile_id' => (string) $user->profile_id,
            'notify_enabled' => (bool) $user->notify_enabled,
            'has_token' => (bool) $user->expo_token,
            'notify_like' => (bool) $user->notify_like,
            'notify_follow' => (bool) $user->notify_follow,
            'notify_mention' => (bool) $user->notify_mention,
            'notify_comment' => (bool) $user->notify_comment,
        ];

        return $this->json($res);
    }

    public function disablePush(Request $request)
    {
        abort_unless($request->hasHeader('X-PIXELFED-APP'), 404, 'Not found');
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('push'), 403);
        abort_unless(NotificationAppGatewayService::enabled(), 404, 'Push notifications are not supported on this server.');
        abort_if($request->user()->status, 422, 'Cannot access this resource at this time');

        $request->user()->update([
            'notify_enabled' => false,
            'expo_token' => null,
            'notify_like' => false,
            'notify_follow' => false,
            'notify_mention' => false,
            'notify_comment' => false,
        ]);

        $user = $request->user();

        return $this->json([
            'version' => PushNotificationService::PUSH_GATEWAY_VERSION,
            'username' => (string) $user->username,
            'profile_id' => (string) $user->profile_id,
            'notify_enabled' => (bool) $user->notify_enabled,
            'has_token' => (bool) $user->expo_token,
            'notify_like' => (bool) $user->notify_like,
            'notify_follow' => (bool) $user->notify_follow,
            'notify_mention' => (bool) $user->notify_mention,
            'notify_comment' => (bool) $user->notify_comment,
        ]);
    }

    public function comparePush(Request $request)
    {
        abort_unless($request->hasHeader('X-PIXELFED-APP'), 404, 'Not found');
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('push'), 403);
        abort_unless(NotificationAppGatewayService::enabled(), 404, 'Push notifications are not supported on this server.');
        abort_if($request->user()->status, 422, 'Cannot access this resource at this time');

        $this->validate($request, [
            'expo_token' => ['required', 'string', new ExpoPushTokenRule],
        ]);

        $user = $request->user();

        if (empty($user->expo_token)) {
            return $this->json([
                'version' => PushNotificationService::PUSH_GATEWAY_VERSION,
                'username' => (string) $user->username,
                'profile_id' => (string) $user->profile_id,
                'notify_enabled' => (bool) $user->notify_enabled,
                'match' => false,
                'has_existing' => false,
            ]);
        }

        $token = $request->input('expo_token');
        $knownToken = $user->expo_token;
        $match = hash_equals($knownToken, $token);

        return $this->json([
            'version' => PushNotificationService::PUSH_GATEWAY_VERSION,
            'username' => (string) $user->username,
            'profile_id' => (string) $user->profile_id,
            'notify_enabled' => (bool) $user->notify_enabled,
            'match' => $match,
            'has_existing' => true,
        ]);
    }

    public function updatePush(Request $request)
    {
        abort_unless($request->hasHeader('X-PIXELFED-APP'), 404, 'Not found');
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('push'), 403);
        abort_unless(NotificationAppGatewayService::enabled(), 404, 'Push notifications are not supported on this server.');
        abort_if($request->user()->status, 422, 'Cannot access this resource at this time');

        $this->validate($request, [
            'notify_enabled' => 'required',
            'token' => ['required', 'string', new ExpoPushTokenRule],
            'notify_like' => 'sometimes',
            'notify_follow' => 'sometimes',
            'notify_mention' => 'sometimes',
            'notify_comment' => 'sometimes',
        ]);

        $pid = $request->user()->profile_id;
        abort_if(! $pid, 422, 'An error occured');
        $expoToken = $request->input('token');

        $existing = User::where('profile_id', '!=', $pid)->whereExpoToken($expoToken)->count();
        abort_if($existing && $existing > 5, 400, 'Push token is already used by another account');

        $request->user()->update([
            'notify_enabled' => $request->boolean('notify_enabled'),
            'expo_token' => $expoToken,
        ]);

        if ($request->filled('notify_like')) {
            $request->user()->update(['notify_like' => (bool) $request->boolean('notify_like')]);
        }
        if ($request->filled('notify_follow')) {
            $request->user()->update(['notify_follow' => (bool) $request->boolean('notify_follow')]);
        }
        if ($request->filled('notify_mention')) {
            $request->user()->update(['notify_mention' => (bool) $request->boolean('notify_mention')]);
        }
        if ($request->filled('notify_comment')) {
            $request->user()->update(['notify_comment' => (bool) $request->boolean('notify_comment')]);
        }

        $user = $request->user();

        $res = [
            'version' => PushNotificationService::PUSH_GATEWAY_VERSION,
            'notify_enabled' => (bool) $user->notify_enabled,
            'has_token' => (bool) $user->expo_token,
            'notify_like' => (bool) $user->notify_like,
            'notify_follow' => (bool) $user->notify_follow,
            'notify_mention' => (bool) $user->notify_mention,
            'notify_comment' => (bool) $user->notify_comment,
        ];

        return $this->json($res);
    }
}  
  public function nagState(Request $request)
    {
        abort_unless((bool) config_cache('pixelfed.oauth_enabled'), 404);

        return [
            'active' => NotificationAppGatewayService::enabled(),
        ];
    }