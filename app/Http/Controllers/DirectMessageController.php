<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\DirectMessage;
use App\Profile;
use App\Status;

class DirectMessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function inbox(Request $request)
    {
        $profile = Auth::user()->profile;
        $inbox = DirectMessage::selectRaw('*, max(created_at) as createdAt')
            ->whereToId($profile->id)
            ->with(['author','status'])
            ->orderBy('createdAt', 'desc')
            ->groupBy('from_id')
            ->paginate(12);
        return view('account.messages', compact('inbox'));
    }

    public function show(Request $request, int $pid, $mid)
    {
        $profile = Auth::user()->profile;

        if ($pid !== $profile->id) {
            abort(403);
        }

        $msg = DirectMessage::whereToId($profile->id)
            ->findOrFail($mid);

        $thread = DirectMessage::whereIn('to_id', [$profile->id, $msg->from_id])
            ->whereIn('from_id', [$profile->id,$msg->from_id])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        $thread = $thread->reverse();

        return view('account.message', compact('msg', 'profile', 'thread'));
    }

    public function compose(Request $request)
    {
        $profile = Auth::user()->profile;
    }
}
