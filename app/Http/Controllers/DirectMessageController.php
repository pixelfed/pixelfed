<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\{
	DirectMessage,
	Profile,
	Status
};

class DirectMessageController extends Controller
{
    public function __construct()
    {
    	$this->middleware('auth');
    }

    public function inbox(Request $request)
    {
    	$profile = Auth::user()->profile;
    	$inbox = DirectMessage::whereToId($profile->id)
    		->with(['author','status'])
    		->orderBy('created_at', 'desc')
    		->groupBy('from_id')
    		->paginate(10);
    	return view('account.messages', compact('inbox'));

    }

    public function show(Request $request, int $pid, $mid)
    {
    	$profile = Auth::user()->profile;

    	if($pid !== $profile->id) { 
    		abort(403); 
    	}

    	$msg = DirectMessage::whereToId($profile->id)
    		->findOrFail($mid);

    	$thread = DirectMessage::whereToId($profile->id)
    		->orWhere([['from_id', $profile->id],['to_id', $msg->from_id]])
    		->orderBy('created_at', 'desc')
    		->paginate(10);

    	return view('account.message', compact('msg', 'profile', 'thread'));
    }

    public function compose(Request $request)
    {
        $profile = Auth::user()->profile;
    }
}
