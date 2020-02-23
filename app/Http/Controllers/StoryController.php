<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Media;
use App\Profile;
use App\Story;
use App\StoryView;
use App\Services\StoryService;
use Cache, Storage;
use Image as Intervention;
use App\Services\FollowerService;


class StoryController extends Controller
{
	public function apiV1Add(Request $request): array
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'file' => function() {
				return [
					'required',
					'mimes:image/jpeg,image/png',
					'max:' . config('pixelfed.max_photo_size'),
				];
			},
		]);

		$user = $request->user();

		if(Story::whereProfileId($user->profile_id)->where('expires_at', '>', now())->count() >= Story::MAX_PER_DAY) {
			abort(400, 'You have reached your limit for new Stories today.');
		}

		$photo = $request->file('file');
		$path = $this->storePhoto($photo);

		$story = new Story();
		$story->duration = 3;
		$story->profile_id = $user->profile_id;
		$story->type = 'photo';
		$story->mime = $photo->getMimeType();
		$story->path = $path;
		$story->local = true;
		$story->size = $photo->getClientSize();
		$story->expires_at = now()->addHours(24);
		$story->save();

		return [
			'code' => 200,
			'msg'  => 'Successfully added',
			'media_url' => url(Storage::url($story->path))
		];
	}

	protected function storePhoto($photo)
	{
		$monthHash = substr(hash('sha1', date('Y').date('m')), 0, 12);
		$sid = (string) Str::uuid();
		$rid = Str::random(9).'.'.Str::random(9);
		$mimes = explode(',', config('pixelfed.media_types'));
		if(in_array($photo->getMimeType(), [
			'image/jpeg',
			'image/png'
		]) == false) {
			abort(400, 'Invalid media type');
			return;
		}

		$storagePath = "public/_esm.t2/{$monthHash}/{$sid}/{$rid}";
		$path = $photo->store($storagePath);
		$fpath = storage_path('app/' . $path);
		$img = Intervention::make($fpath);
		$img->orientate();
		$img->save($fpath, config('pixelfed.image_quality'));
		$img->destroy();
		return $path;
	}

	public function apiV1Delete(Request $request, $id): array
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$user = $request->user();

		$story = Story::whereProfileId($user->profile_id)
		->findOrFail($id);

		if(Storage::exists($story->path) == true) {
			Storage::delete($story->path);
		}

		$story->delete();

		return [
			'code' => 200,
			'msg'  => 'Successfully deleted'
		];
	}

	public function apiV1Recent(Request $request)
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$profile = $request->user()->profile;
		$following = $profile->following->pluck('id')->toArray();

		if(config('database.default') == 'pgsql') {
			$db = Story::with('profile')
			->whereIn('profile_id', $following)
			->where('expires_at', '>', now())
			->distinct('profile_id')
			->take(9)
			->get();
		} else {
			$db = Story::with('profile')
			->whereIn('profile_id', $following)
			->where('expires_at', '>', now())
			->orderByDesc('expires_at')
			->groupBy('profile_id')
			->take(9)
			->get();
		}

		$stories = $db->map(function($s, $k) {
			return [
				'id' => (string) $s->id,
				'photo' => $s->profile->avatarUrl(),
				'name'	=> $s->profile->username,
				'link'	=> $s->profile->url(),
				'lastUpdated' => (int) $s->created_at->format('U'),
				'seen' => $s->seen(),
				'items' => [],
				'pid' => (string) $s->profile->id
			];
		});

		return response()->json($stories, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function apiV1Fetch(Request $request, $id)
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$authed = $request->user()->profile;
		$profile = Profile::findOrFail($id);
		if($id == $authed->id) {
			$publicOnly = true;
		} else {
			$publicOnly = (bool) $profile->followedBy($authed);
		}

		$stories = Story::whereProfileId($profile->id)
		->orderBy('expires_at', 'desc')
		->where('expires_at', '>', now())
		->when(!$publicOnly, function($query, $publicOnly) {
			return $query->wherePublic(true);
		})
		->get()
		->map(function($s, $k) {
			return [
				'id' => (string) $s->id,
				'type' => 'photo',
				'length' => 3,
				'src' => url(Storage::url($s->path)),
				'preview' => null,
				'link' => null,
				'linkText' => null,
				'time' => $s->created_at->format('U'),
				'expires_at' => (int)  $s->expires_at->format('U'),
				'seen' => $s->seen()
			];
		})->toArray();
		return response()->json($stories, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function apiV1Item(Request $request, $id)
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$authed = $request->user()->profile;
		$story = Story::with('profile')
			->where('expires_at', '>', now())
			->findOrFail($id);

		$profile = $story->profile;
		if($story->profile_id == $authed->id) {
			$publicOnly = true;
		} else {
			$publicOnly = (bool) $profile->followedBy($authed);
		}

		abort_if(!$publicOnly, 403);
		
		$res = [
			'id' => (string) $story->id,
			'type' => 'photo',
			'length' => 3,
			'src' => url(Storage::url($story->path)),
			'preview' => null,
			'link' => null,
			'linkText' => null,
			'time' => $story->created_at->format('U'),
			'expires_at' => (int)  $story->expires_at->format('U'),
			'seen' => $story->seen()
		];
		return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function apiV1Profile(Request $request, $id)
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$authed = $request->user()->profile;
		$profile = Profile::findOrFail($id);
		if($id == $authed->id) {
			$publicOnly = true;
		} else {
			$publicOnly = (bool) $profile->followedBy($authed);
		}

		$stories = Story::whereProfileId($profile->id)
		->orderBy('expires_at')
		->where('expires_at', '>', now())
		->when(!$publicOnly, function($query, $publicOnly) {
			return $query->wherePublic(true);
		})
		->get()
		->map(function($s, $k) {
			return [
				'id' => $s->id,
				'type' => 'photo',
				'length' => 3,
				'src' => url(Storage::url($s->path)),
				'preview' => null,
				'link' => null,
				'linkText' => null,
				'time' => $s->created_at->format('U'),
				'expires_at' => (int) $s->expires_at->format('U'),
				'seen' => $s->seen()
			];
		})->toArray();
		if(count($stories) == 0) {
			return [];
		}
		$cursor = count($stories) - 1;
		$stories = [[
			'id' => (string) $stories[$cursor]['id'],
			'photo' => $profile->avatarUrl(),
			'name'	=> $profile->username,
			'link'	=> $profile->url(),
			'lastUpdated' => (int) now()->format('U'),
			'seen' => null,
			'items' => $stories,
			'pid' => (string) $profile->id
		]];
		return response()->json($stories, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function apiV1Viewed(Request $request): array
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'id'	=> 'required|integer|min:1|exists:stories',
		]);
		$id = $request->input('id');
		$authed = $request->user()->profile;
		$story = Story::with('profile')
			->where('expires_at', '>', now())
			->orderByDesc('expires_at')
			->findOrFail($id);

		$profile = $story->profile;
		if($story->profile_id == $authed->id) {
			$publicOnly = true;
		} else {
			$publicOnly = (bool) $profile->followedBy($authed);
		}

		abort_if(!$publicOnly, 403);

		StoryView::firstOrCreate([
			'story_id' => $id,
			'profile_id' => $authed->id
		]);

		return ['code' => 200];
	}

	public function apiV1Exists(Request $request, $id)
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$res = (bool) Story::whereProfileId($id)
		->where('expires_at', '>', now())
		->count();

		return response()->json($res);
	}

	public function apiV1Me(Request $request)
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$profile = $request->user()->profile;
		$stories = Story::whereProfileId($profile->id)
			->orderBy('expires_at')
			->where('expires_at', '>', now())
			->get()
			->map(function($s, $k) {
				return [
					'id' => $s->id,
					'type' => 'photo',
					'length' => 3,
					'src' => url(Storage::url($s->path)),
					'preview' => null,
					'link' => null,
					'linkText' => null,
					'time' => $s->created_at->format('U'),
					'expires_at' => (int) $s->expires_at->format('U'),
					'seen' => true
				];
		})->toArray();
		$ts = count($stories) ? last($stories)['time'] : null;
		$res = [
			'id' => (string) $profile->id,
			'photo' => $profile->avatarUrl(),
			'name' => $profile->username,
			'link' => $profile->url(),
			'lastUpdated' => $ts,
			'seen' => true,
			'items' => $stories
		];

		return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function compose(Request $request)
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);
		
		return view('stories.compose');
	}

	public function iRedirect(Request $request)
	{
		abort_if(!config('instance.stories.enabled') || !$request->user(), 404);

		$user = $request->user();
		abort_if(!$user, 404);
		$username = $user->username;
		return redirect("/stories/{$username}");
	}
}
