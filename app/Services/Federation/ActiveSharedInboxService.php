<?php

namespace App\Services\Federation;

use App\Profile;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ActiveSharedInboxService
{
    const CACHE_KEY = 'pf:services:asinbox:list';

    const CACHE_KEY_CHECK = 'pf:services:asinbox:list-check';

    const CACHE_FILE_NAME = 'ap_asis.json';

    const CACHE_FILE_VERSION = 1;

    public static function get()
    {
        $res = Redis::smembers(self::CACHE_KEY);

        if ($res) {
            return $res;
        }

        if (! $res && self::count() == '0') {
            return self::warmCheck();
        }
    }

    public static function contains($member)
    {
        return Redis::sismember(self::CACHE_KEY, strtolower($member));
    }

    public static function count()
    {
        return Redis::scard(self::CACHE_KEY);
    }

    public static function add($member)
    {
        if (empty($member)) {
            return false;
        }
        if (! str_starts_with(strtolower($member), 'https://')) {
            $member = 'https://'.$member;
        }
        $validUrl = Helpers::validateUrl($member, false, true);
        if (! $validUrl) {
            return false;
        }

        return Redis::sadd(self::CACHE_KEY, strtolower($member));
    }

    public static function remove($member)
    {
        if (empty($member)) {
            return false;
        }
        if (! str_starts_with(strtolower($member), 'https://')) {
            $member = 'https://'.$member;
        }
        $validUrl = Helpers::validateUrl($member);
        if (! $validUrl) {
            return false;
        }

        return Redis::srem(self::CACHE_KEY, strtolower($member));
    }

    public static function warmCheck()
    {
        if (! Cache::has(self::CACHE_KEY_CHECK)) {
            return self::warmCacheFromDatabase();
        }

        return [];
    }

    public static function warmCacheFromDatabase()
    {
        $res = self::parseCacheFileData() ? self::parseCacheFileData() : self::getFromDatabase();
        if (Storage::has(self::CACHE_FILE_NAME)) {
            $res = Storage::get(self::CACHE_FILE_NAME);
            if (! $res) {
                $res = self::getFromDatabase();
            } else {
                $res = json_decode($res, true);
                if (isset($res['version'], $res['data'], $res['created'], $res['updated'])) {
                    if (now()->parse($res['updated'])->lt(now()->subMonths(6))) {
                        $res = self::getFromDatabase();
                    } else {
                        if ($res['version'] === self::CACHE_FILE_VERSION) {
                            $res = $res['data'];
                        } else {
                            $res = self::getFromDatabase();
                        }
                    }
                } else {
                    $res = self::getFromDatabase();
                }
            }
        } else {
            $res = self::getFromDatabase();
        }

        if (! $res) {
            return [];
        }

        $filteredList = [];

        foreach ($res as $value) {
            if (! $value || ! str_starts_with($value, 'https://')) {
                continue;
            }
            $passed = self::add($value);
            if ($passed) {
                $filteredList[] = $value;
            }
        }

        self::saveCacheToDisk($filteredList);
        Cache::remember(self::CACHE_KEY_CHECK, 86400, function () {
            return true;
        });

        return $res;
    }

    public static function parseCacheFileData()
    {
        if (Storage::has(self::CACHE_FILE_NAME)) {
            $res = Storage::get(self::CACHE_FILE_NAME);
            if (! $res) {
                return false;
            } else {
                $res = json_decode($res, true);
                if (! $res || isset($res['version'], $res['data'], $res['created'], $res['updated'])) {
                    if (now()->parse($res['updated'])->lt(now()->subMonths(6))) {
                        return false;
                    } else {
                        if ($res['version'] === self::CACHE_FILE_VERSION) {
                            return $res;
                        } else {
                            return false;
                        }
                    }
                } else {
                    return false;
                }
            }
        }

        return false;
    }

    public static function transformCacheFileData($res)
    {
        return [
            'id' => 'pixelfed/storage/app/'.self::CACHE_FILE_NAME,
            'version' => self::CACHE_FILE_VERSION,
            'created' => now()->format('c'),
            'updated' => now()->format('c'),
            'length' => count($res),
            'data' => $res,
        ];
    }

    public static function updateCacheFileData()
    {
        $res = self::parseCacheFileData();
        if (! $res) {
            return false;
        }

        $diff = [];
        $nodes = $res['data'];
        $latest = self::getFromDatabase();
        $merge = array_merge($nodes, $latest);

        foreach ($merge as $val) {
            if (! in_array($val, $nodes)) {
                if (self::add($val)) {
                    $nodes[] = $val;
                } else {
                    unset($nodes[$val]);
                }
            }
        }

        $data = [
            'id' => 'pixelfed/storage/app/'.self::CACHE_FILE_NAME,
            'version' => self::CACHE_FILE_VERSION,
            'created' => $res['created'],
            'updated' => now()->format('c'),
            'length' => count($nodes),
            'data' => $nodes,
        ];

        $contents = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        Storage::put(self::CACHE_FILE_NAME, $contents);

        return 1;
    }

    public static function saveCacheToDisk($res = false)
    {
        if (! $res) {
            return;
        }

        $contents = json_encode(self::transformCacheFileData($res), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        Storage::put(self::CACHE_FILE_NAME, $contents);
    }

    public static function getFromDatabase()
    {
        return Profile::whereNotNull('sharedInbox')->groupBy('sharedInbox')->pluck('sharedInbox')->toArray();
    }

    public static function scanForUpdates()
    {
        $res = self::getFromDatabase();
        $filteredList = [];

        foreach ($res as $value) {
            if (! $value || ! str_starts_with($value, 'https://')) {
                continue;
            }
            Redis::sadd(self::CACHE_KEY, $value);
            $filteredList[] = $value;
        }

        self::saveCacheToDisk($filteredList);

        return 1;
    }
}
