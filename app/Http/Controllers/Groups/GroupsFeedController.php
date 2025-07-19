<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupPost;
use App\Services\Groups\GroupFeedService;
use App\Services\Groups\GroupPostService;
use App\Services\Groups\GroupsLikeService;
use App\Services\GroupService;
use App\Services\RelationshipService;
use App\Services\UserFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GroupsFeedController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getSelfFeed(Request $request)
    {
        abort_if(! $request->user(), 404);
        $pid = $request->user()->profile_id;
        $limit = $request->input('limit', 5);
        $page = $request->input('page');
        $initial = $request->has('initial');

        if ($initial) {
            $res = Cache::remember('groups:self:feed:'.$pid, 900, function () use ($pid) {
                return $this->getSelfFeedV0($pid, 5, null);
            });
        } else {
            abort_if($page && $page > 5, 422);
            $res = $this->getSelfFeedV0($pid, $limit, $page);
        }

        return response()->json($res, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function getSelfFeedV0($pid, $limit, $page)
    {
        return GroupPost::join('group_members', 'group_posts.group_id', 'group_members.group_id')
            ->select('group_posts.*', 'group_members.group_id', 'group_members.profile_id')
            ->where('group_members.profile_id', $pid)
            ->whereIn('group_posts.type', ['text', 'photo', 'video'])
            ->orderByDesc('group_posts.id')
            ->limit($limit)
            // ->pluck('group_posts.status_id')
            ->simplePaginate($limit)
            ->map(function ($gp) use ($pid) {
                $status = GroupPostService::get($gp['group_id'], $gp['id']);

                if (! $status) {
                    return false;
                }

                $status['favourited'] = (bool) GroupsLikeService::liked($pid, $gp['id']);
                $status['favourites_count'] = GroupsLikeService::count($gp['id']);
                $status['pf_type'] = $gp['type'];
                $status['visibility'] = 'public';
                $status['url'] = url("/groups/{$gp['group_id']}/p/{$gp['id']}");
                $status['group'] = GroupService::get($gp['group_id']);
                $status['account']['url'] = url("/groups/{$gp['group_id']}/user/{$status['account']['id']}");

                return $status;
            });
    }

    public function getGroupProfileFeed(Request $request, $id, $pid)
    {
        abort_if(! $request->user(), 404);
        $cid = $request->user()->profile_id;

        $group = Group::findOrFail($id);
        abort_if(! $group->isMember($pid), 404);

        $feed = GroupPost::whereGroupId($id)
            ->whereProfileId($pid)
            ->latest()
            ->paginate(3)
            ->map(function ($gp) use ($pid) {
                $status = GroupPostService::get($gp['group_id'], $gp['id']);
                if (! $status) {
                    return false;
                }
                $status['favourited'] = (bool) GroupsLikeService::liked($pid, $gp['id']);
                $status['favourites_count'] = GroupsLikeService::count($gp['id']);
                $status['pf_type'] = $gp['type'];
                $status['visibility'] = 'public';
                $status['url'] = $gp->url();

                // if($gp['type'] == 'poll') {
                //     $status['poll'] = PollService::get($status['id']);
                // }

                $status['account']['url'] = "/groups/{$gp['group_id']}/user/{$status['account']['id']}";

                return $status;
            })
            ->filter(function ($status) {
                return $status;
            });

        return $feed;
    }

    public function getGroupFeed(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        $user = $request->user();
        $pid = optional($user)->profile_id ?? false;
        abort_if(! $group->isMember($pid), 404);
        $max = $request->input('max_id');
        $limit = $request->limit ?? 3;
        $filtered = $user ? UserFilterService::filters($user->profile_id) : [];

        // $posts = GroupPost::whereGroupId($group->id)
        //  ->when($maxId, function($q, $maxId) {
        //      return $q->where('status_id', '<', $maxId);
        //  })
        //  ->whereNull('in_reply_to_id')
        //  ->orderByDesc('status_id')
        //  ->simplePaginate($limit)
        //  ->map(function($gp) use($pid) {
        //      $status = StatusService::get($gp['status_id'], false);
        //      if(!$status) {
        //          return false;
        //      }
        //      $status['favourited'] = (bool) LikeService::liked($pid, $gp['status_id']);
        //      $status['favourites_count'] = LikeService::count($gp['status_id']);
        //      $status['pf_type'] = $gp['type'];
        //      $status['visibility'] = 'public';
        //      $status['url'] = $gp->url();

        //      if($gp['type'] == 'poll') {
        //          $status['poll'] = PollService::get($status['id']);
        //      }

        //      $status['account']['url'] = url("/groups/{$gp['group_id']}/user/{$status['account']['id']}");

        //      return $status;
        //  })->filter(function($status) {
        //      return $status;
        //  });
        // return $posts;

        Cache::remember('api:v1:timelines:public:cache_check', 10368000, function () use ($id) {
            if (GroupFeedService::count($id) == 0) {
                GroupFeedService::warmCache($id, true, 400);
            }
        });

        if ($max) {
            $feed = GroupFeedService::getRankedMaxId($id, $max, $limit);
        } else {
            $feed = GroupFeedService::get($id, 0, $limit);
        }

        $res = collect($feed)
            ->map(function ($k) use ($user, $id) {
                $status = GroupPostService::get($id, $k);
                if ($status && $user) {
                    $pid = $user->profile_id;
                    $sid = $status['account']['id'];
                    $status['favourited'] = (bool) GroupsLikeService::liked($pid, $status['id']);
                    $status['favourites_count'] = GroupsLikeService::count($status['id']);
                    $status['relationship'] = $pid == $sid ? [] : RelationshipService::get($pid, $sid);
                }

                return $status;
            })
            ->filter(function ($s) use ($filtered) {
                return $s && in_array($s['account']['id'], $filtered) == false;
            })
            ->values()
            ->toArray();

        return $res;
    }
}
