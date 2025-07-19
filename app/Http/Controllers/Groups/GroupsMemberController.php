<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Jobs\GroupsPipeline\MemberJoinApprovedPipeline;
use App\Jobs\GroupsPipeline\MemberJoinRejectedPipeline;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupPostHashtag;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\Groups\GroupAccountService;
use App\Services\Groups\GroupHashtagService;
use App\Services\GroupService;
use Illuminate\Http\Request;

class GroupsMemberController extends Controller
{
    public function getGroupMembers(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required',
            'limit' => 'nullable|integer|min:3|max:10',
        ]);

        abort_if(! $request->user(), 404);

        $pid = $request->user()->profile_id;
        $gid = $request->input('gid');
        $group = Group::findOrFail($gid);

        abort_if(! $group->isMember($pid), 403, 'Not a member of group.');

        $members = GroupMember::whereGroupId($gid)
            ->whereJoinRequest(false)
            ->simplePaginate(10)
            ->map(function ($member) use ($pid) {
                $account = AccountService::get($member['profile_id']);
                $account['role'] = $member['role'];
                $account['joined'] = $member['created_at'];
                $account['following'] = $pid != $member['profile_id'] ?
                    FollowerService::follows($pid, $member['profile_id']) :
                    null;
                $account['url'] = url("/groups/{$member->group_id}/user/{$member['profile_id']}");

                return $account;
            });

        return response()->json($members->toArray(), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getGroupMemberJoinRequests(Request $request)
    {
        abort_if(! $request->user(), 404);
        $id = $request->input('gid');
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);
        abort_if(! in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        return GroupMember::whereGroupId($group->id)
            ->whereJoinRequest(true)
            ->whereNull('rejected_at')
            ->paginate(10)
            ->map(function ($member) {
                return AccountService::get($member->profile_id);
            });
    }

    public function handleGroupMemberJoinRequest(Request $request)
    {
        abort_if(! $request->user(), 404);
        $id = $request->input('gid');
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);
        abort_if(! in_array($group->selfRole($pid), ['founder', 'admin']), 404);
        $mid = $request->input('pid');
        abort_if($group->isMember($mid), 404);

        $this->validate($request, [
            'gid' => 'required',
            'pid' => 'required',
            'action' => 'required|in:approve,reject',
        ]);

        $action = $request->input('action');

        $member = GroupMember::whereGroupId($group->id)
            ->whereProfileId($mid)
            ->firstOrFail();

        if ($action == 'approve') {
            MemberJoinApprovedPipeline::dispatch($member)->onQueue('groups');
        } elseif ($action == 'reject') {
            MemberJoinRejectedPipeline::dispatch($member)->onQueue('groups');
        }

        return $request->all();
    }

    public function getGroupMember(Request $request)
    {
        $this->validate($request, [
            'gid' => 'required',
            'pid' => 'required',
        ]);

        abort_if(! $request->user(), 404);
        $gid = $request->input('gid');
        $group = Group::findOrFail($gid);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);
        abort_if(! in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $member_id = $request->input('pid');
        $member = GroupMember::whereGroupId($gid)
            ->whereProfileId($member_id)
            ->firstOrFail();

        $account = GroupAccountService::get($group->id, $member['profile_id']);
        $account['role'] = $member['role'];
        $account['joined'] = $member['created_at'];
        $account['following'] = $pid != $member['profile_id'] ?
            FollowerService::follows($pid, $member['profile_id']) :
            null;
        $account['url'] = url("/groups/{$gid}/user/{$member_id}");

        return response()->json($account, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getGroupMemberCommonIntersections(Request $request)
    {
        abort_if(! $request->user(), 404);
        $cid = $request->user()->profile_id;

        // $this->validate($request, [
        //  'gid' => 'required',
        //  'pid' => 'required'
        // ]);

        $gid = $request->input('gid');
        $pid = $request->input('pid');

        if ($pid === $cid) {
            return [];
        }

        $group = Group::findOrFail($gid);
        abort_if(! $group->isMember($cid), 404);
        abort_if(! $group->isMember($pid), 404);

        $self = GroupPostHashtag::selectRaw('group_post_hashtags.*, count(*) as countr')
            ->whereProfileId($cid)
            ->groupBy('hashtag_id')
            ->orderByDesc('countr')
            ->take(20)
            ->pluck('hashtag_id');
        $user = GroupPostHashtag::selectRaw('group_post_hashtags.*, count(*) as countr')
            ->whereProfileId($pid)
            ->groupBy('hashtag_id')
            ->orderByDesc('countr')
            ->take(20)
            ->pluck('hashtag_id');

        $topics = $self->intersect($user)
            ->values()
            ->shuffle()
            ->take(3)
            ->map(function ($id) use ($group) {
                $tag = GroupHashtagService::get($id);
                $tag['url'] = url("/groups/{$group->id}/topics/{$tag['slug']}?src=upt");

                return $tag;
            });

        // $friends = DB::table('followers as u')
        //  ->join('followers as s', 'u.following_id', '=', 's.following_id')
        //  ->where('s.profile_id', $cid)
        //  ->where('u.profile_id', $pid)
        //  ->inRandomOrder()
        //  ->take(10)
        //  ->pluck('s.following_id')
        //  ->map(function($id) use($gid) {
        //      $res = AccountService::get($id);
        //      $res['url'] = url("/groups/{$gid}/user/{$id}");
        //      return $res;
        //  });
        $mutualGroups = GroupService::mutualGroups($cid, $pid, [$gid]);

        $mutualFriends = collect(FollowerService::mutualIds($cid, $pid))
            ->map(function ($id) use ($gid) {
                $res = AccountService::get($id);
                if (GroupService::isMember($gid, $id)) {
                    $res['url'] = url("/groups/{$gid}/user/{$id}");
                } elseif (! $res['local']) {
                    $res['url'] = url("/i/web/profile/_/{$id}");
                }

                return $res;
            });
        $mutualFriendsCount = FollowerService::mutualCount($cid, $pid);

        $res = [
            'groups_count' => $mutualGroups['count'],
            'groups' => $mutualGroups['groups'],
            'topics' => $topics,
            'friends_count' => $mutualFriendsCount,
            'friends' => $mutualFriends,
        ];

        return response()->json($res, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
