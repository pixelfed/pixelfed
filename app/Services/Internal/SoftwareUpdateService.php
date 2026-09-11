<?php

namespace App\Services\Internal;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SoftwareUpdateService
{
    const CACHE_KEY = 'pf:services:software-update:';

    public static function cacheKey()
    {
        return self::CACHE_KEY.'latest:v1.0.0';
    }

    public static function get($flushCache = false)
    {
        $curVersion = config('pixelfed.version');

        if ($flushCache) {
            Cache::forget(self::cacheKey());
        }

        $versions = Cache::remember(self::cacheKey(), 1800, function () {
            return self::fetchLatest();
        });

        if (! $versions || ! isset($versions['latest'], $versions['latest']['version'])) {
            $hideWarning = (bool) config('instance.software-update.disable_failed_warning');

            return [
                'current' => $curVersion,
                'latest' => [
                    'version' => null,
                    'published_at' => null,
                    'url' => null,
                ],
                'running_latest' => $hideWarning ? true : null,
                'ahead_of_latest' => false,
            ];
        }

        $latestVersion = $versions['latest']['version'];
        $cmp = self::compareVersions($curVersion, $latestVersion);

        return [
            'current' => $curVersion,
            'latest' => [
                'version' => $latestVersion,
                'published_at' => $versions['latest']['published_at'],
                'url' => $versions['latest']['url'],
            ],
            'running_latest' => $cmp >= 0,
            'ahead_of_latest' => $cmp > 0,
        ];
    }

    public static function compareVersions($current, $latest)
    {
        return version_compare(
            self::normalizeVersion($current),
            self::normalizeVersion($latest)
        );
    }

    public static function normalizeVersion($version)
    {
        return ltrim(trim((string) $version), 'vV');
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
        } catch (\Exception $e) {
            return;
        }

        if (! $res->ok()) {
            return;
        }

        return $res->json();
    }
}
