<?php

namespace App\Services;

use App\Profile;
use Cache;
use Purify;

class AutolinkService
{
    const CACHE_KEY = 'pf:services:autolink:mue:';

    public static function mentionedUsernameExists($username)
    {
        if (str_starts_with($username, '@')) {
            if (substr_count($username, '@') === 1) {
                $username = substr($username, 1);
            }
        }
        $name = Purify::clean(strtolower($username));

        return Cache::remember(self::CACHE_KEY.base64_encode($name), 7200, function () use ($name) {
            return Profile::where('username', $name)->exists();
        });
    }
}
