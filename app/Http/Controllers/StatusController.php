<?php

namespace App\Http\Controllers;

use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Jobs\SharePipeline\SharePipeline;
use App\Jobs\SharePipeline\UndoSharePipeline;
use App\AccountInterstitial;
use App\Media;
use App\Profile;
use App\Status;
use App\StatusArchived;
use App\StatusView;
use App\Transformer\ActivityPub\StatusTransformer;
use App\Transformer\ActivityPub\Verb\Note;
use App\Transformer\ActivityPub\Verb\Question;
use App\User;
use Auth, DB, Cache;
use Illuminate\Http\Request;
use League\Fractal;
use App\Util\Media\Filter;
use Illuminate\Support\Str;
use App\Services\HashidService;
use App\Services\StatusService;
use App\Util\Media\License;
use App\Services\ReblogService;

class StatusController extends Controller
{
	public function show(Request $request, $username, $id)
	{
		// redirect authed users to Metro 2.0
		if($request->user()) {
			// unless they force static view
			if(!$request->has('fs') || $request->input('fs') != '1') {
				return redirect('/i/web/post/' . $id);
			}
		}

		$user = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();

		if($user->status != null) {
			return ProfileController::accountCheck($user);
		}

		$status = Status::whereProfileId($user->id)
				->whereNull('reblog_of_id')
				->whereIn('scope', ['public','unlisted', 'private'])
				->findOrFail($id);

		if($status->uri || $status->url) {
			$url = $status->uri ?? $status->url;
			if(ends_with($url, '/activity')) {
				$url = str_replace('/activity', '', $url);
			}
			return redirect($url);
		}

		if($status->visibility == 'private' || $user->is_private) {
			if(!Auth::check()) {
				abort(404);
			}
			$pid = Auth::user()->profile;
			if($user->followedBy($pid) == false && $user->id !== $pid->id && Auth::user()->is_admin == false) {
				abort(404);
			}
		}

		if($status->type == 'archived') {
			if(Auth::user()->profile_id !== $status->profile_id) {
				abort(404);
			}
		}

		if($request->user() && $request->user()->profile_id != $status->profile_id) {
			StatusView::firstOrCreate([
				'status_id' => $status->id,
				'status_profile_id' => $status->profile_id,
				'profile_id' => $request->user()->profile_id
			]);
		}

		if ($request->wantsJson() && config_cache('federation.activitypub.enabled')) {
			return $this->showActivityPub($request, $status);
		}

		$template = $status->in_reply_to_id ? 'status.reply' : 'status.show';
		return view($template, compact('user', 'status'));
	}

	public function shortcodeRedirect(Request $request, $id)
	{
		abort(404);
	}

	public function showId(int $id)
	{
		abort(404);
		$status = Status::whereNull('reblog_of_id')
				->whereIn('scope', ['public', 'unlisted'])
				->findOrFail($id);
		return redirect($status->url());
	}

	public function showEmbed(Request $request, $username, int $id)
	{
		if(!config('instance.embed.post')) {
			$res = view('status.embed-removed');
			return response($res)->withHeaders(['X-Frame-Options' => 'ALLOWALL']);
		}

		$profile = Profile::whereNull(['domain','status'])
			->whereIsPrivate(false)
			->whereUsername($username)
			->first();
		if(!$profile) {
			$content = view('status.embed-removed');
			return response($content)->header('X-Frame-Options', 'ALLOWALL');
		}
		$status = Status::whereProfileId($profile->id)
			->whereNull('uri')
			->whereScope('public')
			->whereIsNsfw(false)
			->whereIn('type', ['photo', 'video','photo:album'])
			->find($id);
		if(!$status) {
			$content = view('status.embed-removed');
			return response($content)->header('X-Frame-Options', 'ALLOWALL');
		}
		$showLikes = $request->filled('likes') && $request->likes == true;
		$showCaption = $request->filled('caption') && $request->caption !== false;
		$layout = $request->filled('layout') && $request->layout == 'compact' ? 'compact' : 'full';
		$content = view('status.embed', compact('status', 'showLikes', 'showCaption', 'layout'));
		return response($content)->withHeaders(['X-Frame-Options' => 'ALLOWALL']);
	}

