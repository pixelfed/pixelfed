<?php

namespace App\Services\Internal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class SoftwareUpdateService
{
    const CACHE_KEY = 'pf:services:software-update:';

    public static function get()
    {
        $curVersion = config('pixelfed.version');

        $versions = Cache::remember(self::CACHE_KEY . 'latest:v1.0.0', 1800, function() {
            return self::fetchLatest();
        });

        if(!$versions || !isset($versions['latest'], $versions['latest']['version'])) {
            $hideWarning = (bool) config('instance.software-update.disable_failed_warning');
            return [
                'current' => $curVersion,
                'latest' => [
                    'version' => null,
                    'published_at' => null,
                    'url' => null,
                ],
                'running_latest' => $hideWarning ? true : null
            ];
        }

        return [
            'current' => $curVersion,
            'latest' => [
                'version' => $versions['latest']['version'],
                'published_at' => $versions['latest']['published_at'],
                'url' => $versions['latest']['url'],
            ],
            'running_latest' => strval($versions['latest']['version']) === strval($curVersion)
        ];
    }

    public static function fetchLatest()
    {
        try {
            $res = Http::withOptions(['allow_redirects' => false])
                ->timeout(5)
                ->connectTimeout(5)
                ->retry(2, 500)
                ->get('https://versions.pixelfed.org/versions.json');
        } catch (RequestException $e) {
            return;
        } catch (ConnectionException $e) {
            return;
        } catch (Exception $e) {
            return;
        }

        if(!$res->ok()) {
            return;
        }

        return $res->json();
    }
}
