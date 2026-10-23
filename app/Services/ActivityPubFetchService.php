<?php

namespace App\Services;

use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use League\Uri\BaseUri;
use Psr\Http\Message\ResponseInterface;

class ActivityPubFetchService
{
    const CACHE_KEY = 'pf:services:apfetchs:';

    private const int MAX_REDIRECTS = 2;

    private const MAX_RESPONSE_SIZE = 2 * 1024 * 1024;

    /**
     * Statuses worth another attempt later. Everything else in the 4xx
     * range (401, 403, 404, 410...) is the remote telling us no.
     */
    private const array TRANSIENT_STATUSES = [408, 425, 429];

    /**
     * Whether the most recent failed fetch in this process looked temporary
     * (timeout, connection error, DNS, 5xx, 429) rather than permanent.
     * Null when the last fetch did not fail.
     */
    private static ?bool $lastFailureTransient = null;

    /**
     * True when the fetch that just returned nothing failed in a way that
     * is likely to work if tried again later. Only meaningful right after
     * a call to get() or fetchRequest() came back empty.
     */
    public static function lastFailureWasTransient(): bool
    {
        return self::$lastFailureTransient === true;
    }

    private static function isTransientStatus(int $status): bool
    {
        return $status >= 500 || in_array($status, self::TRANSIENT_STATUSES, true);
    }

    /**
     * Record why a fetch failed. Always returns null so call sites can
     * `return self::failed(...)`.
     */
    private static function failed(bool $transient): null
    {
        self::$lastFailureTransient = $transient;

        return null;
    }

    public static function get($url, $validateUrl = true)
    {
        self::$lastFailureTransient = null;

        $url = Helpers::validateUrl($url);

        if (! $url) {
            self::failed(false);

            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            self::failed(false);

            return false;
        }

        $domainKey = base64_encode(strtolower($host));
        $urlKey = hash('sha256', $url);
        $key = self::CACHE_KEY.$domainKey.':'.$urlKey;

        return Cache::remember($key, 450, function () use ($url) {
            return self::fetchRequest($url);
        });
    }

    public static function validateUrl($url)
    {
        return Helpers::validateUrl($url);
    }

    public static function fetchRequest($url, $returnJsonFormat = false)
    {
        self::$lastFailureTransient = null;

        $currentUrl = $url;

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            $currentUrl = Helpers::validateUrl($currentUrl);

            if (! $currentUrl) {
                return self::failed(false);
            }

            $host = parse_url($currentUrl, PHP_URL_HOST);
            $port = parse_url($currentUrl, PHP_URL_PORT) ?: 443;

            if (! $host) {
                return self::failed(false);
            }

            $ips = Helpers::resolvePublicIps($host);

            if ($ips === []) {
                // A host that just delivered to us but does not resolve is
                // far more likely a DNS blip than a dead domain.
                return self::failed(true);
            }

            $headers = self::signedHeaders($currentUrl);

            try {
                $res = Http::withOptions([
                    'allow_redirects' => false,

                    'curl' => [
                        CURLOPT_RESOLVE => [
                            self::buildResolveEntry(
                                $host,
                                $port,
                                $ips
                            ),
                        ],

                        CURLOPT_FRESH_CONNECT => true,
                        CURLOPT_FORBID_REUSE => true,
                    ],

                    'on_headers' => function (ResponseInterface $response) {
                        $length = $response->getHeaderLine('Content-Length');

                        if (
                            $length !== '' &&
                            ctype_digit($length) &&
                            (int) $length > self::MAX_RESPONSE_SIZE
                        ) {
                            throw new \RuntimeException(
                                'ActivityPub response exceeds maximum size'
                            );
                        }
                    },
                ])
                    ->withHeaders($headers)
                    ->timeout(15)
                    ->connectTimeout(5)
                    ->retry(2, 250)
                    ->get($currentUrl);
            } catch (RequestException $e) {
                // retry() throws once its attempts are used up
                return self::failed(self::isTransientStatus($e->response->status()));
            } catch (ConnectionException $e) {
                return self::failed(true);
            } catch (\Throwable $e) {
                return self::failed(false);
            }

            if (in_array($res->status(), [301, 302, 303, 307, 308], true)) {
                if ($redirects >= self::MAX_REDIRECTS) {
                    return self::failed(false);
                }

                $location = $res->header('Location');

                if (! $location) {
                    return self::failed(false);
                }

                $nextUrl = self::resolveRedirect($currentUrl, $location);

                if (! $nextUrl) {
                    return self::failed(false);
                }

                $currentUrl = $nextUrl;

                continue;
            }

            if (! $res->ok()) {
                return self::failed(self::isTransientStatus($res->status()));
            }

            if (! self::hasValidContentType($res)) {
                return self::failed(false);
            }

            $body = $res->body();

            if (
                $body === '' ||
                strlen($body) > self::MAX_RESPONSE_SIZE
            ) {
                return self::failed(false);
            }

            if (! $returnJsonFormat) {
                return $body;
            }

            try {
                return json_decode(
                    $body,
                    true,
                    64,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException) {
                return self::failed(false);
            }
        }

    }

    private static function signedHeaders(string $url): array
    {
        $baseHeaders = [
            'Accept' => 'application/activity+json',
        ];

        $headers = HttpSignature::instanceActorSign(
            $url,
            false,
            $baseHeaders,
            'get'
        );

        $headers['Accept'] = 'application/activity+json';

        $headers['User-Agent'] =
            'PixelFedBot/1.0.0 (Pixelfed/'.
            config('pixelfed.version').
            '; +'.
            config('app.url').
            ')';

        return $headers;
    }

    private static function buildResolveEntry(
        string $host,
        int $port,
        array $ips
    ): string {
        $addresses = array_map(function ($ip) {
            return str_contains($ip, ':')
                ? '['.$ip.']'
                : $ip;
        }, $ips);

        return $host.':'.$port.':'.implode(',', $addresses);
    }

    private static function resolveRedirect(
        string $baseUrl,
        string $location
    ): ?string {
        $location = trim($location);

        if (
            $location === '' ||
            preg_match('/[\x00-\x20\x7f]/', $location)
        ) {
            return null;
        }

        try {
            $resolved = BaseUri::from($baseUrl)->resolve($location);

            $url = (string) $resolved;

            return Helpers::validateUrl($url)
                ? $url
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function hasValidContentType($res): bool
    {
        $contentType = $res->header('Content-Type');

        if (! $contentType) {
            return false;
        }

        $contentTypeParts = array_map(
            'trim',
            explode(';', $contentType)
        );

        $mediaType = strtolower($contentTypeParts[0]);

        if (! in_array($mediaType, [
            'application/activity+json',
            'application/ld+json',
        ], true)) {
            return false;
        }

        if ($mediaType !== 'application/ld+json') {
            return true;
        }

        foreach (array_slice($contentTypeParts, 1) as $param) {
            if (stripos($param, 'profile=') !== 0) {
                continue;
            }

            $profile = trim(
                substr($param, strlen('profile=')),
                " \"'"
            );

            if ($profile === 'https://www.w3.org/ns/activitystreams') {
                return true;
            }
        }

        return false;
    }
}
