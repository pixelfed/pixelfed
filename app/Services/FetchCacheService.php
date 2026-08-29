<?php

namespace App\Services;

use App\Util\ActivityPub\Helpers;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FetchCacheService
{
    const CACHE_KEY = 'pf:fetch_cache_service:getjson:';

    public static function getJson($url, $verifyCheck = true, $ttl = 3600, $allowRedirects = true)
    {
        $vc = $verifyCheck ? 'vc1:' : 'vc0:';
        $ar = $allowRedirects ? 'ar1:' : 'ar0';
        $key = self::CACHE_KEY.sha1($url).':'.$vc.$ar.$ttl;
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        if ($verifyCheck) {
            $validated = Helpers::validateUrl($url);
            if (! $validated) {
                Cache::put($key, 1, $ttl);

                return false;
            }
            $url = $validated;
        }

        $headers = [
            'User-Agent' => '(Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')',
        ];

        // SSRF-hardening: resolve the host and pin the connection to a
        // validated public IP. Auto-redirects are disabled so a remote host
        // cannot steer the request into an internal address on a later hop.
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 443;
        $ips = $host ? Helpers::resolvePublicIps($host) : [];
        if (empty($ips)) {
            Cache::put($key, 1, $ttl);

            return false;
        }

        $options = [
            'allow_redirects' => false,
            'curl' => [
                CURLOPT_RESOLVE => [
                    $host.':'.((int) $port).':'.implode(',', array_map(
                        fn ($ip) => str_contains($ip, ':') ? '['.$ip.']' : $ip,
                        $ips
                    )),
                ],
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_FORBID_REUSE => true,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            ],
        ];

        try {
            $res = Http::withOptions($options)
                ->retry(3, function (int $attempt, $exception) {
                    return $attempt * 500;
                })
                ->acceptJson()
                ->withHeaders($headers)
                ->timeout(40)
                ->get($url);
        } catch (RequestException $e) {
            Cache::put($key, 1, $ttl);

            return false;
        } catch (ConnectionException $e) {
            Cache::put($key, 1, $ttl);

            return false;
        } catch (\Exception $e) {
            Cache::put($key, 1, $ttl);

            return false;
        }

        if (! $res->ok()) {
            Cache::put($key, 1, $ttl);

            return false;
        }

        $result = $res->json();
        Cache::put($key, $result, $ttl);

        return $result;
    }
}
