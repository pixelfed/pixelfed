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
            return false;
        }

        if ($verifyCheck) {
            if (! Helpers::validateUrl($url)) {
                Cache::put($key, 1, $ttl);

                return false;
            }
        }

        $headers = [
            'User-Agent' => '(Pixelfed/'.config('pixelfed.version').'; +'.config('app.url').')',
        ];

        if ($allowRedirects) {
            $options = [
                'allow_redirects' => [
                    'max' => 2,
                    'strict' => true,
                ],
            ];
        } else {
            $options = [
                'allow_redirects' => false,
            ];
        }
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

        return $res->json();
    }
}
