<?php

namespace App\Services\Groups;

use Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Models\GroupMedia;
use App\Profile;
use App\Status;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Services\HashidService;

class GroupMediaService
{
    const CACHE_KEY = 'groups:media:';

    public static function path($gid, $pid, $sid = false)
    {
        if(!$gid || !$pid) {
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
        return Cache::remember(self::CACHE_KEY.$statusId, 21600, function() use($statusId) {
            $media = GroupMedia::whereStatusId($statusId)->orderBy('order')->get();
            if(!$media) {
                return [];
            }
            $medias = $media->map(function($media) {
                return [
                    'id'            => (string) $media->id,
                    'type'          => 'Document',
                    'url'           => $media->url(),
                    'preview_url'   => $media->url(),
                    'remote_url'    => $media->url,
                    'description'   => $media->cw_summary,
                    'blurhash'      => $media->blurhash ?? 'U4Rfzst8?bt7ogayj[j[~pfQ9Goe%Mj[WBay'
                ];
            });
            return $medias->toArray();
        });
    }

    public static function del($statusId)
    {
        return Cache::forget(self::CACHE_KEY . $statusId);
    }
}
