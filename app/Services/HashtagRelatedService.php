<?php

namespace App\Services;

use DB;
use App\StatusHashtag;
use App\Models\HashtagRelated;

class HashtagRelatedService
{
    public static function get($id)
    {
        $tag = HashtagRelated::whereHashtagId($id)->first();
        if(!$tag) {
            return [];
        }
        return $tag->related_tags;
    }

    public static function fetchRelatedTags($tag)
    {
        $res = StatusHashtag::query()
            ->select('h2.name', DB::raw('COUNT(*) as related_count'))
            ->join('status_hashtags as hs2', function ($join) {
                $join->on('status_hashtags.status_id', '=', 'hs2.status_id')
                     ->whereRaw('status_hashtags.hashtag_id != hs2.hashtag_id');
            })
            ->join('hashtags as h1', 'status_hashtags.hashtag_id', '=', 'h1.id')
            ->join('hashtags as h2', 'hs2.hashtag_id', '=', 'h2.id')
            ->where('h1.name', '=', $tag)
            ->groupBy('h2.name')
            ->orderBy('related_count', 'desc')
            ->limit(30)
            ->get();

        return $res;
    }
}
