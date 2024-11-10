<?php

namespace App\Http\Controllers;

use App\Instance;
use App\Models\Group;
use App\Models\GroupBlock;
use App\Models\GroupCategory;
use App\Models\GroupInvitation;
use App\Models\GroupLike;
use App\Models\GroupLimit;
use App\Models\GroupMember;
use App\Models\GroupPost;
use App\Models\GroupReport;
use App\Profile;
use App\Services\AccountService;
use App\Services\GroupService;
use App\Services\HashidService;
use App\Services\StatusService;
use App\Status;
use App\User;
use Illuminate\Http\Request;
use Storage;

class GroupController extends GroupFederationController
{
    public function __construct()
    {
        $this->middleware('auth');
        abort_unless(config('groups.enabled'), 404);
    }

    public function index(Request $request)
    {
        abort_if(! $request->user(), 404);

        return view('layouts.spa');
    }

    public function home(Request $request)
    {
        abort_if(! $request->user(), 404);

        return view('layouts.spa');
    }

    public function show(Request $request, $id, $path = false)
    {
        $group = Group::find($id);

        if (! $group || $group->status) {
            return response()->view('groups.unavailable')->setStatusCode(404);
        }

        if ($request->wantsJson()) {
            return $this->showGroupObject($group);
        }

        return view('layouts.spa', compact('id', 'path'));
    }

    public function showStatus(Request $request, $gid, $sid)
    {
        $group = Group::find($gid);
        $pid = optional($request->user())->profile_id ?? false;

        if (! $group || $group->status) {
            return response()->view('groups.unavailable')->setStatusCode(404);
        }

        if ($group->is_private) {
            abort_if(! $request->user(), 404);
            abort_if(! $group->isMember($pid), 404);
        }

        $gp = GroupPost::whereGroupId($gid)
            ->findOrFail($sid);

        return view('layouts.spa', compact('group', 'gp'));
    }

