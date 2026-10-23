<?php

namespace App\Services\Internal;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SoftwareUpdateService
{
    const CACHE_KEY = 'pf:services:software-update:';

    public static function cacheKey(): string
    {
        return self::CACHE_KEY.'latest:v1.0.0';
    }

    public static function get($flushCache = false): array
    {
        $curVersion = config('pixelfed.version');

        if ($flushCache) {
            Cache::forget(self::cacheKey());
            Cache::forget('api:nodeinfo');
        }

        // GLITCH MODIFIED: update check against pixelfed-glitch GitHub releases (glitch version scheme)
        $version = Cache::remember(self::cacheKey(), 1800, function () {
            return self::fetchLatest();
        });

        // GLITCH MODIFIED START: glitch version scheme
        $version_regex = '/v*([0-9]+\.[0-9]+\.[0-9]+(?:-?[0-9a-zA-Z-\._]+)?(?:\+|-)glitch\.?([0-9]+\.[0-9]+\.[0-9](?:-?[0-9a-zA-Z-_]+)?))/';

        if(!$version || !isset($version['tag_name']) || !preg_match($version_regex, $version['tag_name'], $latest_matches)) {
        // GLITCH MODIFIED END
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

        // GLITCH MODIFIED START: compare glitch release numbers, not upstream version
        preg_match($version_regex, $curVersion, $current_matches);

        $latest_version = $latest_matches[1];
        $glitch_latest = $latest_matches[2];
        $glitch_current = $current_matches[2] ?? '0';
        $cmp = version_compare($glitch_current, $glitch_latest);
        // GLITCH MODIFIED END

        return [
            'current' => $curVersion,
            'latest' => [
                // GLITCH MODIFIED START: GitHub release payload
                'version' => $latest_version,
                'published_at' => $version['published_at'],
                'url' => $version['html_url'],
                // GLITCH MODIFIED END
            ],
            'running_latest' => $cmp >= 0,
            'ahead_of_latest' => $cmp > 0,
        ];
    }

    public static function compareVersions($current, $latest): int
    {
        return version_compare(
            self::normalizeVersion($current),
            self::normalizeVersion($latest)
        );
    }

    public static function normalizeVersion($version): string
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
                // GLITCH MODIFIED: glitch releases instead of versions.pixelfed.org
                ->get('https://api.github.com/repos/pixelfed-glitch/pixelfed/releases/latest');
        } catch (RequestException|ConnectionException|\Exception) {
            return;
        }

        if (! $res->ok()) {
            return;
        }

        return $res->json();
    }
}
