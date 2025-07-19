<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\Request;

class GroupsMetaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function deleteGroup(Request $request)
    {
        abort_if(! $request->user(), 404);
        $id = $request->input('gid');
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(! $group->isMember($pid), 404);
        abort_if(! in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $group->status = 'delete';
        $group->save();
        GroupService::del($group->id);

        return [200];
    }
}
