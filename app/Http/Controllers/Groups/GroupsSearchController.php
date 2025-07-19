<?php

namespace App\Http\Controllers\Groups;

use App\Follower;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Profile;
use App\Services\AccountService;
use App\Services\Groups\GroupActivityPubService;
use App\Services\GroupService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GroupsSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function inviteFriendsToGroup(Request $request)
    {
        abort_if(! $request->user(), 404);
        $this->validate($request, [
            'uids' => 'required',
            'g' => 'required',
        ]);
        $uid = $request->input('uids');
        $gid = $request->input('g');
        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($gid);
        abort_if(! $group->isMember($pid), 404);
        abort_if(
            GroupInvitation::whereGroupId($group->id)
                ->whereFromProfileId($pid)
                ->count() >= 20,
            422,
            'Invite limit reached'
        );

        $profiles = collect($uid)
            ->map(function ($u) {
                return Profile::find($u);
            })
            ->filter(function ($u) use ($pid) {
                return $u &&
                    $u->id != $pid &&
                    isset($u->id) &&
                    Follower::whereFollowingId($pid)
                        ->whereProfileId($u->id)
                        ->exists();
            })
            ->filter(function ($u) use ($group, $pid) {
                return GroupInvitation::whereGroupId($group->id)
                    ->whereFromProfileId($pid)
                    ->whereToProfileId($u->id)
                    ->exists() == false;
            })
            ->each(function ($u) use ($gid, $pid) {
                $gi = new GroupInvitation;
                $gi->group_id = $gid;
                $gi->from_profile_id = $pid;
                $gi->to_profile_id = $u->id;
                $gi->to_local = true;
                $gi->from_local = $u->domain == null;
                $gi->save();
                // GroupMemberInvite::dispatch($gi);
            });

        return [200];
    }

    public function searchFriendsToInvite(Request $request)
    {
        abort_if(! $request->user(), 404);
        $this->validate($request, [
            'q' => 'required|min:2|max:40',
            'g' => 'required',
            'v' => 'required|in:0.2',
        ]);
        $q = $request->input('q');
        $gid = $request->input('g');
        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($gid);
        abort_if(! $group->isMember($pid), 404);

        $res = Profile::where('username', 'like', "%{$q}%")
            ->whereNull('profiles.domain')
            ->join('followers', 'profiles.id', '=', 'followers.profile_id')
            ->where('followers.following_id', $pid)
            ->take(10)
            ->get()
            ->filter(function ($p) use ($group) {
                return $group->isMember($p->profile_id) == false;
            })
            ->filter(function ($p) use ($group, $pid) {
                return GroupInvitation::whereGroupId($group->id)
                    ->whereFromProfileId($pid)
                    ->whereToProfileId($p->profile_id)
                    ->exists() == false;
            })
            ->map(function ($gm) use ($gid) {
                $a = AccountService::get($gm->profile_id);

                return [
                    'id' => (string) $gm->profile_id,
                    'username' => $a['acct'],
                    'url' => url("/groups/{$gid}/user/{$a['id']}?rf=group_search"),
                ];
            })
            ->values();

        return $res;
    }

    public function searchGlobalResults(Request $request)
    {
        abort_if(! $request->user(), 404);
        $this->validate($request, [
            'q' => 'required|min:2|max:140',
            'v' => 'required|in:0.2',
        ]);
        $q = $request->input('q');

        if (str_starts_with($q, 'https://')) {
            $res = Helpers::getSignedFetch($q);
            if ($res && $res = json_decode($res, true)) {

            }
            if ($res && isset($res['type']) && in_array($res['type'], ['Group', 'Note', 'Page'])) {
                if ($res['type'] === 'Group') {
                    return GroupActivityPubService::fetchGroup($q, true);
                }
                $resp = GroupActivityPubService::fetchGroupPost($q, true);
                $resp['name'] = 'Group Post';
                $resp['url'] = '/groups/'.$resp['group_id'].'/p/'.$resp['id'];

                return [$resp];
            }
        }

        return Group::whereNull('status')
            ->where('name', 'like', '%'.$q.'%')
            ->orderBy('id')
            ->take(10)
            ->pluck('id')
            ->map(function ($group) {
                return GroupService::get($group);
            });
    }

    public function searchLocalAutocomplete(Request $request)
    {
        abort_if(! $request->user(), 404);
        $this->validate($request, [
            'q' => 'required|min:2|max:40',
            'g' => 'required',
            'v' => 'required|in:0.2',
        ]);
        $q = $request->input('q');
        $gid = $request->input('g');
        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($gid);
        abort_if(! $group->isMember($pid), 404);

        $res = GroupMember::whereGroupId($gid)
            ->join('profiles', 'group_members.profile_id', '=', 'profiles.id')
            ->where('profiles.username', 'like', "%{$q}%")
            ->take(10)
            ->get()
            ->map(function ($gm) use ($gid) {
                $a = AccountService::get($gm->profile_id);

                return [
                    'username' => $a['username'],
                    'url' => url("/groups/{$gid}/user/{$a['id']}?rf=group_search"),
                ];
            });

        return $res;
    }

    public function searchAddRecent(Request $request)
    {
        $this->validate($request, [
            'q' => 'required|min:2|max:40',
            'g' => 'required',
        ]);
        $q = $request->input('q');
        $gid = $request->input('g');
        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($gid);
        abort_if(! $group->isMember($pid), 404);

        $key = 'groups:search:recent:'.$gid.':pid:'.$pid;
        $ttl = now()->addDays(14);
        $res = Cache::get($key);
        if (! $res) {
            $val = json_encode([$q]);
        } else {
            $ex = collect(json_decode($res))
                ->prepend($q)
                ->unique('value')
                ->slice(0, 3)
                ->values()
                ->all();
            $val = json_encode($ex);
        }
        Cache::put($key, $val, $ttl);

        return 200;
    }

    public function searchGetRecent(Request $request)
    {
        $gid = $request->input('g');
        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($gid);
        abort_if(! $group->isMember($pid), 404);
        $key = 'groups:search:recent:'.$gid.':pid:'.$pid;

        return Cache::get($key);
    }
}