    public function getGroup(Request $request, $id)
    {
        $group = Group::whereNull('status')->findOrFail($id);
        $pid = optional($request->user())->profile_id ?? false;

        $group = $this->toJson($group, $pid);

        return response()->json($group, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function showStatusLikes(Request $request, $id, $sid)
    {
        $group = Group::findOrFail($id);
        $user = $request->user();
        $pid = $user->profile_id;
        abort_if(! $group->isMember($pid), 404);
        $status = GroupPost::whereGroupId($id)->findOrFail($sid);
        $likes = GroupLike::whereStatusId($sid)
            ->cursorPaginate(10)
            ->map(function ($l) use ($group) {
                $account = AccountService::get($l->profile_id);
                $account['url'] = "/groups/{$group->id}/user/{$account['id']}";

                return $account;
            })
            ->filter(function ($l) {
                return $l && isset($l['id']);
            })
            ->values();

        return $likes;
    }

    public function groupSettings(Request $request, $id)
    {
        abort_if(! $request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);
        abort_if(! in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        return view('groups.settings', compact('group'));
    }

    public function joinGroup(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if($group->isMember($pid), 404);

        if (! $request->user()->is_admin) {
            abort_if(GroupService::getRejoinTimeout($group->id, $pid), 422, 'Cannot re-join this group for 24 hours after leaving or cancelling a request to join');
        }

        $member = new GroupMember;
        $member->group_id = $group->id;
        $member->profile_id = $pid;
        $member->role = 'member';
        $member->local_group = true;
        $member->local_profile = true;
        $member->join_request = $group->is_private;
        $member->save();

        GroupService::delSelf($group->id, $pid);
        GroupService::log(
            $group->id,
            $pid,
            'group:joined',
            null,
            GroupMember::class,
            $member->id
        );

        $group = $this->toJson($group, $pid);

        return $group;
    }

    public function updateGroup(Request $request, $id)
    {
        $this->validate($request, [
            'description' => 'nullable|max:500',
            'membership' => 'required|in:all,local,private',
            'avatar' => 'nullable',
            'header' => 'nullable',
            'discoverable' => 'required',
            'activitypub' => 'required',
            'is_nsfw' => 'required',
            'category' => 'required|string|in:'.implode(',', GroupService::categories()),
        ]);

        $pid = $request->user()->profile_id;
        $group = Group::whereProfileId($pid)->findOrFail($id);
        $member = GroupMember::whereGroupId($group->id)->whereProfileId($pid)->firstOrFail();

        abort_if($member->role != 'founder', 403, 'Invalid group permission');

        $metadata = $group->metadata;
        $len = $group->is_private ? 12 : 4;

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');

            if ($avatar) {
                if (isset($metadata['avatar']) &&
                    isset($metadata['avatar']['path']) &&
                    Storage::exists($metadata['avatar']['path'])
                ) {
                    Storage::delete($metadata['avatar']['path']);
                }

                $fileName = 'avatar_'.strtolower(str_random($len)).'.'.$avatar->extension();
                $path = $avatar->storePubliclyAs('public/g/'.$group->id.'/meta', $fileName);
                $url = url(Storage::url($path));
                $metadata['avatar'] = [
                    'path' => $path,
                    'url' => $url,
                    'updated_at' => now(),
                ];
            }
        }

        if ($request->hasFile('header')) {
            $header = $request->file('header');

            if ($header) {
                if (isset($metadata['header']) &&
                    isset($metadata['header']['path']) &&
                    Storage::exists($metadata['header']['path'])
                ) {
                    Storage::delete($metadata['header']['path']);
                }

                $fileName = 'header_'.strtolower(str_random($len)).'.'.$header->extension();
                $path = $header->storePubliclyAs('public/g/'.$group->id.'/meta', $fileName);
                $url = url(Storage::url($path));
                $metadata['header'] = [
                    'path' => $path,
                    'url' => $url,
                    'updated_at' => now(),
                ];
            }
        }

        $cat = GroupService::categoryById($group->category_id);
        if ($request->category !== $cat['name']) {
            $group->category_id = GroupCategory::whereName($request->category)->first()->id;
        }

        $changes = null;
        $group->description = e($request->input('description', null));
        $group->is_private = $request->input('membership') == 'private';
        $group->local_only = $request->input('membership') == 'local';
        $group->activitypub = $request->input('activitypub') == 'true';
        $group->discoverable = $request->input('discoverable') == 'true';
        $group->is_nsfw = $request->input('is_nsfw') == 'true';
        $group->metadata = $metadata;
        if ($group->isDirty()) {
            $changes = $group->getDirty();
        }
        $group->save();

        GroupService::log(
            $group->id,
            $pid,
            'group:settings:updated',
            $changes
        );

        GroupService::del($group->id);

        $res = $this->toJson($group, $pid);

        return $res;
    }

    protected function toJson($group, $pid = false)
    {
        return GroupService::get($group->id, $pid);
    }

    public function groupLeave(Request $request, $id)
    {
        abort_if(! $request->user(), 404);

        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($id);

        abort_if($pid == $group->profile_id, 422, 'Cannot leave a group you created');

        abort_if(! $group->isMember($pid), 403, 'Not a member of group.');

        GroupMember::whereGroupId($group->id)->whereProfileId($pid)->delete();
        GroupService::del($group->id);
        GroupService::delSelf($group->id, $pid);
        GroupService::setRejoinTimeout($group->id, $pid);

        return [200];
    }

    public function cancelJoinRequest(Request $request, $id)
    {
        abort_if(! $request->user(), 404);

        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($id);

        abort_if($pid == $group->profile_id, 422, 'Cannot leave a group you created');
        abort_if($group->isMember($pid), 422, 'Cannot cancel approved join request, please leave group instead.');

        GroupMember::whereGroupId($group->id)->whereProfileId($pid)->delete();
        GroupService::del($group->id);
        GroupService::delSelf($group->id, $pid);
        GroupService::setRejoinTimeout($group->id, $pid);

        return [200];
    }

    public function metaBlockSearch(Request $request, $id)
    {
        abort_if(! $request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);
        abort_if(! in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $type = $request->input('type');
        $item = $request->input('item');

        switch ($type) {
            case 'instance':
                $res = Instance::whereDomain($item)->first();
                if ($res) {
                    abort_if(GroupBlock::whereGroupId($group->id)->whereInstanceId($res->id)->exists(), 400);
                }
                break;

            case 'user':
                $res = Profile::whereUsername($item)->first();
                if ($res) {
                    abort_if(GroupBlock::whereGroupId($group->id)->whereProfileId($res->id)->exists(), 400);
                }
                if ($res->user_id != null) {
                    abort_if(User::whereIsAdmin(true)->whereId($res->user_id)->exists(), 400);
                }
                break;
        }

        return response()->json((bool) $res, ($res ? 200 : 404));
    }

    public function reportCreate(Request $request, $id)
    {
        abort_if(! $request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);

        $id = $request->input('id');
        $type = $request->input('type');
        $types = [
            // original 3
            'spam',
            'sensitive',
            'abusive',

            // new
            'underage',
            'violence',
            'copyright',
            'impersonation',
            'scam',
            'terrorism',
        ];

        $gp = GroupPost::whereGroupId($group->id)->find($id);
        abort_if(! $gp, 422, 'Cannot report an invalid or deleted post');
        abort_if(! in_array($type, $types), 422, 'Invalid report type');
        abort_if($gp->profile_id === $pid, 422, 'Cannot report your own post');
        abort_if(
            GroupReport::whereGroupId($group->id)
                ->whereProfileId($pid)
                ->whereItemType(GroupPost::class)
                ->whereItemId($id)
                ->exists(),
            422,
            'You already reported this'
        );

        $report = new GroupReport();
        $report->group_id = $group->id;
        $report->profile_id = $pid;
        $report->type = $type;
        $report->item_type = GroupPost::class;
        $report->item_id = $id;
        $report->open = true;
        $report->save();

        GroupService::log(
            $group->id,
            $pid,
            'group:report:create',
            [
                'type' => $type,
                'report_id' => $report->id,
                'status_id' => $gp->status_id,
                'profile_id' => $gp->profile_id,
                'username' => optional(AccountService::get($gp->profile_id))['acct'],
                'gpid' => $gp->id,
                'url' => $gp->url(),
            ],
            GroupReport::class,
            $report->id
        );

        return response([200]);
    }

    public function reportAction(Request $request, $id)
    {
        abort_if(! $request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);
        abort_if(! in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $this->validate($request, [
            'action' => 'required|in:cw,delete,ignore',
            'id' => 'required|string',
        ]);

        $action = $request->input('action');
        $id = $request->input('id');

        $report = GroupReport::whereGroupId($group->id)
            ->findOrFail($id);
        $status = Status::findOrFail($report->item_id);
        $gp = GroupPost::whereGroupId($group->id)
            ->whereStatusId($status->id)
            ->firstOrFail();

        switch ($action) {
            case 'cw':
                $status->is_nsfw = true;
                $status->save();
                StatusService::del($status->id);

                GroupReport::whereGroupId($group->id)
                    ->whereItemType($report->item_type)
                    ->whereItemId($report->item_id)
                    ->update(['open' => false]);

                GroupService::log(
                    $group->id,
                    $pid,
                    'group:moderation:action',
                    [
                        'type' => 'cw',
                        'report_id' => $report->id,
                        'status_id' => $status->id,
                        'profile_id' => $status->profile_id,
                        'status_url' => $gp->url(),
                    ],
                    GroupReport::class,
                    $report->id
                );

                return response()->json([200]);
                break;

            case 'ignore':
                GroupReport::whereGroupId($group->id)
                    ->whereItemType($report->item_type)
                    ->whereItemId($report->item_id)
                    ->update(['open' => false]);

                GroupService::log(
                    $group->id,
                    $pid,
                    'group:moderation:action',
                    [
                        'type' => 'ignore',
                        'report_id' => $report->id,
                        'status_id' => $status->id,
                        'profile_id' => $status->profile_id,
                        'status_url' => $gp->url(),
                    ],
                    GroupReport::class,
                    $report->id
                );

                return response()->json([200]);
                break;
        }
    }

    public function getMemberInteractionLimits(Request $request, $id)
    {
        abort_if(! $request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);
        abort_if(! in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $profile_id = $request->input('profile_id');
        abort_if(! $group->isMember($profile_id), 404);
        $limits = GroupService::getInteractionLimits($group->id, $profile_id);

        return response()->json($limits);
    }

    public function updateMemberInteractionLimits(Request $request, $id)
    {
        abort_if(! $request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);
        abort_if(! in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $this->validate($request, [
            'profile_id' => 'required|exists:profiles,id',
            'can_post' => 'required',
            'can_comment' => 'required',
            'can_like' => 'required',
        ]);

        $member = $request->input('profile_id');
        $can_post = $request->input('can_post');
        $can_comment = $request->input('can_comment');
        $can_like = $request->input('can_like');
        $account = AccountService::get($member);

        abort_if(! $account, 422, 'Invalid profile');
        abort_if(! $group->isMember($member), 422, 'Invalid profile');

        $limit = GroupLimit::firstOrCreate([
            'profile_id' => $member,
            'group_id' => $group->id,
        ]);

        if ($limit->wasRecentlyCreated) {
            abort_if(GroupLimit::whereGroupId($group->id)->count() >= 25, 422, 'limit_reached');
        }

        $previousLimits = $limit->limits;

        $limit->limits = [
            'can_post' => $can_post,
            'can_comment' => $can_comment,
            'can_like' => $can_like,
        ];
        $limit->save();

        GroupService::clearInteractionLimits($group->id, $member);

        GroupService::log(
            $group->id,
            $pid,
            'group:member-limits:updated',
            [
                'profile_id' => $account['id'],
                'username' => $account['username'],
                'previousLimits' => $previousLimits,
                'newLimits' => $limit->limits,
            ],
            GroupLimit::class,
            $limit->id
        );

        return $request->all();
    }

    public function showProfile(Request $request, $id, $pid)
    {
        $group = Group::find($id);

        if (! $group || $group->status) {
            return response()->view('groups.unavailable')->setStatusCode(404);
        }

        return view('layouts.spa');
    }

    public function showProfileByUsername(Request $request, $id, $pid)
    {
        abort_if(! $request->user(), 404);
        if (! $request->user()) {
            return redirect("/{$pid}");
        }

        $group = Group::find($id);
        $cid = $request->user()->profile_id;

        if (! $group || $group->status) {
            return response()->view('groups.unavailable')->setStatusCode(404);
        }

        if (! $group->isMember($cid)) {
            return redirect("/{$pid}");
        }

        $profile = Profile::whereUsername($pid)->first();

        if (! $group->isMember($profile->id)) {
            return redirect("/{$pid}");
        }

        if ($profile) {
            $url = url("/groups/{$id}/user/{$profile->id}");

            return redirect($url);
        }

        abort(404, 'Invalid username');
    }

    public function groupInviteLanding(Request $request, $id)
    {
        abort(404, 'Not yet implemented');
        $group = Group::findOrFail($id);

        return view('groups.invite', compact('group'));
    }

    public function groupShortLinkRedirect(Request $request, $hid)
    {
        $gid = HashidService::decode($hid);
        $group = Group::findOrFail($gid);

        return redirect($group->url());
    }

    public function groupInviteClaim(Request $request, $id)
    {
        $group = GroupService::get($id);
        abort_if(! $group || empty($group), 404);

        return view('groups.invite-claim', compact('group'));
    }

    public function groupMemberInviteCheck(Request $request, $id)
    {
        abort_if(! $request->user(), 404);
        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($id);
        abort_if($group->isMember($pid), 422, 'Already a member');

        $exists = GroupInvitation::whereGroupId($id)->whereToProfileId($pid)->exists();

        return response()->json([
            'gid' => $id,
            'can_join' => (bool) $exists,
        ]);
    }

    public function groupMemberInviteAccept(Request $request, $id)
    {
        abort_if(! $request->user(), 404);
        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($id);
        abort_if($group->isMember($pid), 422, 'Already a member');

        abort_if(! GroupInvitation::whereGroupId($id)->whereToProfileId($pid)->exists(), 422);

        $gm = new GroupMember;
        $gm->group_id = $id;
        $gm->profile_id = $pid;
        $gm->role = 'member';
        $gm->local_group = $group->local;
        $gm->local_profile = true;
        $gm->join_request = false;
        $gm->save();

        GroupInvitation::whereGroupId($id)->whereToProfileId($pid)->delete();
        GroupService::del($id);
        GroupService::delSelf($id, $pid);

        return ['next_url' => $group->url()];
    }

    public function groupMemberInviteDecline(Request $request, $id)
    {
        abort_if(! $request->user(), 404);
        $pid = $request->user()->profile_id;
        $group = Group::findOrFail($id);
        abort_if($group->isMember($pid), 422, 'Already a member');

        return ['next_url' => '/'];
    }
}
