<?php

namespace App\Services\Captcha;

use App\Contracts\CaptchaDriver;
use Illuminate\Support\Facades\Validator;

/**
 * hCaptcha driver. Wraps the existing buzz/laravel-h-captcha package so behavior
 * is identical to the previous hardcoded integration.
 */
class HCaptchaDriver implements CaptchaDriver
{
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

        // Reuse the package's registered "captcha" validation rule so we get the
        // exact same server-side verification as before.
        return Validator::make(
            [$this->responseField() => $token],
            [$this->responseField() => 'required|captcha']
        )->passes();
    }

    public function render(array $attributes = []): string
    {
        // Resolve the buzz/laravel-h-captcha service (bound as "captcha").
        // display() already emits the widget script tag inline.
        return app('captcha')->display($attributes);
    }

    public function scripts(): string
    {
        // The hCaptcha widget script is injected by display() output/config.
        return '';
    }
}