	public function showObject(Request $request, $username, int $id)
	{
		$user = Profile::whereNull('domain')->whereUsername($username)->firstOrFail();

		if($user->status != null) {
			return ProfileController::accountCheck($user);
		}

		$status = Status::whereProfileId($user->id)
				->whereNotIn('visibility',['draft','direct'])
				->findOrFail($id);

		abort_if($status->uri, 404);

		if($status->visibility == 'private' || $user->is_private) {
			if(!Auth::check()) {
				abort(403);
			}
			$pid = Auth::user()->profile;
			if($user->followedBy($pid) == false && $user->id !== $pid->id) {
				abort(403);
			}
		}

		return $this->showActivityPub($request, $status);
	}

	public function compose()
	{
		$this->authCheck();

		return view('status.compose');
	}

	public function store(Request $request)
	{
		return;
	}

	public function delete(Request $request)
	{
		$this->authCheck();

		$this->validate($request, [
		  'item'  => 'required|integer|min:1',
		]);

		$status = Status::findOrFail($request->input('item'));

		$user = Auth::user();

		if($status->profile_id != $user->profile->id &&
			$user->is_admin == true &&
			$status->uri == null
		) {
			$media = $status->media;

			$ai = new AccountInterstitial;
			$ai->user_id = $status->profile->user_id;
			$ai->type = 'post.removed';
			$ai->view = 'account.moderation.post.removed';
			$ai->item_type = 'App\Status';
			$ai->item_id = $status->id;
			$ai->has_media = (bool) $media->count();
			$ai->blurhash = $media->count() ? $media->first()->blurhash : null;
			$ai->meta = json_encode([
				'caption' => $status->caption,
				'created_at' => $status->created_at,
				'type' => $status->type,
				'url' => $status->url(),
				'is_nsfw' => $status->is_nsfw,
				'scope' => $status->scope,
				'reblog' => $status->reblog_of_id,
				'likes_count' => $status->likes_count,
				'reblogs_count' => $status->reblogs_count,
			]);
			$ai->save();

			$u = $status->profile->user;
			$u->has_interstitial = true;
			$u->save();
		}

		Cache::forget('_api:statuses:recent_9:' . $status->profile_id);
		Cache::forget('profile:status_count:' . $status->profile_id);
		Cache::forget('profile:embed:' . $status->profile_id);
		StatusService::del($status->id, true);
		if ($status->profile_id == $user->profile->id || $user->is_admin == true) {
			Cache::forget('profile:status_count:'.$status->profile_id);
			StatusDelete::dispatchNow($status);
		}

		if($request->wantsJson()) {
			return response()->json(['Status successfully deleted.']);
		} else {
			return redirect($user->url());
		}
	}

	public function storeShare(Request $request)
	{
		$this->authCheck();

		$this->validate($request, [
		  'item'    => 'required|integer|min:1',
		]);

		$user = Auth::user();
		$profile = $user->profile;
		$status = Status::whereScope('public')
			->findOrFail($request->input('item'));

		$count = $status->reblogs_count;

		$exists = Status::whereProfileId(Auth::user()->profile->id)
				  ->whereReblogOfId($status->id)
				  ->exists();
		if ($exists == true) {
			$shares = Status::whereProfileId(Auth::user()->profile->id)
				  ->whereReblogOfId($status->id)
				  ->get();
			foreach ($shares as $share) {
				UndoSharePipeline::dispatch($share);
				ReblogService::del($profile->id, $status->id);
				$count--;
			}
		} else {
			$share = new Status();
			$share->profile_id = $profile->id;
			$share->reblog_of_id = $status->id;
			$share->in_reply_to_profile_id = $status->profile_id;
			$share->type = 'share';
			$share->save();
			$count++;
			SharePipeline::dispatch($share);
			ReblogService::add($profile->id, $status->id);
		}

		Cache::forget('status:'.$status->id.':sharedby:userid:'.$user->id);
		StatusService::del($status->id);

		if ($request->ajax()) {
			$response = ['code' => 200, 'msg' => 'Share saved', 'count' => $count];
		} else {
			$response = redirect($status->url());
		}

		return $response;
	}

