<?php

namespace App\Services\Groups;

use App\Models\GroupMedia;
use App\Services\HashidService;
use Cache;

class GroupMediaService
{
    const CACHE_KEY = 'groups:media:';

    public static function path($gid, $pid, $sid = false)
    {
        if (! $gid || ! $pid) {
            return;
        }
        $groupHashid = HashidService::encode($gid);
        $monthHash = HashidService::encode(date('Y').date('n'));
        $pid = HashidService::encode($pid);
        $sid = $sid ? HashidService::encode($sid) : false;
        $path = $sid ?
            "public/g1/{$groupHashid}/{$pid}/{$monthHash}/{$sid}" :
            "public/g1/{$groupHashid}/{$pid}/{$monthHash}";

        return $path;
    }

    public static function get($statusId)
    {
        return Cache::remember(self::CACHE_KEY.$statusId, 21600, function () use ($statusId) {
            $media = GroupMedia::whereStatusId($statusId)->orderBy('order')->get();
            if (! $media) {
                return [];
            }
            $medias = $media->map(function ($media) {
                return [
                    'id' => (string) $media->id,
                    'type' => 'Document',
                    'url' => $media->url(),
                    'preview_url' => $media->url(),
                    'remote_url' => $media->url,
                    'description' => $media->cw_summary,
                    'blurhash' => $media->blurhash ?? 'U4Rfzst8?bt7ogayj[j[~pfQ9Goe%Mj[WBay',
                ];
            });

            return $medias->toArray();
        });
    }

    public static function getMastodon($id)
    {
        $media = self::get($id);
        if (! $media) {
            return [];
        }
        $medias = collect($media)
            ->map(function ($media) {
                $mime = $media['mime'] ? explode('/', $media['mime']) : false;
                unset(
                    $media['optimized_url'],
                    $media['license'],
                    $media['is_nsfw'],
                    $media['orientation'],
                    $media['filter_name'],
                    $media['filter_class'],
                    $media['mime'],
                    $media['hls_manifest']
                );

                $media['type'] = $mime ? strtolower($mime[0]) : 'unknown';

                return $media;
            })
            ->filter(function ($m) {
                return $m && isset($m['url']);
            })
            ->values();

        return $medias->toArray();
    }

    public static function del($statusId)
    {
        return Cache::forget(self::CACHE_KEY.$statusId);
    }

    public static function activitypub($statusId)
    {
        $status = self::get($statusId);
        if (! $status) {
            return [];
        }

        return collect($status)->map(function ($s) {
            $license = isset($s['license']) && $s['license']['title'] ? $s['license']['title'] : null;

            return [
                'type' => 'Document',
                'mediaType' => $s['mime'],
                'url' => $s['url'],
                'name' => $s['description'],
                'summary' => $s['description'],
                'blurhash' => $s['blurhash'],
                'license' => $license,
            ];
        });
    }
}
