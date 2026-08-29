<?php

namespace App\Services;

use App\Util\ActivityPub\Helpers;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use League\Uri\BaseUri;
use Psr\Http\Message\ResponseInterface;

/**
 * SSRF-hardened fetcher for remote media (avatars, attachments).
 *
 * Unlike a bare Guzzle client or file_get_contents(), this service:
 *   - validates the URL (https-only, no userinfo, normalized host);
 *   - resolves the host and refuses any non-global (private/reserved/
 *     link-local) IP, closing the metadata.google.internal style bypass
 *     where a public-looking hostname resolves into a reserved range;
 *   - pins the connection to the already-validated IPs via CURLOPT_RESOLVE
 *     so the address we validated is the address we connect to (no
 *     TOCTOU / DNS-rebinding window);
 *   - never lets the HTTP client follow redirects automatically, instead
 *     re-validating and re-resolving every hop;
 *   - enforces a hard byte cap.
 */
class SecureMediaFetchService
{
    private const MAX_REDIRECTS = 2;

    private const CONNECT_TIMEOUT = 5;

    private const TIMEOUT = 15;

    /**
     * Perform a HEAD request through the pinned, validated path.
     *
     * @return array{length:int,mime:string}|false
     */
    public static function head(string $url, ?int $maxBytes = null)
    {
        $maxBytes = $maxBytes ?? self::defaultMaxBytes();

        return (new self)->request($url, 'head', $maxBytes);
    }

    /**
     * Download up to $maxBytes of the resource through the pinned,
     * validated path. Returns the raw body string, or false.
     *
     * @return string|false
     */
    public static function get(string $url, ?int $maxBytes = null, ?int $expectedLength = null)
    {
        $maxBytes = $maxBytes ?? self::defaultMaxBytes();
        $result = (new self)->request($url, 'get', $maxBytes, $expectedLength);

        if (! is_array($result)) {
            return false;
        }

        return $result['body'] ?? false;
    }

    /**
     * Shared request loop: validate -> resolve public IPs -> pin -> issue
     * request with redirects disabled -> re-validate each hop.
     *
     * @return array|false For 'head': ['length'=>int,'mime'=>string].
     *                     For 'get': ['body'=>string,'length'=>int,'mime'=>string].
     */
    protected function request(string $url, string $method, int $maxBytes, ?int $expectedLength = null)
    {
        $currentUrl = $url;

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            $currentUrl = Helpers::validateUrl($currentUrl);

            if (! $currentUrl) {
                return false;
            }

            $host = parse_url($currentUrl, PHP_URL_HOST);
            $scheme = parse_url($currentUrl, PHP_URL_SCHEME);
            $port = parse_url($currentUrl, PHP_URL_PORT) ?: 443;

            if (! $host || strtolower((string) $scheme) !== 'https') {
                return false;
            }

            // Resolve the host and reject if ANY resolved address is
            // non-global. Fail-closed: empty means unresolved or private.
            $ips = Helpers::resolvePublicIps($host);

            if (empty($ips)) {
                return false;
            }

            try {
                $res = Http::withOptions([
                    'allow_redirects' => false,
                    'sink' => null,
                    'curl' => [
                        CURLOPT_RESOLVE => [
                            $this->buildResolveEntry($host, (int) $port, $ips),
                        ],
                        CURLOPT_FRESH_CONNECT => true,
                        CURLOPT_FORBID_REUSE => true,
                        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                    ],
                    'on_headers' => function (ResponseInterface $response) use ($maxBytes) {
                        $length = $response->getHeaderLine('Content-Length');
                        if ($length !== '' && ctype_digit($length) && (int) $length > $maxBytes) {
                            throw new \RuntimeException('Remote media exceeds maximum size');
                        }
                    },
                ])
                    ->withHeaders(['User-Agent' => self::userAgent()])
                    ->timeout(self::TIMEOUT)
                    ->connectTimeout(self::CONNECT_TIMEOUT)
                    ->{$method}($currentUrl);
            } catch (RequestException $e) {
                return false;
            } catch (ConnectionException $e) {
                return false;
            } catch (\Throwable $e) {
                return false;
            }

            // Manual redirect handling: re-validate + re-resolve the next hop.
            if (in_array($res->status(), [301, 302, 303, 307, 308], true)) {
                if ($redirects >= self::MAX_REDIRECTS) {
                    return false;
                }
                $location = $res->header('Location');
                if (! $location) {
                    return false;
                }
                $nextUrl = $this->resolveRedirect($currentUrl, $location);
                if (! $nextUrl) {
                    return false;
                }
                $currentUrl = $nextUrl;

                continue;
            }

            if (! $res->successful()) {
                return false;
            }

            $mime = $this->normalizeMime($res->header('Content-Type'));
            $declaredLength = $res->header('Content-Length');
            $declaredLength = ($declaredLength !== null && ctype_digit((string) $declaredLength))
                ? (int) $declaredLength
                : null;

            if ($method === 'head') {
                if ($declaredLength === null || $mime === null) {
                    return false;
                }
                if ($declaredLength < 10 || $declaredLength > $maxBytes) {
                    return false;
                }

                return ['length' => $declaredLength, 'mime' => $mime];
            }

            // GET: enforce the cap against the actual body we received.
            $body = $res->body();
            $len = strlen($body);

            if ($len === 0 || $len > $maxBytes) {
                return false;
            }

            if ($expectedLength !== null && $len < $expectedLength) {
                // Received less than the HEAD promised; treat as truncated.
                // Still return what we have, capped, but never more than asked.
                $body = substr($body, 0, $expectedLength);
                $len = strlen($body);
            }

            return [
                'body' => $body,
                'length' => $len,
                'mime' => $mime ?? ($declaredLength !== null ? $mime : null),
            ];
        }

        return false;
    }

    protected function buildResolveEntry(string $host, int $port, array $ips): string
    {
        $addresses = array_map(function ($ip) {
            return str_contains($ip, ':') ? '['.$ip.']' : $ip;
        }, $ips);

        return $host.':'.$port.':'.implode(',', $addresses);
    }

    protected function resolveRedirect(string $baseUrl, string $location): ?string
    {
        $location = trim($location);

        if ($location === '' || preg_match('/[\x00-\x20\x7f]/', $location)) {
            return null;
        }

        try {
            $resolved = (string) BaseUri::from($baseUrl)->resolve($location);

            return Helpers::validateUrl($resolved) ? $resolved : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeMime(?string $contentType): ?string
    {
        if (! $contentType) {
            return null;
        }

        $mime = strtolower(trim(explode(';', $contentType)[0]));

        return $mime === '' ? null : $mime;
    }

    protected static function defaultMaxBytes(): int
    {
        // Cap on the larger of avatar/photo config limits (kB -> bytes),
        // with a sane fallback.
        $photo = (int) config_cache('pixelfed.max_photo_size');
        $avatar = (int) config('pixelfed.max_avatar_size');
        $maxKb = max($photo, $avatar, 1000);

        return $maxKb * 1000;
    }

    protected static function userAgent(): string
    {
        return 'PixelFedBot/1.0.0 (Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')';
    }
}
