<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\GroupService;
use App\Models\Group;
use App\Models\GroupMember;

class CreateGroupsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function checkCreatePermission(Request $request)
    {
        abort_if(!$request->user(), 404);
        $pid = $request->user()->profile_id;
        $config = GroupService::config();
        if($request->user()->is_admin) {
            $allowed = true;
        } else {
            $max = $config['limits']['user']['create']['max'];
            $allowed = Group::whereProfileId($pid)->count() <= $max;
        }

        return ['permission' => (bool) $allowed];
    }

    public function storeGroup(Request $request)
    {
        abort_if(!$request->user(), 404);

        $this->validate($request, [
            'name' => 'required',
            'description' => 'nullable|max:500',
            'membership' => 'required|in:public,private,local'
        ]);

        $pid = $request->user()->profile_id;

        $config = GroupService::config();
        abort_if($config['limits']['user']['create']['new'] == false && $request->user()->is_admin == false, 422, 'Invalid operation');
        $max = $config['limits']['user']['create']['max'];
        // abort_if(Group::whereProfileId($pid)->count() <= $max, 422, 'Group limit reached');

        $group = new Group;
        $group->profile_id = $pid;
        $group->name = $request->input('name');
        $group->description = $request->input('description', null);
        $group->is_private = $request->input('membership') == 'private';
        $group->local_only = $request->input('membership') == 'local';
        $group->metadata = $request->input('configuration');
        $group->save();

        GroupService::log($group->id, $pid, 'group:created');

        $member = new GroupMember;
        $member->group_id = $group->id;
        $member->profile_id = $pid;
        $member->role = 'founder';
        $member->local_group = true;
        $member->local_profile = true;
        $member->save();

        GroupService::log(
            $group->id,
            $pid,
            'group:joined',
            null,
            GroupMember::class,
            $member->id
        );

        return [
            'id' => $group->id,
            'url' => $group->url()
        ];
    }
}
