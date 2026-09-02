<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupCategory;
use App\Models\GroupMember;
use App\Services\Groups\GroupAccountService;
use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
class GroupsApiController extends Controller
{
    protected function toJson($group, $pid = false)
    {
        return GroupService::get($group->id, $pid);
    }

    public function getConfig(Request $request)
    {
        return GroupService::config();
    }

    public function getGroupAccount(Request $request, $gid, $pid): JsonResponse
    {
        $res = GroupAccountService::get($gid, $pid);

        return response()->json($res);
    }

    public function getGroupCategories(Request $request): JsonResponse
    {
        $res = GroupService::categories();

        return response()->json($res, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getGroupsByCategory(Request $request)
    {
        $name = $request->input('name');
        $category = GroupCategory::whereName($name)->firstOrFail();
        $groups = Group::whereCategoryId($category->id)
            ->simplePaginate(6)
            ->map(function ($group) {
                return GroupService::get($group->id);
            })
            ->filter(function ($group) {
                return $group;
            })
            ->values();

        return $groups;
    }

    public function getRecommendedGroups(Request $request): array
    {
        return [];
    }

    public function getSelfGroups(Request $request): JsonResponse
    {
        $selfOnly = $request->input('self') == true;
        $memberOnly = $request->input('member') == true;
        $pid = $request->user()->profile_id;
        $res = GroupMember::whereProfileId($request->user()->profile_id)
            ->when($selfOnly, function ($q, $selfOnly) {
                return $q->whereRole('founder');
            })
            ->when($memberOnly, function ($q, $memberOnly) {
                return $q->whereRole('member');
            })
            ->simplePaginate(4)
            ->map(function ($member) use ($pid) {
                $group = $member->group;

                return $this->toJson($group, $pid);
            });

        return response()->json($res);
    }
}
