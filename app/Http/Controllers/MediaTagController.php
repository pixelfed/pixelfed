<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MediaTag;
use App\Profile;
use App\UserFilter;
use App\User;
use Illuminate\Support\Str;

class MediaTagController extends Controller
{
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
}
