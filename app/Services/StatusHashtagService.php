<?php

namespace App\Services;

use App\Models\Hashtag;
use App\Models\Status;
use App\Models\StatusHashtag;
use App\Transformer\Api\HashtagTransformer;

class StatusHashtagService
{
    const CACHE_KEY = 'pf:services:status-hashtag:collection:';

    public static function get($id, $page = 1, $stop = 9)
    {
        if ($page > 20) {
            return [];
        }

        $pid = request()->user() ? request()->user()->profile_id : false;
        $filtered = $pid ? UserFilterService::filters($pid) : [];

        return StatusHashtag::whereHashtagId($id)
            ->whereStatusVisibility('public')
            ->skip($stop)
            ->latest()
            ->take(9)
            ->pluck('status_id')
            ->map(function ($i) use ($id) {
                return self::getStatus($i, $id);
            })
            ->filter(function ($i) use ($filtered) {
                $status = $i['status'] ?? null;
                $accountId = $status['account']['id'] ?? null;

                if (empty($status) || ! $accountId) {
                    return false;
                }

                if (! empty($filtered) && in_array($accountId, $filtered)) {
                    return false;
                }

                return ! empty($status['media_attachments']);
            })
            ->values();
    }

    public static function coldGet($id, $start = 0, $stop = 2000)
    {
        $stop = $stop > 2000 ? 2000 : $stop;
        $ids = StatusHashtag::whereHashtagId($id)
            ->whereStatusVisibility('public')
            ->whereHas('media')
            ->latest()
            ->skip($start)
            ->take($stop)
            ->pluck('status_id');
        foreach ($ids as $key) {
            self::set($id, $key);
        }

        return $ids;
    }

    public static function set($key, $val)
    {
        return 1;
    }

    public static function del($key)
    {
        return 1;
    }

    public static function count($id)
    {
        $cc = Hashtag::find($id);
        if (! $cc) {
            return 0;
        }

        return $cc->cached_count ?? 0;
    }

    public static function getStatus($statusId, $hashtagId): array
    {
        return ['status' => StatusService::get($statusId)];
    }

    public static function statusTags($statusId)
    {
        $status = Status::with('hashtags')->find($statusId);
        if (! $status) {
            return [];
        }

        return FractalService::collection($status->hashtags, new HashtagTransformer);
    }
}