	public function showActivityPub(Request $request, $status)
	{
		$object = $status->type == 'poll' ? new Question() : new Note();
		$fractal = new Fractal\Manager();
		$resource = new Fractal\Resource\Item($status, $object);
		$res = $fractal->createData($resource)->toArray();

		return response()->json($res['data'], 200, ['Content-Type' => 'application/activity+json'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}

	public function edit(Request $request, $username, $id)
	{
		$this->authCheck();
		$user = Auth::user()->profile;
		$status = Status::whereProfileId($user->id)
				->with(['media'])
				->findOrFail($id);
		$licenses = License::get();
		return view('status.edit', compact('user', 'status', 'licenses'));
	}

	public function editStore(Request $request, $username, $id)
	{
		$this->authCheck();
		$user = Auth::user()->profile;
		$status = Status::whereProfileId($user->id)
				->with(['media'])
				->findOrFail($id);

		$this->validate($request, [
		  'license'      => 'nullable|integer|min:1|max:16',
		]);

		$licenseId = $request->input('license');

		$status->media->each(function($media) use($licenseId) {
			$media->license = $licenseId;
			$media->save();
			Cache::forget('status:transformer:media:attachments:'.$media->status_id);
		});

		return redirect($status->url());
	}

	protected function authCheck()
	{
		if (Auth::check() == false) {
			abort(403);
		}
	}

	protected function validateVisibility($visibility)
	{
		$allowed = ['public', 'unlisted', 'private'];
		return in_array($visibility, $allowed) ? $visibility : 'public';
	}

	public static function mimeTypeCheck($mimes)
	{
		$allowed = explode(',', config_cache('pixelfed.media_types'));
		$count = count($mimes);
		$photos = 0;
		$videos = 0;
		foreach($mimes as $mime) {
			if(in_array($mime, $allowed) == false && $mime !== 'video/mp4') {
				continue;
			}
			if(str_contains($mime, 'image/')) {
				$photos++;
			}
			if(str_contains($mime, 'video/')) {
				$videos++;
			}
		}
		if($photos == 1 && $videos == 0) {
			return 'photo';
		}
		if($videos == 1 && $photos == 0) {
			return 'video';
		}
		if($photos > 1 && $videos == 0) {
			return 'photo:album';
		}
		if($videos > 1 && $photos == 0) {
			return 'video:album';
		}
		if($photos >= 1 && $videos >= 1) {
			return 'photo:video:album';
		}

		return 'text';
	}

	public function toggleVisibility(Request $request) {
		$this->authCheck();
		$this->validate($request, [
			'item' => 'required|string|min:1|max:20',
			'disableComments' => 'required|boolean'
		]);

		$user = Auth::user();
		$id = $request->input('item');
		$state = $request->input('disableComments');

		$status = Status::findOrFail($id);

		if($status->profile_id != $user->profile->id && $user->is_admin == false) {
			abort(403);
		}

		$status->comments_disabled = $status->comments_disabled == true ? false : true;
		$status->save();

		return response()->json([200]);
	}

	public function storeView(Request $request)
	{
		abort_if(!$request->user(), 403);

		$views = $request->input('_v');
		$uid = $request->user()->profile_id;

		if(empty($views) || !is_array($views)) {
			return response()->json(0);
		}

		Cache::forget('profile:home-timeline-cursor:' . $request->user()->id);

		foreach($views as $view) {
			if(!isset($view['sid']) || !isset($view['pid'])) {
				continue;
			}
			DB::transaction(function () use($view, $uid) {
				StatusView::firstOrCreate([
						'status_id' => $view['sid'],
						'status_profile_id' => $view['pid'],
						'profile_id' => $uid
				]);
			});
		}

		return response()->json(1);
	}
}
