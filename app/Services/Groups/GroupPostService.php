<?php

namespace App\Services\Groups;

use App\Models\Group;
use App\Models\GroupPost;
use App\Services\FractalService;
use App\Transformer\Api\GroupPostTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GroupPostService
{
    const CACHE_KEY = 'pf:services:groups:post:';

    public static function key($gid, $pid)
    {
        return self::CACHE_KEY.$gid.':'.$pid;
    }

    public static function get($gid, $pid)
    {
        return Cache::remember(self::key($gid, $pid), 604800, function () use ($gid, $pid) {
            $gp = GroupPost::whereGroupId($gid)->find($pid);

            if (! $gp) {
                return null;
            }

            $res = FractalService::item($gp, new GroupPostTransformer);

            $res['pf_type'] = $gp['type'];
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

    public function getStatus(Request $request)
    {
        $gid = $request->input('gid');
        $sid = $request->input('sid');
        $pid = $request->user()?->profile_id ?? false;

        $group = Group::findOrFail($gid);

        if ($group->is_private) {
            abort_if(! $group->isMember($pid), 404);
        }

        $gp = GroupPost::whereGroupId($group->id)->whereId($sid)->firstOrFail();

        $status = GroupPostService::get($gp['group_id'], $gp['id']);
        if (! $status) {
            return false;
        }
        $status['reply_count'] = $gp['reply_count'];
        $status['favourited'] = (bool) GroupsLikeService::liked($pid, $gp['id']);
        $status['favourites_count'] = GroupsLikeService::count($gp['id']);
        $status['pf_type'] = $gp['type'];
        $status['visibility'] = 'public';
        $status['url'] = $gp->url();
        $status['account']['url'] = url("/groups/{$gp->group_id}/user/{$gp->profile_id}");

        // if($gp['type'] == 'poll') {
        //     $status['poll'] = PollService::get($status['id']);
        // }

        return $status;
    }
}
