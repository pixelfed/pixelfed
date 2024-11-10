<?php

namespace App\Http\Controllers\Groups;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AccountService;
use App\Services\GroupService;
use App\Follower;
use App\Profile;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupInvitation;

class GroupsDiscoverController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getDiscoverPopular(Request $request)
    {
        abort_if(!$request->user(), 404);
        $groups = Group::orderByDesc('member_count')
            ->take(12)
            ->pluck('id')
            ->map(function($id) {
                return GroupService::get($id);
            })
            ->filter(function($id) {
                return $id;
            })
            ->take(6)
            ->values();
        return $groups;
    }

    public function getDiscoverNew(Request $request)
    {
        abort_if(!$request->user(), 404);
        $groups = Group::latest()
            ->take(12)
            ->pluck('id')
            ->map(function($id) {
                return GroupService::get($id);
            })
            ->filter(function($id) {
                return $id;
            })
            ->take(6)
            ->values();
        return $groups;
    }
}
