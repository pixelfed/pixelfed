<?php

namespace App\Http\Controllers;

use App\Bookmark;
use App\Status;
use Auth;
use Illuminate\Http\Request;
use App\Services\BookmarkService;
use App\Services\FollowerService;
use App\Services\UserRoleService;

class BookmarkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'item' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $status = Status::findOrFail($request->input('item'));

        abort_if($user->has_roles && !UserRoleService::can('can-bookmark', $user->id), 403, 'Invalid permissions for this action');
        abort_if($status->in_reply_to_id || $status->reblog_of_id, 404);
        abort_if(!in_array($status->scope, ['public', 'unlisted', 'private']), 404);
        abort_if(!in_array($status->type, ['photo','photo:album', 'video', 'video:album', 'photo:video:album']), 404);

        if($status->scope == 'private') {
            if($user->profile_id !== $status->profile_id && !FollowerService::follows($user->profile_id, $status->profile_id)) {
                if($exists = Bookmark::whereStatusId($status->id)->whereProfileId($user->profile_id)->first()) {
                    BookmarkService::del($user->profile_id, $status->id);
                    $exists->delete();

                    if ($request->ajax()) {
                        return ['code' => 200, 'msg' => 'Bookmark removed!'];
                    } else {
                        return redirect()->back();
                    }
                }
                abort(404, 'Error: You cannot bookmark private posts from accounts you do not follow.');
            }
        }

        $bookmark = Bookmark::firstOrCreate(
            ['status_id' => $status->id], ['profile_id' => $user->profile_id]
        );

        if (!$bookmark->wasRecentlyCreated) {
            BookmarkService::del($user->profile_id, $status->id);
            $bookmark->delete();
        } else {
            BookmarkService::add($user->profile_id, $status->id);
        }

        return $request->expectsJson() ? ['code' => 200, 'msg' => 'Bookmark saved!'] : redirect()->back();
    }
}
