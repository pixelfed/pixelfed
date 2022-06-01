<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LiveStream;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\LiveStreamService;

class LiveStreamController extends Controller
{
    public function createStream(Request $request)
    {
    	abort_if(!config('livestreaming.enabled'), 400);
    	abort_if(!$request->user(), 403);

    	if(config('livestreaming.broadcast.limits.enabled')) {
    		if($request->user()->is_admin) {

    		} else {
    			$limits = config('livestreaming.broadcast.limits');
    			$user = $request->user();
    			abort_if($limits['admins_only'] && $user->is_admin == false, 401, 'LSE:003');
    			if($limits['min_account_age']) {
    				abort_if($user->created_at->gt(now()->subDays($limits['min_account_age'])), 403, 'LSE:005');
    			}

    			if($limits['min_follower_count']) {
    				$account = AccountService::get($user->profile_id);
    				abort_if($account['followers_count'] < $limits['min_follower_count'], 403, 'LSE:008');
    			}
    		}
    	}

    	$this->validate($request, [
    		'name' => 'nullable|string|max:80',
    		'description' => 'nullable|string|max:240',
    		'visibility' => 'required|in:public,private'
    	]);

    	$stream = new LiveStream;
    	$stream->name = $request->input('name');
    	$stream->description = $request->input('description');
    	$stream->visibility = $request->input('visibility');
    	$stream->profile_id = $request->user()->profile_id;
    	$stream->stream_id = Str::random(40);
    	$stream->stream_key = Str::random(64);
    	$stream->save();

    	return [
    		'url' => $stream->getStreamKeyUrl(),
    		'id' => $stream->stream_id
    	];
    }

    public function getUserStream(Request $request)
    {
    	abort_if(!config('livestreaming.enabled'), 400);
    	abort_if(!$request->user(), 403);

    	$stream = LiveStream::whereProfileId($request->input('profile_id'))->first();

    	if(!$stream) {
    		return [];
    	}

    	$res = [];
    	$owner = $stream->profile_id == $request->user()->profile_id;

    	if($stream->visibility === 'private') {
    		abort_if(!$owner && !FollowerService::follows($request->user()->profile_id, $stream->profile_id), 403, 'LSE:011');
    	}

    	if($owner) {
    		$res['stream_key'] = $stream->stream_key;
    		$res['stream_id'] = $stream->stream_id;
    		$res['stream_url'] = $stream->getStreamKeyUrl();
    	}

    	if($stream->live_at == null) {
    		$res['hls_url'] = null;
	    	$res['name'] = $stream->name;
	    	$res['description'] = $stream->description;
	    	return $res;
    	}

    	$res = [
    		'hls_url' => $stream->getHlsUrl(),
    		'name' => $stream->name,
    		'description' => $stream->description
    	];

    	return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function deleteStream(Request $request)
    {
    	abort_if(!config('livestreaming.enabled'), 400);
    	abort_if(!$request->user(), 403);

    	LiveStream::whereProfileId($request->user()->profile_id)
    		->get()
    		->each(function($stream) {
    			Storage::deleteDirectory("public/live-hls/{$stream->stream_id}");
    			$stream->delete();
    		});

    	return [200];
    }

    public function getActiveStreams(Request $request)
    {
    	abort_if(!config('livestreaming.enabled'), 400);
    	abort_if(!$request->user(), 403);

    	return LiveStream::whereVisibility('local')->whereNotNull('live_at')->get()->map(function($stream) {
    		return [
    			'account' => AccountService::get($stream->profile_id),
    			'stream_id' => $stream->stream_id
    		];
    	});
    }

    public function getLatestChat(Request $request)
    {
    	abort_if(!config('livestreaming.enabled'), 400);
    	abort_if(!$request->user(), 403);

    	$stream = LiveStream::whereProfileId($request->input('profile_id'))
    		->whereNotNull('live_at')
    		->first();

    	if(!$stream) {
    		return [];
    	}

    	$owner = $stream->profile_id == $request->user()->profile_id;
    	if($stream->visibility === 'private') {
    		abort_if(!$owner && !FollowerService::follows($request->user()->profile_id, $stream->profile_id), 403, 'LSE:021');
    	}

    	$res = collect(LiveStreamService::getComments($stream->profile_id))
    		->map(function($r) {
    			return json_decode($r);
    		});

    	return $res;
    }

    public function addChatComment(Request $request)
    {
    	abort_if(!config('livestreaming.enabled'), 400);
    	abort_if(!$request->user(), 403);

    	$this->validate($request, [
    		'profile_id' => 'required|exists:profiles,id',
    		'message' => 'required|max:140'
    	]);

    	$stream = LiveStream::whereProfileId($request->input('profile_id'))->firstOrFail();

    	$owner = $stream->profile_id == $request->user()->profile_id;
    	if($stream->visibility === 'private') {
    		abort_if(!$owner && !FollowerService::follows($request->user()->profile_id, $stream->profile_id), 403, 'LSE:022');
    	}

    	$res = [
    		'pid' => (string) $request->user()->profile_id,
    		'username' => $request->user()->username,
    		'text' => $request->input('message'),
    		'ts' => now()->timestamp
    	];

    	LiveStreamService::addComment($stream->profile_id, json_encode($res, JSON_UNESCAPED_SLASHES));

    	return $res;
    }

    public function editStream(Request $request)
    {
    	abort_if(!config('livestreaming.enabled'), 400);
    	abort_if(!$request->user(), 403);

    	$this->validate($request, [
    		'name' => 'nullable|string|max:80',
    		'description' => 'nullable|string|max:240'
    	]);

    	$stream = LiveStream::whereProfileId($request->user()->profile_id)->firstOrFail();
    	$stream->name = $request->input('name');
    	$stream->description = $request->input('description');
    	$stream->save();

    	return;
    }

    public function deleteChatComment(Request $request)
    {
    	abort_if(!config('livestreaming.enabled'), 400);
    	abort_if(!$request->user(), 403);

    	$this->validate($request, [
    		'profile_id' => 'required|exists:profiles,id',
    		'message' => 'required'
    	]);

    	abort_if($request->user()->profile_id != $request->input('profile_id'), 403);

    	$stream = LiveStream::whereProfileId($request->user()->profile_id)->firstOrFail();

    	$payload = $request->input('message');
    	$payload = json_encode($payload, JSON_UNESCAPED_SLASHES);
    	LiveStreamService::deleteComment($stream->profile_id, $payload);

    	return;
    }

    public function getConfig(Request $request)
    {
    	abort_if(!config('livestreaming.enabled'), 400);
    	abort_if(!$request->user(), 403);

    	$res = [
    		'enabled' => config('livestreaming.enabled'),
    		'broadcast' => [
    			'sources' => config('livestreaming.broadcast.sources'),
    			'limits' => config('livestreaming.broadcast.limits')
    		],
    	];

    	return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
    }
}
