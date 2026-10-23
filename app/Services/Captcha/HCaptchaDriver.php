<?php

namespace App\Services\Captcha;

use App\Contracts\CaptchaDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * hCaptcha driver.
 *
 * Verifies tokens against api.hcaptcha.com/siteverify and renders the widget
 * script from js.hcaptcha.com.
 *
 * @see https://docs.hcaptcha.com/
 */
class HCaptchaDriver implements CaptchaDriver
{
    private const VERIFY_URL = 'https://api.hcaptcha.com/siteverify';

    private const SCRIPT_URL = 'https://js.hcaptcha.com/1/api.js';

    public function name(): string
    {
        return 'hcaptcha';
    }

    public function isConfigured(): bool
    {
        $secret = config_cache('captcha.hcaptcha.secret');
        $sitekey = config_cache('captcha.hcaptcha.sitekey');

        return ! empty($secret)
            && ! empty($sitekey)
            && $secret !== 'default_secret'
            && $sitekey !== 'default_sitekey';
    }

    public function responseField(): string
    {
        return 'h-captcha-response';
    }

    public function verify(array $input): bool
    {
        $token = $input[$this->responseField()] ?? null;

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('captcha.hcaptcha.timeout', 5))
                ->post(self::VERIFY_URL, [
                    'secret' => config_cache('captcha.hcaptcha.secret'),
                    'response' => $token,
                ]);
        } catch (\Throwable $e) {
            Log::warning('[captcha:hcaptcha] verify request failed: '.$e->getMessage());

            return (bool) config('captcha.hcaptcha.fail_open', false);
        }

        if ($response->failed()) {
            return (bool) config('captcha.hcaptcha.fail_open', false);
        }

        return (bool) $response->json('success', false);
    }

    public function render(array $attributes = []): string
    {
        $sitekey = e((string) config_cache('captcha.hcaptcha.sitekey'));

        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' '.e($key).'="'.e($value).'"';
        }

        return '<div class="h-captcha" data-sitekey="'.$sitekey.'"'.$attrs.'></div>';
    }

    public function scripts(): string
    {
        $src = self::SCRIPT_URL;

        // Localize the widget when a locale is configured.
        $lang = config('captcha.hcaptcha.lang');
        if (! empty($lang)) {
            $src .= '?hl='.urlencode((string) $lang);
        }

        return '<script src="'.e($src).'" async defer></script>';
    }
}
