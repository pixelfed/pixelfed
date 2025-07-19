<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\Request;

class GroupsDiscoverController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getDiscoverPopular(Request $request)
    {
        abort_if(! $request->user(), 404);
        $groups = Group::orderByDesc('member_count')
            ->take(12)
            ->pluck('id')
            ->map(function ($id) {
                return GroupService::get($id);
            })
            ->filter(function ($id) {
                return $id;
            })
            ->take(6)
            ->values();

        return $groups;
    }

    public function getDiscoverNew(Request $request)
    {
        abort_if(! $request->user(), 404);
        $groups = Group::latest()
            ->take(12)
            ->pluck('id')
            ->map(function ($id) {
                return GroupService::get($id);
            })
            ->filter(function ($id) {
                return $id;
            })
            ->take(6)
            ->values();

        return $groups;
    }
}
