<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountRevocationService
{
    /**
     * Kill every credential that lets this user reach the app:
     * Passport access + refresh tokens, auth codes, web sessions,
     * remember-me cookie, push token. Safe to call repeatedly.
     */
    public static function revokeAll(User $user): void
    {
        DB::transaction(function () use ($user) {
            DB::table('oauth_refresh_tokens')
                ->whereIn('access_token_id', function ($q) use ($user) {
                    $q->select('id')
                        ->from('oauth_access_tokens')
                        ->where('user_id', $user->id);
                })
                ->delete();

            DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
            DB::table('oauth_auth_codes')->where('user_id', $user->id)->delete();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->delete();
            }

            $user->setRememberToken(Str::random(60));
            $user->expo_token = null;
            $user->save();
        });
    }
}
