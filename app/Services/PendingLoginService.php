<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class PendingLoginService
{
    public const STEP_2FA = '2fa';

    public const STEP_VERIFY = 'verify';

    public const SESSION_KEY = 'auth.pending';

    public const TTL_SECONDS = 600;

    public const MAX_2FA_ATTEMPTS = 3;

    /**
     * Begin (or restart) a pending login for a user whose password has already
     * been verified. Regenerates the session id so a fixated session can never
     * carry the pending state. Session data (including url.intended) survives.
     */
    public static function start(Request $request, User $user, bool $remember, string $step): void
    {
        $request->session()->regenerate(true);

        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'email' => $user->email,
            'remember' => $remember,
            'step' => $step,
            'attempts' => 0,
            'expires_at' => now()->addSeconds(self::TTL_SECONDS)->getTimestamp(),
        ]);
    }

    public static function pending(Request $request): ?array
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! is_array($pending) || empty($pending['user_id'])) {
            return null;
        }

        if ((int) ($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            self::clear($request);

            return null;
        }

        return $pending;
    }

    public static function step(Request $request): ?string
    {
        return self::pending($request)['step'] ?? null;
    }

    public static function email(Request $request): ?string
    {
        return self::pending($request)['email'] ?? null;
    }

    public static function remember(Request $request): bool
    {
        return (bool) (self::pending($request)['remember'] ?? false);
    }

    public static function user(Request $request): ?User
    {
        $pending = self::pending($request);

        if (! $pending) {
            return null;
        }

        $user = User::find($pending['user_id']);

        if (! $user) {
            self::clear($request);

            return null;
        }

        return $user;
    }

    public static function attemptsRemaining(Request $request): int
    {
        $pending = self::pending($request);

        if (! $pending) {
            return 0;
        }

        return max(0, self::MAX_2FA_ATTEMPTS - (int) ($pending['attempts'] ?? 0));
    }

    /**
     * Records a failed 2FA attempt. Returns true when the attempt limit has been
     * reached and the pending login has been discarded.
     */
    public static function recordFailure(Request $request): bool
    {
        $pending = self::pending($request);

        if (! $pending) {
            return true;
        }

        $pending['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;

        if ($pending['attempts'] >= self::MAX_2FA_ATTEMPTS) {
            self::clear($request);

            return true;
        }

        $request->session()->put(self::SESSION_KEY, $pending);

        return false;
    }

    public static function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /**
     * Verifies a TOTP code first, then falls back to backup codes.
     * A matched backup code is consumed.
     */
    public static function verifyCode(User $user, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if ($code === '') {
            return false;
        }

        if (strlen($code) === 6 && ctype_digit($code) && ! empty($user->{'2fa_secret'})) {
            if ((new Google2FA)->verifyKey($user->{'2fa_secret'}, $code)) {
                return true;
            }
        }

        return self::consumeBackupCode($user, $code);
    }

    protected static function consumeBackupCode(User $user, string $code): bool
    {
        $codes = $user->{'2fa_backup_codes'};

        if (is_string($codes)) {
            $codes = json_decode($codes, true);
        }

        if (! is_array($codes) || ! count($codes)) {
            return false;
        }

        foreach ($codes as $index => $stored) {
            if (is_string($stored) && hash_equals($stored, $code)) {
                unset($codes[$index]);
                $user->{'2fa_backup_codes'} = json_encode(array_values($codes));
                $user->save();

                return true;
            }
        }

        return false;
    }
}
