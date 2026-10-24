<?php

namespace App\Services\Captcha;

use App\Contracts\CaptchaDriver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cap driver (self-hosted proof-of-work CAPTCHA).
 *
 * Verifies tokens against the Cap instance's /siteverify endpoint and renders
 * the @cap.js/widget from the jsDelivr CDN.
 *
 * The full API endpoint the widget and verifier talk to is composed from a base
 * URL (captcha.cap.endpoint) plus the site key (captcha.cap.sitekey):
 *
 *     https://cap.example.com  +  3c87a0e810  =>  https://cap.example.com/3c87a0e810/
 *
 * @see https://capjs.js.org/
 */
class CapDriver implements CaptchaDriver
{
    /**
     * Default @cap.js/widget version served from the CDN. "latest" tracks the
     * newest stable release; override via captcha.cap.widget_version.
     */
    private const string DEFAULT_WIDGET_VERSION = 'latest';

    public function name(): string
    {
        return 'cap';
    }

    public function isConfigured(): bool
    {
        return ! empty(config_cache('captcha.cap.endpoint'))
            && ! empty(config_cache('captcha.cap.sitekey'))
            && ! empty(config_cache('captcha.cap.secret'));
    }

    public function responseField(): string
    {
        return (string) config('captcha.cap.token_field', 'cap-token');
    }

    /**
     * Compose the full Cap API endpoint: "{base}/{sitekey}/".
     *
     * The base URL is the instance origin without the site key. The site key is
     * appended as a path segment with a trailing slash (required by Cap).
     */
    public function apiEndpoint(): string
    {
        $base = rtrim(trim((string) config_cache('captcha.cap.endpoint')), '/');
        $sitekey = trim((string) config_cache('captcha.cap.sitekey'), '/ ');

        if ($base === '' || $sitekey === '') {
            return '';
        }

        return $base.'/'.$sitekey.'/';
    }

    public function verify(array $input): bool
    {
        $token = $input[$this->responseField()] ?? null;

        if (empty($token)) {
            return false;
        }

        $endpoint = $this->apiEndpoint();
        if ($endpoint === '') {
            return false;
        }

        try {
            $response = Http::asJson()
                ->timeout((int) config('captcha.cap.timeout', 5))
                ->post($endpoint.'siteverify', [
                    'secret' => config_cache('captcha.cap.secret'),
                    'response' => $token,
                ]);
        } catch (\Throwable $e) {
            Log::warning('[captcha:cap] verify request failed: '.$e->getMessage());

            return (bool) config('captcha.cap.fail_open', false);
        }

        if ($response->failed()) {
            return (bool) config('captcha.cap.fail_open', false);
        }

        return (bool) $response->json('success', false);
    }

    public function render(array $attributes = []): string
    {
        $endpoint = e($this->apiEndpoint());
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
