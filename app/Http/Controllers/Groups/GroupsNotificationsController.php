<?php

namespace App\Http\Controllers\Groups;

use App\Http\Controllers\Controller;
use App\Notification;
use App\Services\AccountService;
use App\Services\GroupService;
use App\Services\StatusService;
use Illuminate\Http\Request;

class GroupsNotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function selfGlobalNotifications(Request $request)
    {
        abort_if(! $request->user(), 404);
        $pid = $request->user()->profile_id;

        $res = Notification::whereProfileId($pid)
            ->where('action', 'like', 'group%')
            ->latest()
            ->paginate(10)
            ->map(function ($n) {
                $res = [
                    'id' => $n->id,
                    'type' => $n->action,
                    'account' => AccountService::get($n->actor_id),
                    'object' => [
                        'id' => $n->item_id,
                        'type' => last(explode('\\', $n->item_type)),
                    ],
                    'created_at' => $n->created_at->format('c'),
                ];

                if ($res['object']['type'] == 'Status' || in_array($n->action, ['group:comment'])) {
                    $res['status'] = StatusService::get($n->item_id, false);
                    $res['group'] = GroupService::get($res['status']['gid']);
                }

                if ($res['object']['type'] == 'Group') {
                    $res['group'] = GroupService::get($n->item_id);
                }

                return $res;
            });

        return response()->json($res, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
