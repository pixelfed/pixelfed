<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupHashtag;
use App\Models\GroupPost;
use App\Models\GroupPostHashtag;
use App\Services\Groups\GroupPostService;
use App\Services\Groups\GroupsLikeService;
use Illuminate\Http\Request;

class GroupsTopicController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function groupTopics(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required',
        ]);

        abort_if(! $request->user(), 404);

        $pid = $request->user()->profile_id;
        $gid = $request->input('gid');
        $group = Group::findOrFail($gid);

        abort_if(! $group->isMember($pid), 403, 'Not a member of group.');

        $posts = GroupPostHashtag::join('group_hashtags', 'group_hashtags.id', '=', 'group_post_hashtags.hashtag_id')
            ->selectRaw('group_hashtags.*, group_post_hashtags.*, count(group_post_hashtags.hashtag_id) as ht_count')
            ->where('group_post_hashtags.group_id', $gid)
            ->orderByDesc('ht_count')
            ->limit(10)
            ->pluck('group_post_hashtags.hashtag_id', 'ht_count')
            ->map(function ($id, $key) use ($gid) {
                $tag = GroupHashtag::find($id);

                return [
                    'hid' => $id,
                    'name' => $tag->name,
                    'url' => url("/groups/{$gid}/topics/{$tag->slug}"),
                    'count' => $key,
                ];
            })->values();

        return response()->json($posts, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function groupTopicTag(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required',
            'name' => 'required',
        ]);

        abort_if(! $request->user(), 404);

        $pid = $request->user()->profile_id;
        $gid = $request->input('gid');
        $limit = $request->input('limit', 3);
        $group = Group::findOrFail($gid);

        abort_if(! $group->isMember($pid), 403, 'Not a member of group.');

        $name = $request->input('name');
        $hashtag = GroupHashtag::whereName($name)->first();

        if (! $hashtag) {
            return [];
        }

        // $posts = GroupPost::whereGroupId($gid)
        //  ->select('status_hashtags.*', 'group_posts.*')
        //  ->where('status_hashtags.hashtag_id', $hashtag->id)
        //  ->join('status_hashtags', 'group_posts.status_id', '=', 'status_hashtags.status_id')
        //  ->orderByDesc('group_posts.status_id')
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
        //      return $status;
        //  });

        $posts = GroupPostHashtag::whereGroupId($gid)
            ->whereHashtagId($hashtag->id)
            ->orderByDesc('id')
            ->simplePaginate($limit)
            ->map(function ($gp) use ($pid) {
                $status = GroupPostService::get($gp['group_id'], $gp['status_id']);
                if (! $status) {
                    return false;
                }
                $status['favourited'] = (bool) GroupsLikeService::liked($pid, $gp['status_id']);
                $status['favourites_count'] = GroupsLikeService::count($gp['status_id']);
                $status['pf_type'] = $status['pf_type'];
                $status['visibility'] = 'public';

                return $status;
            });

        return response()->json($posts, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function showTopicFeed(Request $request, $gid, $tag)
    {
        abort_if(! $request->user(), 404);

        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($gid);
        $gid = $group->id;
        abort_if(! $group->isMember($pid), 403, 'Not a member of group.');

        return view('groups.topic-feed', compact('gid', 'tag'));
    }
}
