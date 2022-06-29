<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LiveStream;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\LiveStreamService;
use App\User;
use App\Events\LiveStream\NewChatComment;
use App\Events\LiveStream\DeleteChatComment;
use App\Events\LiveStream\BanUser;
use App\Events\LiveStream\PinChatMessage;
use App\Events\LiveStream\UnpinChatMessage;
use App\Events\LiveStream\StreamStart;
use App\Events\LiveStream\StreamEnd;

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
		$stream->stream_id = Str::random(40) . '_' . $stream->profile_id;
		$stream->stream_key = 'streamkey-' . Str::random(64);
		$stream->save();

		return [
			'host' => $stream->getStreamServer(),
			'key' => $stream->stream_key,
			'url' => $stream->getStreamKeyUrl(),
			'id' => $stream->stream_id
		];
	}

	public function getUserStream(Request $request)
	{
		abort_if(!config('livestreaming.enabled'), 400);
		abort_if(!$request->user(), 403);

		$stream = LiveStream::whereProfileId($request->input('profile_id'))
			->whereNotNull('live_at')
			->orderByDesc('live_at')
			->first();

		if(!$stream) {
			return [];
		}

		$res = [];
		$owner = $request->user() ? $stream->profile_id == $request->user()->profile_id : false;

		if($stream->visibility === 'private') {
			abort_if(!$owner && !FollowerService::follows($request->user()->profile_id, $stream->profile_id), 403, 'LSE:011');
		}

		$res = [
			'hls_url' => $stream->getHlsUrl(),
			'name' => $stream->name,
			'description' => $stream->description
		];

		return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
	}


	public function getUserStreamAsGuest(Request $request)
	{
		abort_if(!config('livestreaming.enabled'), 400);

		$stream = LiveStream::whereProfileId($request->input('profile_id'))
			->whereVisibility('public')
			->whereNotNull('live_at')
			->orderByDesc('live_at')
			->first();

		if(!$stream) {
			return [];
		}

		$res = [];

		$res = [
			'hls_url' => $stream->getHlsUrl(),
			'name' => $stream->name,
			'description' => $stream->description
		];

		return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
	}

	public function showProfilePlayer(Request $request, $username)
	{
		abort_if(!config('livestreaming.enabled'), 400);

		$user = User::whereUsername($username)->firstOrFail();
		$id = (string) $user->profile_id;
		$stream = LiveStream::whereProfileId($id)
			->whereNotNull('live_at')
			->first();

		abort_if(!$request->user() && $stream && $stream->visibility !== 'public', 404);

		return view('live.player', compact('id'));
	}

	public function deleteStream(Request $request)
	{
		abort_if(!config('livestreaming.enabled'), 400);
		abort_if(!$request->user(), 403);

		LiveStream::whereProfileId($request->user()->profile_id)
			->get()
			->each(function($stream) {
				Storage::deleteDirectory("public/live-hls/{$stream->stream_id}");
				LiveStreamService::clearChat($stream->profile_id);
				StreamEnd::dispatch($stream->profile_id);
				$stream->delete();
			});

		return [200];
	}

	public function getActiveStreams(Request $request)
	{
		abort_if(!config('livestreaming.enabled'), 400);
		abort_if(!$request->user(), 403);

		return LiveStream::whereIn('visibility', ['local', 'public'])->whereNotNull('live_at')->get()->map(function($stream) {
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
			->map(function($res) {
				return json_decode($res);
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

		$stream = LiveStream::whereProfileId($request->input('profile_id'))
			->whereNotNull('live_at')
			->firstOrFail();

		$owner = $stream->profile_id == $request->user()->profile_id;
		if($stream->visibility === 'private') {
			abort_if(!$owner && !FollowerService::follows($request->user()->profile_id, $stream->profile_id), 403);
		}

		$user = AccountService::get($request->user()->profile_id);

		abort_if(!$user, 422);

		$res = [
			'id' => (string) Str::uuid(),
			'pid' => (string) $request->user()->profile_id,
			'avatar' => $user['avatar'],
			'username' => $user['username'],
			'text' => $request->input('message'),
			'ts' => now()->timestamp
		];

		LiveStreamService::addComment($stream->profile_id, json_encode($res, JSON_UNESCAPED_SLASHES));
		NewChatComment::dispatch($stream, $res);
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

		$uid = $request->user()->profile_id;
		$pid = $request->input('profile_id');
		$msg = $request->input('message');
		$admin = $uid == $request->input('profile_id');
		$owner = $uid == $msg['pid'];
		abort_if(!$admin && !$owner, 403);

		$stream = LiveStream::whereProfileId($pid)->firstOrFail();

		$payload = $request->input('message');
		DeleteChatComment::dispatch($stream, $payload);
		$payload = json_encode($payload, JSON_UNESCAPED_SLASHES);
		LiveStreamService::deleteComment($stream->profile_id, $payload);
		return;
	}

	public function banChatUser(Request $request)
	{
		abort_if(!config('livestreaming.enabled'), 400);
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'profile_id' => 'required|exists:profiles,id',
		]);

		abort_if($request->user()->profile_id == $request->input('profile_id'), 403);

		$stream = LiveStream::whereProfileId($request->user()->profile_id)->firstOrFail();
		$pid = $request->input('profile_id');

		BanUser::dispatch($stream, $pid);
		return;
	}

	public function pinChatComment(Request $request)
	{
		abort_if(!config('livestreaming.enabled'), 400);
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'profile_id' => 'required|exists:profiles,id',
			'message' => 'required'
		]);

		$uid = $request->user()->profile_id;
		$pid = $request->input('profile_id');
		$msg = $request->input('message');

		abort_if($uid != $pid, 403);

		$stream = LiveStream::whereProfileId($request->user()->profile_id)->firstOrFail();
		PinChatMessage::dispatch($stream, $msg);
		return;
	}

	public function unpinChatComment(Request $request)
	{
		abort_if(!config('livestreaming.enabled'), 400);
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'profile_id' => 'required|exists:profiles,id',
			'message' => 'required'
		]);

		$uid = $request->user()->profile_id;
		$pid = $request->input('profile_id');
		$msg = $request->input('message');

		abort_if($uid != $pid, 403);

		$stream = LiveStream::whereProfileId($request->user()->profile_id)->firstOrFail();
		UnpinChatMessage::dispatch($stream, $msg);
		return;
	}

	public function getConfig(Request $request)
	{
		$res = [
			'enabled' => (bool) config('livestreaming.enabled'),
			'broadcast' => [
				'sources' => config('livestreaming.broadcast.sources'),
				'limits' => config('livestreaming.broadcast.limits')
			],
		];

		return response()->json($res, 200, [], JSON_UNESCAPED_SLASHES);
	}

	public function clientBroadcastPublish(Request $request)
	{
		abort_if(!config('livestreaming.enabled'), 400);
		abort_if($request->ip() != '127.0.0.1', 400);
		$key = $request->input('name');
		$name = $request->input('name');

		abort_if(!$name, 400);

		if(empty($key)) {
			abort_if(!$request->filled('tcurl'), 400);
			$url = $this->parseStreamUrl($request->input('tcurl'));
			$key = $request->filled('name') ? $request->input('name') : $url['name'];
		}

		$token = substr($name, 0, 10) === 'streamkey-';

		if($token) {
			$stream = LiveStream::whereStreamKey($key)->firstOrFail();
			return redirect($stream->getStreamRtmpUrl(), 301);
		} else {
			$stream = LiveStream::whereStreamId($key)->firstOrFail();
		}

		StreamStart::dispatch($stream->profile_id);

		if($request->filled('name') && $token == false) {
			$stream->live_at = now();
			$stream->save();

			return [];
		} else {
			abort(400);
		}

		abort(400);
	}

	public function clientBroadcastFinish(Request $request)
	{
		abort_if(!config('livestreaming.enabled'), 400);
		abort_if($request->ip() != '127.0.0.1', 400);
		$name = $request->input('name');
		$stream = LiveStream::whereStreamId($name)->firstOrFail();
		StreamEnd::dispatch($stream->profile_id);
		LiveStreamService::clearChat($stream->profile_id);

		if(config('livestreaming.broadcast.delete_token_after_finished')) {
			$stream->delete();
		} else {
			$stream->live_at = null;
			$stream->save();
		}

		return [];
	}

	protected function parseStreamUrl($url)
	{
		$name = null;
		$key = null;
		$query = parse_url($url, PHP_URL_QUERY);
		$parts = explode('&', $query);
		foreach($parts as $part) {
			if (!strlen(trim($part))) {
				continue;
			}
			$s = explode('=', $part);
			if(in_array($s[0], ['name', 'key'])) {
				if($s[0] === 'name') {
					$name = $s[1];
				}
				if($s[0] === 'key') {
					$key = $s[1];
				}
			}
		}

		return ['name' => $name, 'key' => $key];
	}
}
