<?php

namespace App\Http\Controllers\Admin;

use App\Models\Group;
use App\Models\GroupCategory;
use App\Models\GroupInteraction;
use App\Models\GroupMember;
use App\Models\GroupPost;
use App\Models\GroupReport;
use Cache;
use Illuminate\Http\Request;

trait AdminGroupsController
{
    public function groupsHome(Request $request)
    {
        $stats = $this->groupAdminStats();

        return view('admin.groups.home', compact('stats'));
    }

    protected function groupAdminStats()
    {
        return Cache::remember('admin:groups:stats', 3, function () {
            $res = [
                'total' => Group::count(),
                'local' => Group::whereLocal(true)->count(),
            ];

            $res['remote'] = $res['total'] - $res['local'];
            $res['categories'] = GroupCategory::count();
            $res['posts'] = GroupPost::count();
            $res['members'] = GroupMember::count();
            $res['interactions'] = GroupInteraction::count();
            $res['reports'] = GroupReport::count();

            $res['local_30d'] = Cache::remember('admin:groups:stats:local_30d', 43200, function () {
                return Group::whereLocal(true)->where('created_at', '>', now()->subMonth())->count();
            });

            $res['remote_30d'] = Cache::remember('admin:groups:stats:remote_30d', 43200, function () {
                return Group::whereLocal(false)->where('created_at', '>', now()->subMonth())->count();
            });

            return $res;
        });
    }
}
