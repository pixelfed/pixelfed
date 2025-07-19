<?php

namespace App\Services\Groups;

use App\Models\GroupHashtag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GroupHashtagService
{
    const CACHE_KEY = 'pf:services:groups-v1:hashtags:';

    public static function get($id)
    {
        return Cache::remember(self::CACHE_KEY.$id, 3600, function () use ($id) {
            $tag = GroupHashtag::find($id);
            if (! $tag) {
                return [];
            }

            return [
                'name' => $tag->name,
                'slug' => Str::slug($tag->name),
            ];
        });
    }
}
