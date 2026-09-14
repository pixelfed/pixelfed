<?php

namespace App\Services\Captcha;

use App\Contracts\CaptchaDriver;
use Illuminate\Http\Client\Factory as HttpFactory;
use LaravelCap\Cap;

/**
 * Cap driver (self-hosted proof-of-work CAPTCHA).
 *
 * Wraps the oliweb/laravel-cap package for verification, and renders the
 * locally-published widget (public/vendor/cap/) so no external CDN is used.
 *
 * @see https://github.com/oliweb-ch/laravel-cap
 */
class CapDriver implements CaptchaDriver
{
    /**
     * Default @cap.js/widget version served from the CDN. "latest" tracks the
     * newest stable release; override via captcha.cap.widget_version.
     */
    private const DEFAULT_WIDGET_VERSION = 'latest';

    public function name(): string
    {
        return 'cap';
    }

    public function isConfigured(): bool
    {
        return ! empty(config_cache('captcha.cap.endpoint'))
            && ! empty(config_cache('captcha.cap.secret'));
    }

    public function responseField(): string
    {
        return (string) config('captcha.cap.token_field', 'cap-token');
    }

    public function verify(array $input): bool
    {
        $token = $input[$this->responseField()] ?? null;

        if (empty($token)) {
            return false;
        }

        $cap = new Cap(app(HttpFactory::class), [
            'endpoint' => config_cache('captcha.cap.endpoint'),
            'secret' => config_cache('captcha.cap.secret'),
            'timeout' => (int) config('captcha.cap.timeout', 5),
            'fail_open' => (bool) config('captcha.cap.fail_open', false),
        ]);

        return $cap->verify((string) $token);
    }

    public function render(array $attributes = []): string
    {
        $endpoint = e((string) config_cache('captcha.cap.endpoint'));
        $field = e($this->responseField());

        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' '.e($key).'="'.e($value).'"';
        }

        return '<cap-widget data-cap-api-endpoint="'.$endpoint.'"'
            .' data-cap-hidden-field-name="'.$field.'"'.$attrs.'></cap-widget>';
    }

    public function scripts(): string
    {
        // Load the widget from the jsDelivr CDN. Defaults to the "latest"
        // stable release; pin a specific version via captcha.cap.widget_version.
        $version = trim((string) config('captcha.cap.widget_version')) ?: self::DEFAULT_WIDGET_VERSION;
        $src = 'https://cdn.jsdelivr.net/npm/@cap.js/widget@'.$version;

        return '<script src="'.e($src).'"></script>';
    }
}
