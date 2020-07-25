<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MediaTagService;
use App\MediaTag;
use App\Notification;
use App\Profile;
use App\UserFilter;
use App\User;
use Illuminate\Support\Str;

class MediaTagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function usernameLookup(Request $request)
    {
    	abort_if(!$request->user(), 403);

    	$this->validate($request, [
    		'q' => 'required|string|min:1|max:50'
    	]);

    	$q = $request->input('q');

    	if(Str::of($q)->startsWith('@')) {
    		if(strlen($q) < 3) {
    			return [];
    		}
    		$q = mb_substr($q, 1);
    	}

    	$blocked = UserFilter::whereFilterableType('App\Profile')
    		->whereFilterType('block')
    		->whereFilterableId($request->user()->profile_id)
    		->pluck('user_id');

    	$blocked->push($request->user()->profile_id);

    	$results = Profile::select('id','domain','username')
    		->whereNotIn('id', $blocked)
    		->whereNull('domain')
    		->where('username','like','%'.$q.'%')
    		->limit(15)
    		->get()
    		->map(function($r) {
    			return [
    				'id' => (string) $r->id,
    				'name' => $r->username,
    				'privacy' => true,
    				'avatar' => $r->avatarUrl()
    			];
    		});

    	return $results;
    }

    public function untagProfile(Request $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'status_id' => 'required',
            'profile_id' => 'required'
        ]);

        $user = $request->user();
        $status_id = $request->input('status_id');
        $profile_id = (int) $request->input('profile_id');

        abort_if((int) $user->profile_id !== $profile_id, 400);

        $tag = MediaTag::whereStatusId($status_id)
            ->whereProfileId($profile_id)
            ->first();

        if(!$tag) {
            return [];
        }
        Notification::whereItemType('App\MediaTag')
            ->whereItemId($tag->id)
            ->whereProfileId($profile_id)
            ->whereAction('tagged')
            ->delete();

        MediaTagService::untag($status_id, $profile_id);

        return [200];

    }
}
