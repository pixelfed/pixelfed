<?php

namespace App\Services;

use App\Mail\ConfirmEmail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailVerificationService
{
    public const LINK_TTL_HOURS = 24;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public const AUTO_SEND_COOLDOWN_SECONDS = 600;

    /**
     * Sends a fresh verification link. Returns false when the user is already
     * verified or a link for this address was sent inside the cooldown window.
     */
    public static function send(User $user, int $cooldownSeconds = self::RESEND_COOLDOWN_SECONDS): bool
    {
        if (! is_null($user->email_verified_at)) {
            return false;
        }

        $recent = EmailVerification::where('user_id', $user->id)
            ->where('email', $user->email)
            ->where('created_at', '>', now()->subSeconds($cooldownSeconds))
            ->exists();

        if ($recent) {
            return false;
        }

        EmailVerification::where('user_id', $user->id)->delete();

        $verify = new EmailVerification;
        $verify->user_id = $user->id;
        $verify->email = $user->email;
        $verify->user_token = (string) Str::uuid().Str::random(mt_rand(5, 9));
        $verify->random_token = Str::random(mt_rand(64, 70));
        $verify->save();

        Mail::to($user->email)->send(new ConfirmEmail($verify));

        return true;
    }

    /**
     * Confirms a token pair without requiring an authenticated session.
     * Returns the verified user, or null when the link is invalid, expired,
     * or no longer matches the account's current email address.
     */
    public static function confirm(string $userToken, string $randomToken): ?User
    {
        $verify = EmailVerification::where('user_token', $userToken)
            ->where('random_token', $randomToken)
            ->first();

        if (! $verify) {
            return null;
        }

        if ($verify->created_at && $verify->created_at->lt(now()->subHours(self::LINK_TTL_HOURS))) {
            $verify->delete();

            return null;
        }

        $user = User::find($verify->user_id);

        if (! $user || strcasecmp((string) $user->email, (string) $verify->email) !== 0) {
            $verify->delete();

            return null;
        }

        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
        }

        EmailVerification::where('user_id', $user->id)->delete();

        return $user;
    }
}
