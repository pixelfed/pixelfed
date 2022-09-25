<?php

namespace App\Http\Controllers\Api;

use Cache;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\AccountLog;
use App\EmailVerification;
use App\Status;
use App\Report;
use App\Profile;
use App\Services\AccountService;
use App\Services\StatusService;
use App\Services\ProfileStatusService;
use Jenssegers\Agent\Agent;
use Mail;
use App\Mail\PasswordChange;

class ApiV1Dot1Controller extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function error($msg, $code = 400, $extra = [], $headers = [])
    {
        $res = [
            "msg" => $msg,
            "code" => $code
        ];
        return response()->json(array_merge($res, $extra), $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    public function report(Request $request)
    {
        $user = $request->user();

        abort_if(!$user, 403);
        abort_if($user->status != null, 403);

        $report_type = $request->input('report_type');
        $object_id = $request->input('object_id');
        $object_type = $request->input('object_type');

        $types = [
            'spam',
            'sensitive',
            'abusive',
            'underage',
            'violence',
            'copyright',
            'impersonation',
            'scam',
            'terrorism'
        ];

        if (!$report_type || !$object_id || !$object_type) {
            return $this->error("Invalid or missing parameters", 400, ["error_code" => "ERROR_INVALID_PARAMS"]);
        }

        if (!in_array($report_type, $types)) {
            return $this->error("Invalid report type", 400, ["error_code" => "ERROR_TYPE_INVALID"]);
        }

        if ($object_type === "user" && $object_id == $user->profile_id) {
            return $this->error("Cannot self report", 400, ["error_code" => "ERROR_NO_SELF_REPORTS"]);
        }

        $rpid = null;

        switch ($object_type) {
            case 'post':
                $object = Status::find($object_id);
                if (!$object) {
                    return $this->error("Invalid object id", 400, ["error_code" => "ERROR_INVALID_OBJECT_ID"]);
                }
                $object_type = 'App\Status';
                $exists = Report::whereUserId($user->id)
                    ->whereObjectId($object->id)
                    ->whereObjectType('App\Status')
                    ->count();

                $rpid = $object->profile_id;
            break;

            case 'user':
                $object = Profile::find($object_id);
                if (!$object) {
                    return $this->error("Invalid object id", 400, ["error_code" => "ERROR_INVALID_OBJECT_ID"]);
                }
                $object_type = 'App\Profile';
                $exists = Report::whereUserId($user->id)
                    ->whereObjectId($object->id)
                    ->whereObjectType('App\Profile')
                    ->count();
                $rpid = $object->id;
            break;

            default:
                return $this->error("Invalid report type", 400, ["error_code" => "ERROR_REPORT_OBJECT_TYPE_INVALID"]);
            break;
      }

        if ($exists !== 0) {
            return $this->error("Duplicate report", 400, ["error_code" => "ERROR_REPORT_DUPLICATE"]);
        }

        if ($object->profile_id == $user->profile_id) {
            return $this->error("Cannot self report", 400, ["error_code" => "ERROR_NO_SELF_REPORTS"]);
        }

        $report = new Report;
        $report->profile_id = $user->profile_id;
        $report->user_id = $user->id;
        $report->object_id = $object->id;
        $report->object_type = $object_type;
        $report->reported_profile_id = $rpid;
        $report->type = $report_type;
        $report->save();

        $res = [
            "msg" => "Successfully sent report",
            "code" => 200
        ];
        return $this->json($res);
    }

    /**
     * DELETE /api/v1.1/accounts/avatar
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        abort_if(!$user, 403);
        abort_if($user->status != null, 403);

        $avatar = $user->profile->avatar;

        if( $avatar->media_path == 'public/avatars/default.png' ||
            $avatar->media_path == 'public/avatars/default.jpg'
        ) {
            return AccountService::get($user->profile_id);
        }

        if(is_file(storage_path('app/' . $avatar->media_path))) {
            @unlink(storage_path('app/' . $avatar->media_path));
        }

        $avatar->media_path = 'public/avatars/default.jpg';
        $avatar->change_count = $avatar->change_count + 1;
        $avatar->save();

        Cache::forget('avatar:' . $user->profile_id);
        Cache::forget("avatar:{$user->profile_id}");
        Cache::forget('user:account:id:'.$user->id);
        AccountService::del($user->profile_id);

        return AccountService::get($user->profile_id);
    }

    /**
     * GET /api/v1.1/accounts/{id}/posts
     *
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function accountPosts(Request $request, $id)
    {
        $user = $request->user();

        abort_if(!$user, 403);
        abort_if($user->status != null, 403);

        $account = AccountService::get($id);

        if(!$account || $account['username'] !== $request->input('username')) {
            return $this->json([]);
        }

        $posts = ProfileStatusService::get($id);

        if(!$posts) {
            return $this->json([]);
        }

        $res = collect($posts)
            ->map(function($id) {
                return StatusService::get($id);
            })
            ->filter(function($post) {
                return $post && isset($post['account']);
            })
            ->toArray();

        return $this->json($res);
    }

    /**
     * POST /api/v1.1/accounts/change-password
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountChangePassword(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);
        abort_if($user->status != null, 403);

        $this->validate($request, [
            'current_password' => 'bail|required|current_password',
            'new_password' => 'required|min:' . config('pixelfed.min_password_length', 8),
            'confirm_password' => 'required|same:new_password'
        ],[
            'current_password' => 'The password you entered is incorrect'
        ]);

        $user->password = bcrypt($request->input('new_password'));
        $user->save();

        $log = new AccountLog;
        $log->user_id = $user->id;
        $log->item_id = $user->id;
        $log->item_type = 'App\User';
        $log->action = 'account.edit.password';
        $log->message = 'Password changed';
        $log->link = null;
        $log->ip_address = $request->ip();
        $log->user_agent = $request->userAgent();
        $log->save();

        Mail::to($request->user())->send(new PasswordChange($user));

        return $this->json(AccountService::get($user->profile_id));
    }

    /**
     * GET /api/v1.1/accounts/login-activity
     *
     * @return array
     */
    public function accountLoginActivity(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);
        abort_if($user->status != null, 403);
        $agent = new Agent();
        $currentIp = $request->ip();

        $activity = AccountLog::whereUserId($user->id)
            ->whereAction('auth.login')
            ->orderBy('created_at', 'desc')
            ->groupBy('ip_address')
            ->limit(10)
            ->get()
            ->map(function($item) use($agent, $currentIp) {
                $agent->setUserAgent($item->user_agent);
                return [
                    'id' => $item->id,
                    'action' => $item->action,
                    'ip' => $item->ip_address,
                    'ip_current' => $item->ip_address === $currentIp,
                    'is_mobile' => $agent->isMobile(),
                    'device' => $agent->device(),
                    'browser' => $agent->browser(),
                    'platform' => $agent->platform(),
                    'created_at' => $item->created_at->format('c')
                ];
            });

        return $this->json($activity);
    }

    /**
     * GET /api/v1.1/accounts/two-factor
     *
     * @return array
     */
    public function accountTwoFactor(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);
        abort_if($user->status != null, 403);

        $res = [
            'active' => (bool) $user->{'2fa_enabled'},
            'setup_at' => $user->{'2fa_setup_at'}
        ];
        return $this->json($res);
    }

    /**
     * GET /api/v1.1/accounts/emails-from-pixelfed
     *
     * @return array
     */
    public function accountEmailsFromPixelfed(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);
        abort_if($user->status != null, 403);
        $from = config('mail.from.address');

        $emailVerifications = EmailVerification::whereUserId($user->id)
            ->orderByDesc('id')
            ->where('created_at', '>', now()->subDays(14))
            ->limit(10)
            ->get()
            ->map(function($mail) use($user, $from) {
                return [
                    'type' => 'Email Verification',
                    'subject' => 'Confirm Email',
                    'to_address' => $user->email,
                    'from_address' => $from,
                    'created_at' => str_replace('@', 'at', $mail->created_at->format('M j, Y @ g:i:s A'))
                ];
            })
            ->toArray();

        $passwordResets = DB::table('password_resets')
            ->whereEmail($user->email)
            ->where('created_at', '>', now()->subDays(14))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function($mail) use($user, $from) {
                return [
                    'type' => 'Password Reset',
                    'subject' => 'Reset Password Notification',
                    'to_address' => $user->email,
                    'from_address' => $from,
                    'created_at' => str_replace('@', 'at', now()->parse($mail->created_at)->format('M j, Y @ g:i:s A'))
                ];
            })
            ->toArray();

        $passwordChanges = AccountLog::whereUserId($user->id)
            ->whereAction('account.edit.password')
            ->where('created_at', '>', now()->subDays(14))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function($mail) use($user, $from) {
                return [
                    'type' => 'Password Change',
                    'subject' => 'Password Change',
                    'to_address' => $user->email,
                    'from_address' => $from,
                    'created_at' => str_replace('@', 'at', now()->parse($mail->created_at)->format('M j, Y @ g:i:s A'))
                ];
            })
            ->toArray();

        $res = collect([])
            ->merge($emailVerifications)
            ->merge($passwordResets)
            ->merge($passwordChanges)
            ->sortByDesc('created_at')
            ->values();

        return $this->json($res);
    }


    /**
     * GET /api/v1.1/accounts/apps-and-applications
     *
     * @return array
     */
    public function accountApps(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 403);
        abort_if($user->status != null, 403);

        $res = $user->tokens->sortByDesc('created_at')->take(10)->map(function($token, $key) {
            return [
                'id' => $key + 1,
                'did' => encrypt($token->id),
                'name' => $token->client->name,
                'scopes' => $token->scopes,
                'revoked' => $token->revoked,
                'created_at' => str_replace('@', 'at', now()->parse($token->created_at)->format('M j, Y @ g:i:s A')),
                'expires_at' => str_replace('@', 'at', now()->parse($token->expires_at)->format('M j, Y @ g:i:s A'))
            ];
        });

        return $this->json($res);
    }
}
