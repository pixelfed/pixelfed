<?php

namespace App\Services\Groups;

use App\Models\GroupComment;
use App\Transformer\Api\GroupPostTransformer;
use Illuminate\Support\Facades\Cache;

class GroupCommentService
{
    const CACHE_KEY = 'pf:services:groups:comment:';

    public static function key($gid, $pid)
    {
        return self::CACHE_KEY.$gid.':'.$pid;
    }

    public static function get($gid, $pid)
    {
        return Cache::remember(self::key($gid, $pid), 604800, function () use ($gid, $pid) {
            $gp = GroupComment::whereGroupId($gid)->find($pid);

            if (! $gp) {
                return null;
            }

            $res = FractalService::item($gp, new GroupPostTransformer);

            $res['pf_type'] = 'group:post:comment';
            $res['url'] = $gp->url();

            // if($gp['type'] == 'poll') {
            //  $status['poll'] = PollService::get($status['id']);
            // }
            // $status['account']['url'] = url("/groups/{$gp['group_id']}/user/{$status['account']['id']}");
            return $res;
        });
    }

    public static function del($gid, $pid)
    {
        return Cache::forget(self::key($gid, $pid));
    }
}
