<?php

namespace App\Services\Captcha;

use App\Contracts\CaptchaDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile driver.
 *
 * @see https://developers.cloudflare.com/turnstile/
 */
class TurnstileDriver implements CaptchaDriver
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    public function name(): string
    {
        return 'turnstile';
    }

    public function isConfigured(): bool
    {
        return ! empty(config_cache('captcha.turnstile.secret'))
            && ! empty(config_cache('captcha.turnstile.sitekey'));
    }

    public function responseField(): string
    {
        return 'cf-turnstile-response';
    }

    public function verify(array $input): bool
    {
        $token = $input[$this->responseField()] ?? null;

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('captcha.turnstile.timeout', 5))
                ->post(self::VERIFY_URL, [
                    'secret' => config_cache('captcha.turnstile.secret'),
                    'response' => $token,
                ]);
        } catch (\Throwable $e) {
            Log::warning('[captcha:turnstile] verify request failed: '.$e->getMessage());

            return (bool) config('captcha.turnstile.fail_open', false);
        }

        if ($response->failed()) {
            return (bool) config('captcha.turnstile.fail_open', false);
        }

        return (bool) $response->json('success', false);
    }

    public function render(array $attributes = []): string
    {
        $sitekey = e((string) config_cache('captcha.turnstile.sitekey'));

        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' '.e($key).'="'.e($value).'"';
        }

        return '<div class="cf-turnstile" data-sitekey="'.$sitekey.'"'.$attrs.'></div>';
    }

    public function scripts(): string
    {
        return '<link rel="preconnect" href="https://challenges.cloudflare.com" crossorigin>'
            .'<script src="'.self::SCRIPT_URL.'" async defer></script>';
    }
}
