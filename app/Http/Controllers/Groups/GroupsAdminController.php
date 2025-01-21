<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\GroupService;
use App\Instance;
use App\Profile;
use App\Models\Group;
use App\Models\GroupBlock;
use App\Models\GroupCategory;
use App\Models\GroupInteraction;
use App\Models\GroupPost;
use App\Models\GroupMember;
use App\Models\GroupReport;
use App\Services\Groups\GroupAccountService;
use App\Services\Groups\GroupPostService;

class GroupsAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getAdminTabs(Request $request, $id)
    {
        abort_if(!$request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(!$group->isMember($pid), 404);
        abort_if(!in_array($group->selfRole($pid), ['founder', 'admin']), 404);
        abort_if($pid !== $group->profile_id, 404);

        $reqs = GroupMember::whereGroupId($group->id)->whereJoinRequest(true)->count();
        $mods = GroupReport::whereGroupId($group->id)->whereOpen(true)->count();
        $tabs = [
            'moderation_count' => $mods > 99 ? '99+' : $mods,
            'request_count' => $reqs > 99 ? '99+' : $reqs
        ];

        return response()->json($tabs);
    }

    public function getInteractionLogs(Request $request, $id)
    {
        abort_if(!$request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(!$group->isMember($pid), 404);
        abort_if(!in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $logs = GroupInteraction::whereGroupId($id)
            ->latest()
            ->paginate(10)
            ->map(function($log) use($group) {
                return [
                    'id' => $log->id,
                    'profile' => GroupAccountService::get($group->id, $log->profile_id),
                    'type' => $log->type,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at->format('c')
                ];
            });

        return response()->json($logs, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function getBlocks(Request $request, $id)
    {
        abort_if(!$request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(!$group->isMember($pid), 404);
        abort_if(!in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $blocks = [
            'instances' => GroupBlock::whereGroupId($group->id)->whereNotNull('instance_id')->whereModerated(false)->latest()->take(3)->pluck('name'),
            'users' => GroupBlock::whereGroupId($group->id)->whereNotNull('profile_id')->whereIsUser(true)->latest()->take(3)->pluck('name'),
            'moderated' => GroupBlock::whereGroupId($group->id)->whereNotNull('instance_id')->whereModerated(true)->latest()->take(3)->pluck('name')
        ];

        return response()->json($blocks, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

    public function exportBlocks(Request $request, $id)
    {
        abort_if(!$request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(!$group->isMember($pid), 404);
        abort_if(!in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $blocks = [
            'instances' => GroupBlock::whereGroupId($group->id)->whereNotNull('instance_id')->whereModerated(false)->latest()->pluck('name'),
            'users' => GroupBlock::whereGroupId($group->id)->whereNotNull('profile_id')->whereIsUser(true)->latest()->pluck('name'),
            'moderated' => GroupBlock::whereGroupId($group->id)->whereNotNull('instance_id')->whereModerated(true)->latest()->pluck('name')
        ];

        $blocks['_created_at'] = now()->format('c');
        $blocks['_version'] = '1.0.0';
        ksort($blocks);

        return response()->streamDownload(function() use($blocks) {
            echo json_encode($blocks, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
        });
    }

    public function addBlock(Request $request, $id)
    {
        abort_if(!$request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(!$group->isMember($pid), 404);
        abort_if(!in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $this->validate($request, [
            'item' => 'required',
            'type' => 'required|in:instance,user,moderate'
        ]);

        $item = $request->input('item');
        $type = $request->input('type');

        switch($type) {
            case 'instance':
                $instance = Instance::whereDomain($item)->first();
                abort_if(!$instance, 422, 'This domain either isn\'nt known or is invalid');
                $gb = new GroupBlock;
                $gb->group_id = $group->id;
                $gb->admin_id = $pid;
                $gb->instance_id = $instance->id;
                $gb->name = $instance->domain;
                $gb->is_user = false;
                $gb->moderated = false;
                $gb->save();

                GroupService::log(
                    $group->id,
                    $pid,
                    'group:admin:block:instance',
                    [
                        'domain' => $instance->domain
                    ],
                    GroupBlock::class,
                    $gb->id
                );

                return [200];
            break;

            case 'user':
                $profile = Profile::whereUsername($item)->first();
                abort_if(!$profile, 422, 'This user either isn\'nt known or is invalid');
                $gb = new GroupBlock;
                $gb->group_id = $group->id;
                $gb->admin_id = $pid;
                $gb->profile_id = $profile->id;
                $gb->name = $profile->username;
                $gb->is_user = true;
                $gb->moderated = false;
                $gb->save();

                GroupService::log(
                    $group->id,
                    $pid,
                    'group:admin:block:user',
                    [
                        'username' => $profile->username,
                        'domain' => $profile->domain
                    ],
                    GroupBlock::class,
                    $gb->id
                );

                return [200];
            break;

            case 'moderate':
                $instance = Instance::whereDomain($item)->first();
                abort_if(!$instance, 422, 'This domain either isn\'nt known or is invalid');
                $gb = new GroupBlock;
                $gb->group_id = $group->id;
                $gb->admin_id = $pid;
                $gb->instance_id = $instance->id;
                $gb->name = $instance->domain;
                $gb->is_user = false;
                $gb->moderated = true;
                $gb->save();

                GroupService::log(
                    $group->id,
                    $pid,
                    'group:admin:moderate:instance',
                    [
                        'domain' => $instance->domain
                    ],
                    GroupBlock::class,
                    $gb->id
                );

                return [200];
            break;

            default:
                return response()->json([], 422, []);
            break;
        }
    }

    public function undoBlock(Request $request, $id)
    {
        abort_if(!$request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(!$group->isMember($pid), 404);
        abort_if(!in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $this->validate($request, [
            'item' => 'required',
            'type' => 'required|in:instance,user,moderate'
        ]);

        $item = $request->input('item');
        $type = $request->input('type');

        switch($type) {
            case 'instance':
                $instance = Instance::whereDomain($item)->first();
                abort_if(!$instance, 422, 'This domain either isn\'nt known or is invalid');

                $gb = GroupBlock::whereGroupId($group->id)
                    ->whereInstanceId($instance->id)
                    ->whereModerated(false)
                    ->first();

                abort_if(!$gb, 422, 'Invalid group block');

                GroupService::log(
                    $group->id,
                    $pid,
                    'group:admin:unblock:instance',
                    [
                        'domain' => $instance->domain
                    ],
                    GroupBlock::class,
                    $gb->id
                );

                $gb->delete();

                return [200];
            break;

            case 'user':
                $profile = Profile::whereUsername($item)->first();
                abort_if(!$profile, 422, 'This user either isn\'nt known or is invalid');

                $gb = GroupBlock::whereGroupId($group->id)
                    ->whereProfileId($profile->id)
                    ->whereIsUser(true)
                    ->first();

                abort_if(!$gb, 422, 'Invalid group block');

                GroupService::log(
                    $group->id,
                    $pid,
                    'group:admin:unblock:user',
                    [
                        'username' => $profile->username,
                        'domain' => $profile->domain
                    ],
                    GroupBlock::class,
                    $gb->id
                );

                $gb->delete();

                return [200];
            break;

            case 'moderate':
                $instance = Instance::whereDomain($item)->first();
                abort_if(!$instance, 422, 'This domain either isn\'nt known or is invalid');

                $gb = GroupBlock::whereGroupId($group->id)
                    ->whereInstanceId($instance->id)
                    ->whereModerated(true)
                    ->first();

                abort_if(!$gb, 422, 'Invalid group block');

                GroupService::log(
                    $group->id,
                    $pid,
                    'group:admin:moderate:instance',
                    [
                        'domain' => $instance->domain
                    ],
                    GroupBlock::class,
                    $gb->id
                );

                $gb->delete();

                return [200];
            break;

            default:
                return response()->json([], 422, []);
            break;
        }
    }

    public function getReportList(Request $request, $id)
    {
        abort_if(!$request->user(), 404);
        $group = Group::findOrFail($id);
        $pid = $request->user()->profile_id;
        abort_if(!$group->isMember($pid), 404);
        abort_if(!in_array($group->selfRole($pid), ['founder', 'admin']), 404);

        $scope = $request->input('scope', 'open');

        $list = GroupReport::selectRaw('id, profile_id, item_type, item_id, type, created_at, count(*) as total')
            ->whereGroupId($group->id)
            ->groupBy('item_id')
            ->when($scope == 'open', function($query, $scope) {
                return $query->whereOpen(true);
            })
            ->latest()
            ->simplePaginate(10)
            ->map(function($report) use($group) {
                $res = [
                    'id' => (string) $report->id,
                    'profile' => GroupAccountService::get($group->id, $report->profile_id),
                    'type' => $report->type,
                    'created_at' => $report->created_at->format('c'),
                    'total_count' => $report->total
                ];

                if($report->item_type === GroupPost::class) {
                    $res['status'] = GroupPostService::get($group->id, $report->item_id);
                }

                return $res;
            });
        return response()->json($list, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

}
