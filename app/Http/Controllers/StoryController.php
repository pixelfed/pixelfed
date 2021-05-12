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
use App\Services\MediaPathService;
use FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;

class StoryController extends Controller
{
	public function apiV1Add(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'file' => function() {
				return [
					'required',
					'mimes:image/jpeg,image/png,video/mp4',
					'max:' . config_cache('pixelfed.max_photo_size'),
				];
			},
		]);

		$user = $request->user();

		if(Story::whereProfileId($user->profile_id)->where('expires_at', '>', now())->count() >= Story::MAX_PER_DAY) {
			abort(400, 'You have reached your limit for new Stories today.');
		}

		$photo = $request->file('file');
		$path = $this->storePhoto($photo, $user);

		$story = new Story();
		$story->duration = 3;
		$story->profile_id = $user->profile_id;
		$story->type = Str::endsWith($photo->getMimeType(), 'mp4') ? 'video' :'photo';
		$story->mime = $photo->getMimeType();
		$story->path = $path;
		$story->local = true;
		$story->size = $photo->getSize();
		$story->save();

		$url = $story->path;

		if($story->type === 'video') {
			$video = FFMpeg::open($path);
			$width = $video->getVideoStream()->get('width');
			$height = $video->getVideoStream()->get('height');


			if($width !== 1080 || $height !== 1920) {
				Storage::delete($story->path);
				$story->delete();
				abort(422, 'Invalid video dimensions, must be 1080x1920');
			}
		}

		return [
			'code' => 200,
			'msg'  => 'Successfully added',
			'media_id' => (string) $story->id,
			'media_url' => url(Storage::url($url)) . '?v=' . time(),
			'media_type' => $story->type
		];
	}

	protected function storePhoto($photo, $user)
	{
		$mimes = explode(',', config_cache('pixelfed.media_types'));
		if(in_array($photo->getMimeType(), [
			'image/jpeg',
			'image/png',
			'video/mp4'
		]) == false) {
			abort(400, 'Invalid media type');
			return;
		}

		$storagePath = MediaPathService::story($user->profile);
		$path = $photo->store($storagePath);
		if(in_array($photo->getMimeType(), ['image/jpeg','image/png'])) {
			$fpath = storage_path('app/' . $path);
			$img = Intervention::make($fpath);
			$img->orientate();
			$img->save($fpath, config_cache('pixelfed.image_quality'));
			$img->destroy();
		}
		return $path;
	}

	public function cropPhoto(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'media_id' => 'required|integer|min:1',
			'width' => 'required',
			'height' => 'required',
			'x' => 'required',
			'y' => 'required'
		]);

		$user = $request->user();
		$id = $request->input('media_id');
		$width = round($request->input('width'));
		$height = round($request->input('height'));
		$x = round($request->input('x'));
		$y = round($request->input('y'));

		$story = Story::whereProfileId($user->profile_id)->findOrFail($id);

		$path = storage_path('app/' . $story->path);

		if(!is_file($path)) {
			abort(400, 'Invalid or missing media.');
		}

		if($story->type === 'photo') {
			$img = Intervention::make($path);
			$img->crop($width, $height, $x, $y);
			$img->resize(1080, 1920, function ($constraint) {
				$constraint->aspectRatio();
			});
			$img->save($path, config_cache('pixelfed.image_quality'));
		}

		return [
			'code' => 200,
			'msg'  => 'Successfully cropped',
		];
	}

	public function publishStory(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$this->validate($request, [
			'media_id' => 'required',
			'duration' => 'required|integer|min:3|max:10'
		]);

		$id = $request->input('media_id');
		$user = $request->user();
		$story = Story::whereProfileId($user->profile_id)
			->findOrFail($id);

		$story->active = true;
		$story->duration = $request->input('duration', 10);
		$story->expires_at = now()->addHours(24);
		$story->save();

		return [
			'code' => 200,
			'msg'  => 'Successfully published',
		];
	}

	public function apiV1Delete(Request $request, $id)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

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
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$profile = $request->user()->profile;
		$following = $profile->following->pluck('id')->toArray();

		if(config('database.default') == 'pgsql') {
			$db = Story::with('profile')
			->whereActive(true)
			->whereIn('profile_id', $following)
			->where('expires_at', '>', now())
			->distinct('profile_id')
			->take(9)
			->get();
		} else {
			$db = Story::with('profile')
			->whereActive(true)
			->whereIn('profile_id', $following)
			->where('created_at', '>', now()->subDay())
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
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$authed = $request->user()->profile;
		$profile = Profile::findOrFail($id);
		if($id == $authed->id) {
			$publicOnly = true;
		} else {
			$publicOnly = (bool) $profile->followedBy($authed);
		}

		$stories = Story::whereProfileId($profile->id)
		->whereActive(true)
		->orderBy('expires_at', 'desc')
		->where('expires_at', '>', now())
		->when(!$publicOnly, function($query, $publicOnly) {
			return $query->wherePublic(true);
		})
		->get()
		->map(function($s, $k) {
			return [
				'id' => (string) $s->id,
				'type' => Str::endsWith($s->path, '.mp4') ? 'video' :'photo',
				'length' => 3,
				'src' => url(Storage::url($s->path)),
				'preview' => null,
				'link' => null,
				'linkText' => null,
				'time' => $s->created_at->format('U'),
				'expires_at' => (int)  $s->expires_at->format('U'),
				'created_ago' => $s->created_at->diffForHumans(null, true, true),
				'seen' => $s->seen()
			];
		})->toArray();
		return response()->json($stories, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function apiV1Item(Request $request, $id)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$authed = $request->user()->profile;
		$story = Story::with('profile')
			->whereActive(true)
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
			'type' => Str::endsWith($story->path, '.mp4') ? 'video' :'photo',
			'length' => 10,
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
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$authed = $request->user()->profile;
		$profile = Profile::findOrFail($id);
		if($id == $authed->id) {
			$publicOnly = true;
		} else {
			$publicOnly = (bool) $profile->followedBy($authed);
		}

		$stories = Story::whereProfileId($profile->id)
		->whereActive(true)
		->orderBy('expires_at')
		->where('expires_at', '>', now())
		->when(!$publicOnly, function($query, $publicOnly) {
			return $query->wherePublic(true);
		})
		->get()
		->map(function($s, $k) {
			return [
				'id' => $s->id,
				'type' => Str::endsWith($s->path, '.mp4') ? 'video' :'photo',
				'length' => 10,
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

	public function apiV1Viewed(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

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
			return [];
		}

		$publicOnly = (bool) $profile->followedBy($authed);
		abort_if(!$publicOnly, 403);

		StoryView::firstOrCreate([
			'story_id' => $id,
			'profile_id' => $authed->id
		]);

		$story->view_count = $story->view_count + 1;
		$story->save();

		return ['code' => 200];
	}

	public function apiV1Exists(Request $request, $id)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$res = (bool) Story::whereProfileId($id)
		->whereActive(true)
		->where('expires_at', '>', now())
		->count();

		return response()->json($res);
	}

	public function apiV1Me(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$profile = $request->user()->profile;
		$stories = Story::whereProfileId($profile->id)
			->whereActive(true)
			->orderBy('expires_at')
			->where('expires_at', '>', now())
			->get()
			->map(function($s, $k) {
				return [
					'id' => $s->id,
					'type' => Str::endsWith($s->path, '.mp4') ? 'video' :'photo',
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
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		return view('stories.compose');
	}

	public function iRedirect(Request $request)
	{
		abort_if(!config_cache('instance.stories.enabled') || !$request->user(), 404);

		$user = $request->user();
		abort_if(!$user, 404);
		$username = $user->username;
		return redirect("/stories/{$username}");
	}
}
