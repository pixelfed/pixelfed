<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\DirectMessage;
use App\Follower;
use App\Notification;
use App\Media;
use App\Profile;
use App\Status;
use App\Story;
use App\StoryView;
use App\Services\PollService;
use App\Services\ProfileService;
use App\Services\StoryService;
use Cache, Storage;
use Image as Intervention;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\MediaPathService;
use FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;
use League\Fractal\Manager;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Resource\Item;
use App\Transformer\ActivityPub\Verb\StoryVerb;
use App\Jobs\StoryPipeline\StoryViewDeliver;

class StoryController extends StoryComposeController
{
	public function recent(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);
		$pid = $request->user()->profile_id;

		if(config('database.default') == 'pgsql') {
			$s = Story::select('stories.*', 'followers.following_id')
				->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
				->where('followers.profile_id', $pid)
				->where('stories.active', true)
				->get()
				->map(function($s) {
					$r  = new \StdClass;
					$r->id = $s->id;
					$r->profile_id = $s->profile_id;
					$r->type = $s->type;
					$r->path = $s->path;
					return $r;
				})
				->unique('profile_id');
		} else {
			$s = Story::select('stories.*', 'followers.following_id')
				->leftJoin('followers', 'followers.following_id', 'stories.profile_id')
				->where('followers.profile_id', $pid)
				->where('stories.active', true)
				->groupBy('followers.following_id')
				->orderByDesc('id')
				->get();
		}

		$res = $s->map(function($s) use($pid) {
			$profile = AccountService::get($s->profile_id);
			$url = $profile['local'] ? url("/stories/{$profile['username']}") :
				url("/i/rs/{$profile['id']}");
			return [
				'pid' => $profile['id'],
				'avatar' => $profile['avatar'],
				'local' => $profile['local'],
				'username'	=> $profile['acct'],
				'latest' => [
					'id' => $s->id,
					'type' => $s->type,
					'preview_url' => url(Storage::url($s->path))
				],
				'url' => $url,
				'seen' => StoryService::hasSeen($pid, StoryService::latest($s->profile_id)),
				'sid' => $s->id
			];
		})
		->sortBy('seen')
		->values();
		return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function profile(Request $request, $id)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$authed = $request->user()->profile_id;
		$profile = Profile::findOrFail($id);

		if($authed != $profile->id && !FollowerService::follows($authed, $profile->id)) {
			return [];
		}

		$stories = Story::whereProfileId($profile->id)
		->whereActive(true)
		->orderBy('expires_at')
		->get()
		->map(function($s, $k) use($authed) {
			$seen = StoryService::hasSeen($authed, $s->id);
			$res = [
				'id' => (string) $s->id,
				'type' => $s->type,
				'duration' => $s->duration,
				'src' => url(Storage::url($s->path)),
				'created_at' => $s->created_at->toAtomString(),
				'expires_at' => $s->expires_at->toAtomString(),
				'view_count' => ($authed == $s->profile_id) ? ($s->view_count ?? 0) : null,
				'seen' => $seen,
				'progress' => $seen ? 100 : 0,
				'can_reply' => (bool) $s->can_reply,
				'can_react' => (bool) $s->can_react
			];

			if($s->type == 'poll') {
				$res['question'] = json_decode($s->story, true)['question'];
				$res['options'] = json_decode($s->story, true)['options'];
				$res['voted'] = PollService::votedStory($s->id, $authed);
				if($res['voted']) {
					$res['voted_index'] = PollService::storyChoice($s->id, $authed);
				}
			}

			return $res;
		})->toArray();
		if(count($stories) == 0) {
			return [];
		}
		$cursor = count($stories) - 1;
		$stories = [[
			'id' => (string) $stories[$cursor]['id'],
			'nodes' => $stories,
			'account' => AccountService::get($profile->id),
			'pid' => (string) $profile->id
		]];
		return response()->json($stories, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function viewed(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'id'	=> 'required|min:1',
		]);
		$id = $request->input('id');

		$authed = $request->user()->profile;

		$story = Story::with('profile')
			->findOrFail($id);
		$exp = $story->expires_at;

		$profile = $story->profile;

		if($story->profile_id == $authed->id) {
			return [];
		}

		$publicOnly = (bool) $profile->followedBy($authed);
		abort_if(!$publicOnly, 403);


		$v = StoryView::firstOrCreate([
			'story_id' => $id,
			'profile_id' => $authed->id
		]);

		if($v->wasRecentlyCreated) {
			Story::findOrFail($story->id)->increment('view_count');

			if($story->local == false) {
				StoryViewDeliver::dispatch($story, $authed)->onQueue('story');
			}
		}

		Cache::forget('stories:recent:by_id:' . $authed->id);
		StoryService::addSeen($authed->id, $story->id);
		return ['code' => 200];
	}

	public function exists(Request $request, $id)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		return response()->json(Story::whereProfileId($id)
		->whereActive(true)
		->exists());
	}

	public function iRedirect(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$user = $request->user();
		abort_if(!$user, 404);
		$username = $user->username;
		return redirect("/stories/{$username}");
	}

	public function viewers(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'sid' => 'required|string'
		]);

		$pid = $request->user()->profile_id;
		$sid = $request->input('sid');

		$story = Story::whereProfileId($pid)
			->whereActive(true)
			->findOrFail($sid);

		$viewers = StoryView::whereStoryId($story->id)
			->latest()
			->simplePaginate(10)
			->map(function($view) {
				return AccountService::get($view->profile_id);
			})
			->values();

		return response()->json($viewers, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function remoteStory(Request $request, $id)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$profile = Profile::findOrFail($id);
		if($profile->user_id != null || $profile->domain == null) {
			return redirect('/stories/' . $profile->username);
		}
		$pid = $profile->id;
		return view('stories.show_remote', compact('pid'));
	}

	public function pollResults(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'sid' => 'required|string'
		]);

		$pid = $request->user()->profile_id;
		$sid = $request->input('sid');

		$story = Story::whereProfileId($pid)
			->whereActive(true)
			->findOrFail($sid);

		return PollService::storyResults($sid);
	}

	public function getActivityObject(Request $request, $username, $id)
	{
		abort_if(!config_cache('instance.stories.enabled'), 404);

		if(!$request->wantsJson()) {
			return redirect('/stories/' . $username);
		}

		abort_if(!$request->hasHeader('Authorization'), 404);

		$profile = Profile::whereUsername($username)->whereNull('domain')->firstOrFail();
		$story = Story::whereActive(true)->whereProfileId($profile->id)->findOrFail($id);

		abort_if($story->bearcap_token == null, 404);
		abort_if(now()->gt($story->expires_at), 404);
		$token = substr($request->header('Authorization'), 7);
		abort_if(hash_equals($story->bearcap_token, $token) === false, 404);
		abort_if($story->created_at->lt(now()->subMinutes(20)), 404);

		$fractal = new Manager();
		$fractal->setSerializer(new ArraySerializer());
		$resource = new Item($story, new StoryVerb());
		$res = $fractal->createData($resource)->toArray();
		return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function showSystemStory()
	{
		// return view('stories.system');
	}
}
