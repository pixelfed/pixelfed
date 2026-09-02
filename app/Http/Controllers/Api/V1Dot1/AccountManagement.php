<?php

namespace App\Http\Controllers\Api\V1Dot1;

use App\Mail\PasswordChange;
use App\Models\AccountLog;
use App\Models\EmailVerification;
use App\Models\User;
use App\Services\AccountService;
use App\Services\BouncerService;
use App\Services\FollowerService;
use App\Services\ProfileStatusService;
use App\Services\StatusService;
use App\Services\UserAgentService;
use App\Transformer\Api\StatusTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

trait AccountManagement
{
    /**
     * DELETE /api/v1.1/accounts/avatar
     *
     * @return AccountTransformer
     */
    public function deleteAvatar(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        abort_if($user->status != null, 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $avatar = $user->profile->avatar;

        if (
            $avatar->media_path == 'public/avatars/default.png' ||
            $avatar->media_path == 'public/avatars/default.jpg'
        ) {
            return AccountService::get($user->profile_id);
        }

        if (is_file(storage_path('app/'.$avatar->media_path))) {
            @unlink(storage_path('app/'.$avatar->media_path));
        }

        $avatar->media_path = 'public/avatars/default.jpg';
        $avatar->change_count = $avatar->change_count + 1;
        $avatar->save();

        Cache::forget('avatar:'.$user->profile_id);
        Cache::forget("avatar:{$user->profile_id}");
        Cache::forget('user:account:id:'.$user->id);
        AccountService::del($user->profile_id);

        return AccountService::get($user->profile_id);
    }

    /**
     * GET /api/v1.1/accounts/{id}/posts
     *
     * @return StatusTransformer
     */
    public function accountPosts(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        abort_if($user->status != null, 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $account = AccountService::get($id);

        if (! $account || $account['username'] !== $request->input('username')) {
            return $this->json([]);
        }

        $posts = ProfileStatusService::get($id);

        if (! $posts) {
            return $this->json([]);
        }

        $res = collect($posts)
            ->map(function ($id) {
                return StatusService::get($id);
            })
            ->filter(function ($post) {
                return $post && isset($post['account']);
            })
            ->toArray();

        return $this->json($res);
    }

    /**
     * POST /api/v1.1/accounts/change-password
     *
     * @return AccountTransformer
     */
    public function accountChangePassword(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        abort_if($user->status != null, 403);
        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $this->validate($request, [
            'current_password' => 'bail|required|current_password',
            'new_password' => 'required|min:'.config('pixelfed.min_password_length', 8),
            'confirm_password' => 'required|same:new_password',
        ], [
            'current_password' => 'The password you entered is incorrect',
        ]);

        $user->password = bcrypt($request->input('new_password'));
        $user->save();

        $log = new AccountLog;
        $log->user_id = $user->id;
        $log->item_id = $user->id;
        $log->item_type = User::class;
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
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        abort_if($user->status != null, 403);
        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }
        $agent = new UserAgentService;
        $currentIp = $request->ip();

        $activity = AccountLog::whereUserId($user->id)
            ->whereAction('auth.login')
            ->orderBy('created_at', 'desc')
            ->groupBy('ip_address')
            ->limit(10)
            ->get()
            ->map(function ($item) use ($agent, $currentIp) {
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
                    'created_at' => $item->created_at->format('c'),
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
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        abort_if($user->status != null, 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $res = [
            'active' => (bool) $user->{'2fa_enabled'},
            'setup_at' => $user->{'2fa_setup_at'},
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
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        abort_if($user->status != null, 403);
        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }
        $from = config('mail.from.address');

        $emailVerifications = EmailVerification::whereUserId($user->id)
            ->orderByDesc('id')
            ->where('created_at', '>', now()->subDays(14))
            ->limit(10)
            ->get()
            ->map(function ($mail) use ($user, $from) {
                return [
                    'type' => 'Email Verification',
                    'subject' => 'Confirm Email',
                    'to_address' => $user->email,
                    'from_address' => $from,
                    'created_at' => str_replace('@', 'at', $mail->created_at->format('M j, Y @ g:i:s A')),
                ];
            })
            ->toArray();

        $passwordResets = DB::table('password_resets')
            ->whereEmail($user->email)
            ->where('created_at', '>', now()->subDays(14))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($mail) use ($user, $from) {
                return [
                    'type' => 'Password Reset',
                    'subject' => 'Reset Password Notification',
                    'to_address' => $user->email,
                    'from_address' => $from,
                    'created_at' => str_replace('@', 'at', now()->parse($mail->created_at)->format('M j, Y @ g:i:s A')),
                ];
            })
            ->toArray();

        $passwordChanges = AccountLog::whereUserId($user->id)
            ->whereAction('account.edit.password')
            ->where('created_at', '>', now()->subDays(14))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($mail) use ($user, $from) {
                return [
                    'type' => 'Password Change',
                    'subject' => 'Password Change',
                    'to_address' => $user->email,
                    'from_address' => $from,
                    'created_at' => str_replace('@', 'at', now()->parse($mail->created_at)->format('M j, Y @ g:i:s A')),
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
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        abort_if($user->status != null, 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_signups')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $res = $user->tokens->sortByDesc('created_at')->take(10)->map(function ($token, $key) use ($request) {
            return [
                'id' => $token->id,
                'current_session' => $request->user()->token()->id == $token->id,
                'name' => $token->client->name,
                'scopes' => $token->scopes,
                'revoked' => $token->revoked,
                'created_at' => str_replace('@', 'at', now()->parse($token->created_at)->format('M j, Y @ g:i:s A')),
                'expires_at' => str_replace('@', 'at', now()->parse($token->expires_at)->format('M j, Y @ g:i:s A')),
            ];
        });

        return $this->json($res);
    }

    public function getMutualAccounts(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $account = AccountService::get($id, true);
        if (! $account || ! isset($account['id'])) {
            return [];
        }
        $res = collect(FollowerService::mutualAccounts($request->user()->profile_id, $id))
            ->map(function ($accountId) {
                return AccountService::get($accountId, true);
            })
            ->filter()
            ->take(24)
            ->values();

        return $this->json($res);
    }

    public function accountUsernameToId(Request $request, $username)
    {
        abort_if(! $request->user() || ! $request->user()->token() || ! $username, 403);
        abort_unless($request->user()->tokenCan('read'), 403);
        $username = trim($username);
        $rateLimiting = (bool) config_cache('api.rate-limits.v1Dot1.accounts.usernameToId.enabled');
        $ipRateLimiting = (bool) config_cache('api.rate-limits.v1Dot1.accounts.usernameToId.ip_enabled');
        if ($ipRateLimiting) {
            $userLimit = (int) config_cache('api.rate-limits.v1Dot1.accounts.usernameToId.ip_limit');
            $userDecay = (int) config_cache('api.rate-limits.v1Dot1.accounts.usernameToId.ip_decay');
            $userKey = 'pf:apiv1.1:acctU2ID:byIp:'.$request->ip();

            if (RateLimiter::tooManyAttempts($userKey, $userLimit)) {
                $limits = [
                    'X-Rate-Limit-Limit' => $userLimit,
                    'X-Rate-Limit-Remaining' => RateLimiter::remaining($userKey, $userLimit),
                    'X-Rate-Limit-Reset' => RateLimiter::availableIn($userKey),
                ];

                return $this->json(['error' => 'Too many attempts!'], 429, $limits);
            }

            RateLimiter::increment($userKey, $userDecay);
            $limits = [
                'X-Rate-Limit-Limit' => $userLimit,
                'X-Rate-Limit-Remaining' => RateLimiter::remaining($userKey, $userLimit),
                'X-Rate-Limit-Reset' => RateLimiter::availableIn($userKey),
            ];
        }
        if ($rateLimiting) {
            $userLimit = (int) config_cache('api.rate-limits.v1Dot1.accounts.usernameToId.limit');
            $userDecay = (int) config_cache('api.rate-limits.v1Dot1.accounts.usernameToId.decay');
            $userKey = 'pf:apiv1.1:acctU2ID:byUid:'.$request->user()->id;

            if (RateLimiter::tooManyAttempts($userKey, $userLimit)) {
                $limits = [
                    'X-Rate-Limit-Limit' => $userLimit,
                    'X-Rate-Limit-Remaining' => RateLimiter::remaining($userKey, $userLimit),
                    'X-Rate-Limit-Reset' => RateLimiter::availableIn($userKey),
                ];

                return $this->json(['error' => 'Too many attempts!'], 429, $limits);
            }

            RateLimiter::increment($userKey, $userDecay);
            $limits = [
                'X-Rate-Limit-Limit' => $userLimit,
                'X-Rate-Limit-Remaining' => RateLimiter::remaining($userKey, $userLimit),
                'X-Rate-Limit-Reset' => RateLimiter::availableIn($userKey),
            ];
        }
        if (str_ends_with($username, config_cache('pixelfed.domain.app'))) {
            $pre = str_starts_with($username, '@') ? substr($username, 1) : $username;
            $parts = explode('@', $pre);
            $username = $parts[0];
        }
        $accountId = AccountService::usernameToId($username);
        if (! $accountId) {
            return [];
        }
        $account = AccountService::get($accountId);

        return $this->json($account, 200, $rateLimiting ? $limits : []);
    }
}
