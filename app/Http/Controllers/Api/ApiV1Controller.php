<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Util\ActivityPub\Helpers;
use App\Util\Media\Filter;
use Laravel\Passport\Passport;
use Auth, Cache, DB, URL;
use App\{
	Avatar,
	Bookmark,
	DirectMessage,
	Follower,
	FollowRequest,
	Hashtag,
	Instance,
	Like,
	Media,
	Notification,
	Profile,
	Status,
	StatusHashtag,
	User,
	UserSetting,
	UserFilter,
};
use League\Fractal;
use App\Transformer\Api\Mastodon\v1\{
	AccountTransformer,
	MediaTransformer,
	NotificationTransformer,
	StatusTransformer,
};
use App\Transformer\Api\{
	RelationshipTransformer,
};
use App\Http\Controllers\FollowerController;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use App\Http\Controllers\StatusController;

use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Jobs\CommentPipeline\CommentPipeline;
use App\Jobs\LikePipeline\LikePipeline;
use App\Jobs\SharePipeline\SharePipeline;
use App\Jobs\SharePipeline\UndoSharePipeline;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\VideoPipeline\{
	VideoOptimize,
	VideoPostProcess,
	VideoThumbnail
};

use App\Services\{
	AccountService,
	FollowerService,
	InstanceService,
	LikeService,
	NotificationService,
	MediaPathService,
	PublicTimelineService,
	ReblogService,
	RelationshipService,
	SearchApiV2Service,
	StatusService,
	MediaBlocklistService,
	SnowflakeService,
	UserFilterService
};
use App\Util\Lexer\Autolink;
use App\Util\Localization\Localization;
use App\Util\Media\License;
use App\Jobs\MediaPipeline\MediaSyncLicensePipeline;
use App\Services\DiscoverService;
use App\Services\CustomEmojiService;

class ApiV1Controller extends Controller
{
	protected $fractal;

	public function __construct()
	{
		$this->fractal = new Fractal\Manager();
		$this->fractal->setSerializer(new ArraySerializer());
	}

	public function apps(Request $request)
	{
		abort_if(!config_cache('pixelfed.oauth_enabled'), 404);

		$this->validate($request, [
			'client_name' 		=> 'required',
			'redirect_uris' 	=> 'required',
			'scopes' 			=> 'nullable',
			'website' 			=> 'nullable'
		]);

		$uris = implode(',', explode('\n', $request->redirect_uris));

		$client = Passport::client()->forceFill([
			'user_id' => null,
			'name' => e($request->client_name),
			'secret' => Str::random(40),
			'redirect' => $uris,
			'personal_access_client' => false,
			'password_client' => false,
			'revoked' => false,
		]);

		$client->save();

		$res = [
			'id' => $client->id,
			'name' => $client->name,
			'website' => null,
			'redirect_uri' => $client->redirect,
			'client_id' => $client->id,
			'client_secret' => $client->secret,
			'vapid_key' => null
		];

		return response()->json($res, 200, [
			'Access-Control-Allow-Origin' => '*'
		]);
	}

	/**
	 * GET /api/v1/accounts/verify_credentials
	 *
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function verifyCredentials(Request $request)
	{
		$user = $request->user();

		abort_if(!$user, 403);
		abort_if($user->status != null, 403);

		$res = AccountService::getMastodon($user->profile_id);

		$res['source'] = [
			'privacy' => $res['locked'] ? 'private' : 'public',
			'sensitive' => false,
			'language' => $user->language ?? 'en',
			'note' => '',
			'fields' => []
		];

		return response()->json($res);
	}

	/**
	 * GET /api/v1/accounts/{id}
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function accountById(Request $request, $id)
	{
		$res = AccountService::getMastodon($id, true);
		if(!$res) {
			return response()->json(['error' => 'Record not found'], 404);
		}
		return response()->json($res);
	}

	/**
	 * PATCH /api/v1/accounts/update_credentials
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function accountUpdateCredentials(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'avatar'			=> 'sometimes|mimetypes:image/jpeg,image/png',
			'display_name'      => 'nullable|string',
			'note'              => 'nullable|string',
			'locked'            => 'nullable',
			'website'			=> 'nullable',
			// 'source.privacy'    => 'nullable|in:unlisted,public,private',
			// 'source.sensitive'  => 'nullable|boolean'
		]);

		$user = $request->user();
		$profile = $user->profile;
		$settings = $user->settings;

		$changes = false;
		$other = array_merge(AccountService::defaultSettings()['other'], $settings->other ?? []);
		$syncLicenses = false;
		$licenseChanged = false;
		$composeSettings = array_merge(AccountService::defaultSettings()['compose_settings'], $settings->compose_settings ?? []);

		// return $request->input('locked');

		if($request->has('avatar')) {
			$av = Avatar::whereProfileId($profile->id)->first();
			if($av) {
				$currentAvatar = storage_path('app/'.$av->media_path);
				$file = $request->file('avatar');
				$path = "public/avatars/{$profile->id}";
				$name = strtolower(str_random(6)). '.' . $file->guessExtension();
				$request->file('avatar')->storeAs($path, $name);
				$av->media_path = "{$path}/{$name}";
				$av->save();
				Cache::forget("avatar:{$profile->id}");
				Cache::forget('user:account:id:'.$user->id);
				AvatarOptimize::dispatch($user->profile, $currentAvatar);
			}
			$changes = true;
		}

		if($request->has('source[language]')) {
			$lang = $request->input('source[language]');
			if(in_array($lang, Localization::languages())) {
				$user->language = $lang;
				$changes = true;
				$other['language'] = $lang;
			}
		}

		if($request->has('website')) {
			$website = $request->input('website');
			if($website != $profile->website) {
				if($website) {
					if(!strpos($website, '.')) {
						$website = null;
					}

					if($website && !strpos($website, '://')) {
						$website = 'https://' . $website;
					}

					$host = parse_url($website, PHP_URL_HOST);

					$bannedInstances = InstanceService::getBannedDomains();
					if(in_array($host, $bannedInstances)) {
						$website = null;
					}
				}
				$profile->website = $website ? $website : null;
				$changes = true;
			}
		}

		if($request->has('display_name')) {
			$displayName = $request->input('display_name');
			if($displayName !== $user->name) {
				$user->name = $displayName;
				$profile->name = $displayName;
				$changes = true;
			}
		}

		if($request->has('note')) {
			$note = $request->input('note');
			if($note !== strip_tags($profile->bio)) {
				$profile->bio = Autolink::create()->autolink(strip_tags($note));
				$changes = true;
			}
		}

		if($request->has('locked')) {
			$locked = $request->input('locked') == 'true';
			if($profile->is_private != $locked) {
				$profile->is_private = $locked;
				$changes = true;
			}
		}

		if($request->has('reduce_motion')) {
			$reduced = $request->input('reduce_motion');
			if($settings->reduce_motion != $reduced) {
				$settings->reduce_motion = $reduced;
				$changes = true;
			}
		}

		if($request->has('high_contrast_mode')) {
			$contrast = $request->input('high_contrast_mode');
			if($settings->high_contrast_mode != $contrast) {
				$settings->high_contrast_mode = $contrast;
				$changes = true;
			}
		}

		if($request->has('video_autoplay')) {
			$autoplay = $request->input('video_autoplay');
			if($settings->video_autoplay != $autoplay) {
				$settings->video_autoplay = $autoplay;
				$changes = true;
			}
		}

		if($request->has('license')) {
			$license = $request->input('license');
			abort_if(!in_array($license, License::keys()), 422, 'Invalid media license id');
			$syncLicenses = $request->input('sync_licenses') == true;
			abort_if($syncLicenses && Cache::get('pf:settings:mls_recently:'.$user->id) == 2, 422, 'You can only sync licenses twice per 24 hours');
			if($composeSettings['default_license'] != $license) {
				$composeSettings['default_license'] = $license;
				$licenseChanged = true;
				$changes = true;
			}
		}

		if($request->has('media_descriptions')) {
			$md = $request->input('media_descriptions') == true;
			if($composeSettings['media_descriptions'] != $md) {
				$composeSettings['media_descriptions'] = $md;
				$changes = true;
			}
		}

		if($request->has('crawlable')) {
			$crawlable = $request->input('crawlable');
			if($settings->crawlable != $crawlable) {
				$settings->crawlable = $crawlable;
				$changes = true;
			}
		}

		if($request->has('show_profile_follower_count')) {
			$show_profile_follower_count = $request->input('show_profile_follower_count');
			if($settings->show_profile_follower_count != $show_profile_follower_count) {
				$settings->show_profile_follower_count = $show_profile_follower_count;
				$changes = true;
			}
		}

		if($request->has('show_profile_following_count')) {
			$show_profile_following_count = $request->input('show_profile_following_count');
			if($settings->show_profile_following_count != $show_profile_following_count) {
				$settings->show_profile_following_count = $show_profile_following_count;
				$changes = true;
			}
		}

		if($request->has('public_dm')) {
			$public_dm = $request->input('public_dm');
			if($settings->public_dm != $public_dm) {
				$settings->public_dm = $public_dm;
				$changes = true;
			}
		}

		if($request->has('source[privacy]')) {
			$scope = $request->input('source[privacy]');
			if(in_array($scope, ['public', 'private', 'unlisted'])) {
				if($composeSettings['default_scope'] != $scope) {
					$composeSettings['default_scope'] = $profile->is_private ? 'private' : $scope;
					$changes = true;
				}
			}
		}

		if($request->has('disable_embeds')) {
			$disabledEmbeds = $request->input('disable_embeds');
			if($other['disable_embeds'] != $disabledEmbeds) {
				$other['disable_embeds'] = $disabledEmbeds;
				$changes = true;
			}
		}

		if($changes) {
			$settings->other = $other;
			$settings->compose_settings = $composeSettings;
			$settings->save();
			$user->save();
			$profile->save();
			Cache::forget('profile:settings:' . $profile->id);
			Cache::forget('user:account:id:' . $profile->user_id);
			Cache::forget('profile:follower_count:' . $profile->id);
			Cache::forget('profile:following_count:' . $profile->id);
			Cache::forget('profile:embed:' . $profile->id);
			Cache::forget('profile:compose:settings:' . $user->id);
			Cache::forget('profile:view:'.$user->username);
			AccountService::del($user->profile_id);
		}

		if($syncLicenses && $licenseChanged) {
			$key = 'pf:settings:mls_recently:'.$user->id;
			$val = Cache::has($key) ? 2 : 1;
			Cache::put($key, $val, 86400);
			MediaSyncLicensePipeline::dispatch($user->id, $request->input('license'));
		}

		$res = AccountService::getMastodon($user->profile_id);
		$res['bio'] = strip_tags($res['note']);
		$res = array_merge($res, $other);

		return response()->json($res);
	}

	/**
	 * GET /api/v1/accounts/{id}/followers
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function accountFollowersById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);
		$account = AccountService::get($id);
		abort_if(!$account, 404);
		$pid = $request->user()->profile_id;

		if($pid != $account['id']) {
			if($account['locked']) {
				if(FollowerService::follows($pid, $account['id'])) {
					return [];
				}
			}

			if(AccountService::hiddenFollowers($id)) {
				return [];
			}

			if($request->has('page') && $request->page >= 5) {
				return [];
			}
		}

		$res = DB::table('followers')
			->select('id', 'profile_id', 'following_id')
			->whereFollowingId($account['id'])
			->orderByDesc('id')
			->simplePaginate(10)
			->map(function($follower) {
				return AccountService::getMastodon($follower->profile_id);
			})
			->filter(function($account) {
				return $account && isset($account['id']);
			})
			->values()
			->toArray();

		return response()->json($res);
	}

	/**
	 * GET /api/v1/accounts/{id}/following
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function accountFollowingById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);
		$account = AccountService::get($id);
		abort_if(!$account, 404);
		$pid = $request->user()->profile_id;

		if($pid != $account['id']) {
			if($account['locked']) {
				if(FollowerService::follows($pid, $account['id'])) {
					return [];
				}
			}

			if(AccountService::hiddenFollowing($id)) {
				return [];
			}

			if($request->has('page') && $request->page >= 5) {
				return [];
			}
		}

		$res = DB::table('followers')
			->select('id', 'profile_id', 'following_id')
			->whereProfileId($account['id'])
			->orderByDesc('id')
			->simplePaginate(10)
			->map(function($follower) {
				return AccountService::get($follower->following_id);
			})
			->filter(function($account) {
				return $account && isset($account['id']);
			})
			->values()
			->toArray();

		return response()->json($res);
	}

	/**
	 * GET /api/v1/accounts/{id}/statuses
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\StatusTransformer
	 */
	public function accountStatusesById(Request $request, $id)
	{
		$user = $request->user();

		$this->validate($request, [
			'only_media' => 'nullable',
			'media_type' => 'sometimes|string|in:photo,video',
			'pinned' => 'nullable',
			'exclude_replies' => 'nullable',
			'max_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
			'since_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
			'min_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
			'limit' => 'nullable|integer|min:1|max:80'
		]);

		$profile = AccountService::getMastodon($id);
        abort_if(!$profile, 404);

		$limit = $request->limit ?? 20;
		$max_id = $request->max_id;
		$min_id = $request->min_id;

		if(!$max_id && !$min_id) {
			$min_id = 1;
		}

		$pid = $request->user()->profile_id;
		$scope = $request->only_media == true ?
			['photo', 'photo:album', 'video', 'video:album'] :
			['photo', 'photo:album', 'video', 'video:album', 'share', 'reply'];

		if($request->only_media && $request->has('media_type')) {
			$mt = $request->input('media_type');
			if($mt == 'video') {
				$scope = ['video', 'video:album'];
			}
		}

		if($pid == $profile['id']) {
			$visibility = ['public', 'unlisted', 'private'];
		} else if($profile['locked']) {
			$following = FollowerService::follows($pid, $profile['id']);
			abort_unless($following, 403);
			$visibility = ['public', 'unlisted', 'private'];
		} else {
			$following = FollowerService::follows($pid, $profile['id']);
			$visibility = $following ? ['public', 'unlisted', 'private'] : ['public', 'unlisted'];
		}

		$dir = $min_id ? '>' : '<';
		$id = $min_id ?? $max_id;
		$res = Status::whereProfileId($profile['id'])
		->whereNull('in_reply_to_id')
		->whereNull('reblog_of_id')
		->whereIn('type', $scope)
		->where('id', $dir, $id)
		->whereIn('scope', $visibility)
		->limit($limit)
		->orderByDesc('id')
		->get()
		->map(function($s) use($user) {
			try {
				$status = StatusService::getMastodon($s->id, false);
			} catch (\Exception $e) {
				$status = false;
			}
			if($user && $status) {
				$status['favourited'] = (bool) LikeService::liked($user->profile_id, $s->id);
			}
			return $status;
		})
		->filter(function($s) {
			return $s;
		})
		->values();

		return response()->json($res);
	}

	/**
	 * POST /api/v1/accounts/{id}/follow
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\RelationshipTransformer
	 */
	public function accountFollowById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();

		$target = Profile::where('id', '!=', $user->profile_id)
			->whereNull('status')
			->findOrFail($id);


		$private = (bool) $target->is_private;
		$remote = (bool) $target->domain;
		$blocked = UserFilter::whereUserId($target->id)
				->whereFilterType('block')
				->whereFilterableId($user->profile_id)
				->whereFilterableType('App\Profile')
				->exists();

		if($blocked == true) {
			abort(400, 'You cannot follow this user.');
		}

		$isFollowing = Follower::whereProfileId($user->profile_id)
			->whereFollowingId($target->id)
			->exists();

		// Following already, return empty relationship
		if($isFollowing == true) {
			$res = RelationshipService::get($user->profile_id, $target->id) ?? [];
			return response()->json($res);
		}

		// Rate limits, max 7500 followers per account
		if($user->profile->following()->count() >= Follower::MAX_FOLLOWING) {
			abort(400, 'You cannot follow more than ' . Follower::MAX_FOLLOWING . ' accounts');
		}

		// Rate limits, follow 30 accounts per hour max
		if($user->profile->following()->where('followers.created_at', '>', now()->subHour())->count() >= Follower::FOLLOW_PER_HOUR) {
			abort(400, 'You can only follow ' . Follower::FOLLOW_PER_HOUR . ' users per hour');
		}

		if($private == true) {
			$follow = FollowRequest::firstOrCreate([
				'follower_id' => $user->profile_id,
				'following_id' => $target->id
			]);
			if($remote == true && config('federation.activitypub.remoteFollow') == true) {
				(new FollowerController())->sendFollow($user->profile, $target);
			}
		} else {
			$follower = new Follower();
			$follower->profile_id = $user->profile_id;
			$follower->following_id = $target->id;
			$follower->save();

			if($remote == true && config('federation.activitypub.remoteFollow') == true) {
				(new FollowerController())->sendFollow($user->profile, $target);
			}
			FollowPipeline::dispatch($follower);
		}

		RelationshipService::refresh($user->profile_id, $target->id);
		Cache::forget('profile:following:'.$target->id);
		Cache::forget('profile:followers:'.$target->id);
		Cache::forget('profile:following:'.$user->profile_id);
		Cache::forget('profile:followers:'.$user->profile_id);
		Cache::forget('api:local:exp:rec:'.$user->profile_id);
		Cache::forget('user:account:id:'.$target->user_id);
		Cache::forget('user:account:id:'.$user->id);
		Cache::forget('profile:follower_count:'.$target->id);
		Cache::forget('profile:follower_count:'.$user->profile_id);
		Cache::forget('profile:following_count:'.$target->id);
		Cache::forget('profile:following_count:'.$user->profile_id);
		AccountService::del($user->profile_id);
		AccountService::del($target->id);

		$res = RelationshipService::get($user->profile_id, $target->id);

		return response()->json($res);
	}

	/**
	 * POST /api/v1/accounts/{id}/unfollow
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\RelationshipTransformer
	 */
	public function accountUnfollowById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();

		$target = Profile::where('id', '!=', $user->profile_id)
			->whereNull('status')
			->findOrFail($id);

		$private = (bool) $target->is_private;
		$remote = (bool) $target->domain;

		$isFollowing = Follower::whereProfileId($user->profile_id)
			->whereFollowingId($target->id)
			->exists();

		if($isFollowing == false) {
			$resource = new Fractal\Resource\Item($target, new RelationshipTransformer());
			$res = $this->fractal->createData($resource)->toArray();

			return response()->json($res);
		}

		// Rate limits, follow 30 accounts per hour max
		if($user->profile->following()->where('followers.updated_at', '>', now()->subHour())->count() >= Follower::FOLLOW_PER_HOUR) {
			abort(400, 'You can only follow or unfollow ' . Follower::FOLLOW_PER_HOUR . ' users per hour');
		}

		$user->profile->decrement('following_count');

		FollowRequest::whereFollowerId($user->profile_id)
			->whereFollowingId($target->id)
			->delete();

		Follower::whereProfileId($user->profile_id)
			->whereFollowingId($target->id)
			->delete();

		if($remote == true && config('federation.activitypub.remoteFollow') == true) {
			(new FollowerController())->sendUndoFollow($user->profile, $target);
		}

		RelationshipService::refresh($user->profile_id, $target->id);
		Cache::forget('profile:following:'.$target->id);
		Cache::forget('profile:followers:'.$target->id);
		Cache::forget('profile:following:'.$user->profile_id);
		Cache::forget('profile:followers:'.$user->profile_id);
		Cache::forget('api:local:exp:rec:'.$user->profile_id);
		Cache::forget('user:account:id:'.$target->user_id);
		Cache::forget('user:account:id:'.$user->id);
		Cache::forget('profile:follower_count:'.$target->id);
		Cache::forget('profile:follower_count:'.$user->profile_id);
		Cache::forget('profile:following_count:'.$target->id);
		Cache::forget('profile:following_count:'.$user->profile_id);
		AccountService::del($user->profile_id);
		AccountService::del($target->id);

		$res = RelationshipService::get($user->profile_id, $target->id);

		return response()->json($res);
	}

	/**
	 * GET /api/v1/accounts/relationships
	 *
	 * @param  array|integer  $id
	 *
	 * @return \App\Services\RelationshipService
	 */
	public function accountRelationshipsById(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'id'    => 'required|array|min:1|max:20',
			'id.*'  => 'required|integer|min:1|max:' . PHP_INT_MAX
		]);
		$pid = $request->user()->profile_id ?? $request->user()->profile->id;
		$res = collect($request->input('id'))
			->filter(function($id) use($pid) {
				return $id != $pid;
			})
			->map(function($id) use($pid) {
				return RelationshipService::get($pid, $id);
		});
		return response()->json($res);
	}

	/**
	 * GET /api/v1/accounts/search
	 *
	 *
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function accountSearch(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'q'         => 'required|string|min:1|max:255',
			'limit'     => 'nullable|integer|min:1|max:40',
			'resolve'   => 'nullable'
		]);

		$user = $request->user();
		$query = $request->input('q');
		$limit = $request->input('limit') ?? 20;
		$resolve = (bool) $request->input('resolve', false);
		$q = '%' . $query . '%';

		$profiles = Profile::whereNull('status')
			->where('username', 'like', $q)
			->orWhere('name', 'like', $q)
			->limit($limit)
			->get();

		$resource = new Fractal\Resource\Collection($profiles, new AccountTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		return response()->json($res);
	}

	/**
	 * GET /api/v1/blocks
	 *
	 *
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function accountBlocks(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'limit'     => 'nullable|integer|min:1|max:40',
			'page'      => 'nullable|integer|min:1|max:10'
		]);

		$user = $request->user();
		$limit = $request->input('limit') ?? 40;

		$blocked = UserFilter::select('filterable_id','filterable_type','filter_type','user_id')
			->whereUserId($user->profile_id)
			->whereFilterableType('App\Profile')
			->whereFilterType('block')
			->simplePaginate($limit)
			->pluck('filterable_id');

		$profiles = Profile::findOrFail($blocked);
		$resource = new Fractal\Resource\Collection($profiles, new AccountTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		return response()->json($res);
	}

	/**
	 * POST /api/v1/accounts/{id}/block
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\RelationshipTransformer
	 */
	public function accountBlockById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();
		$pid = $user->profile_id ?? $user->profile->id;

		if($id == $pid) {
			abort(400, 'You cannot block yourself');
		}

		$profile = Profile::findOrFail($id);

		if($profile->user->is_admin == true) {
			abort(400, 'You cannot block an admin');
		}

		Follower::whereProfileId($profile->id)->whereFollowingId($pid)->delete();
		Follower::whereProfileId($pid)->whereFollowingId($profile->id)->delete();
		Notification::whereProfileId($pid)->whereActorId($profile->id)->delete();

		$filter = UserFilter::firstOrCreate([
			'user_id'         => $pid,
			'filterable_id'   => $profile->id,
			'filterable_type' => 'App\Profile',
			'filter_type'     => 'block',
		]);

		Cache::forget("user:filter:list:$pid");
		Cache::forget("api:local:exp:rec:$pid");

		$resource = new Fractal\Resource\Item($profile, new RelationshipTransformer());
		$res = $this->fractal->createData($resource)->toArray();

		return response()->json($res);
	}

	/**
	 * POST /api/v1/accounts/{id}/unblock
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\RelationshipTransformer
	 */
	public function accountUnblockById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();
		$pid = $user->profile_id ?? $user->profile->id;

		if($id == $pid) {
			abort(400, 'You cannot unblock yourself');
		}

		$profile = Profile::findOrFail($id);

		UserFilter::whereUserId($pid)
			->whereFilterableId($profile->id)
			->whereFilterableType('App\Profile')
			->whereFilterType('block')
			->delete();

		Cache::forget("user:filter:list:$pid");
		Cache::forget("api:local:exp:rec:$pid");

		$resource = new Fractal\Resource\Item($profile, new RelationshipTransformer());
		$res = $this->fractal->createData($resource)->toArray();

		return response()->json($res);
	}

	/**
	 * GET /api/v1/custom_emojis
	 *
	 * Return empty array, we don't support custom emoji
	 *
	 * @return array
	 */
	public function customEmojis()
	{
		return response(CustomEmojiService::all())->header('Content-Type', 'application/json');
	}

	/**
	 * GET /api/v1/domain_blocks
	 *
	 * Return empty array
	 *
	 * @return array
	 */
	public function accountDomainBlocks(Request $request)
	{
		abort_if(!$request->user(), 403);
		return response()->json([]);
	}

	/**
	 * GET /api/v1/endorsements
	 *
	 * Return empty array
	 *
	 * @return array
	 */
	public function accountEndorsements(Request $request)
	{
		abort_if(!$request->user(), 403);
		return response()->json([]);
	}

	/**
	 * GET /api/v1/favourites
	 *
	 * Returns collection of liked statuses
	 *
	 * @return \App\Transformer\Api\StatusTransformer
	 */
	public function accountFavourites(Request $request)
	{
		abort_if(!$request->user(), 403);
		$this->validate($request, [
			'limit' => 'sometimes|integer|min:1|max:20'
		]);

		$user = $request->user();
		$maxId = $request->input('max_id');
		$minId = $request->input('min_id');
		$limit = $request->input('limit') ?? 10;

		$res = Like::whereProfileId($user->profile_id)
			->when($maxId, function($q, $maxId) {
				return $q->where('id', '<', $maxId);
			})
			->when($minId, function($q, $minId) {
				return $q->where('id', '>', $minId);
			})
			->orderByDesc('id')
			->limit($limit)
			->get()
			->map(function($like) {
				$status =  StatusService::getMastodon($like['status_id'], false);
				$status['like_id'] = $like->id;
				$status['liked_at'] = $like->created_at->format('c');
				return $status;
			})
			->filter(function($status) {
				return $status && isset($status['id'], $status['like_id']);
			})
			->values();

		if($res->count()) {
			$ids = $res->map(function($status) {
				return $status['like_id'];
			});
			$max = $ids->max();
			$min = $ids->min();

			$baseUrl = config('app.url') . '/api/v1/favourites?limit=' . $limit . '&';
			$link = '<'.$baseUrl.'max_id='.$max.'>; rel="next",<'.$baseUrl.'min_id='.$min.'>; rel="prev"';
			return response()
				->json($res)
				->withHeaders([
					'Link' => $link,
				]);
		} else {
			return response()->json($res);
		}
	}

	/**
	 * POST /api/v1/statuses/{id}/favourite
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\StatusTransformer
	 */
	public function statusFavouriteById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();

		$status = Status::findOrFail($id);

		if($status->profile_id !== $user->profile_id) {
			if($status->scope == 'private') {
				abort_if(!$status->profile->followedBy($user->profile), 403);
			} else {
				abort_if(!in_array($status->scope, ['public','unlisted']), 403);
			}
		}

		$like = Like::firstOrCreate([
			'profile_id' => $user->profile_id,
			'status_id' => $status->id
		]);

		if($like->wasRecentlyCreated == true) {
			$like->status_profile_id = $status->profile_id;
			$like->is_comment = !empty($status->in_reply_to_id);
			$like->save();
			$status->likes_count = $status->likes()->count();
			$status->save();
			LikePipeline::dispatch($like);
		}

		$res = StatusService::getMastodon($status->id, false);
		$res['favourited'] = true;
		return response()->json($res);
	}

	/**
	 * POST /api/v1/statuses/{id}/unfavourite
	 *
	 * @param  integer  $id
	 *
	 * @return \App\Transformer\Api\StatusTransformer
	 */
	public function statusUnfavouriteById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();

		$status = Status::findOrFail($id);

		if($status->profile_id !== $user->profile_id) {
			if($status->scope == 'private') {
				abort_if(!$status->profile->followedBy($user->profile), 403);
			} else {
				abort_if(!in_array($status->scope, ['public','unlisted']), 403);
			}
		}

		$like = Like::whereProfileId($user->profile_id)
			->whereStatusId($status->id)
			->first();

		if($like) {
			$like->forceDelete();
			$status->likes_count = $status->likes()->count();
			$status->save();
		}

		StatusService::del($status->id);

		$res = StatusService::getMastodon($status->id, false);
		$res['favourited'] = false;
		return response()->json($res);
	}

	/**
	 * GET /api/v1/filters
	 *
	 *  Return empty response since we filter server side
	 *
	 * @return array
	 */
	public function accountFilters(Request $request)
	{
		abort_if(!$request->user(), 403);

		return response()->json([]);
	}

	/**
	 * GET /api/v1/follow_requests
	 *
	 *  Return array of Accounts that have sent follow requests
	 *
	 * @return \App\Transformer\Api\AccountTransformer
	 */
	public function accountFollowRequests(Request $request)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();

		$followRequests = FollowRequest::whereFollowingId($user->profile->id)->pluck('follower_id');

		$profiles = Profile::find($followRequests);

		$resource = new Fractal\Resource\Collection($profiles, new AccountTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		return response()->json($res);
	}

	/**
	 * POST /api/v1/follow_requests/{id}/authorize
	 *
	 * @param  integer  $id
	 *
	 * @return null
	 */
	public function accountFollowRequestAccept(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		// todo

		return response()->json([]);
	}

	/**
	 * POST /api/v1/follow_requests/{id}/reject
	 *
	 * @param  integer  $id
	 *
	 * @return null
	 */
	public function accountFollowRequestReject(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		// todo

		return response()->json([]);
	}

	/**
	 * GET /api/v1/suggestions
	 *
	 *   Return empty array as we don't support suggestions
	 *
	 * @return null
	 */
	public function accountSuggestions(Request $request)
	{
		abort_if(!$request->user(), 403);

		// todo

		return response()->json([]);
	}

	/**
	 * GET /api/v1/instance
	 *
	 *   Information about the server.
	 *
	 * @return Instance
	 */
	public function instance(Request $request)
	{
		$res = Cache::remember('api:v1:instance-data-response-v0', 1800, function () {
			$contact = Cache::remember('api:v1:instance-data:contact', 604800, function () {
				$admin = User::whereIsAdmin(true)->first();
				return $admin && isset($admin->profile_id) ?
					AccountService::getMastodon($admin->profile_id, true) :
					null;
			});

			$stats = Cache::remember('api:v1:instance-data:stats', 43200, function () {
				return [
					'user_count' => User::count(),
					'status_count' => Status::whereNull('uri')->count(),
					'domain_count' => Instance::count(),
				];
			});

			$rules = Cache::remember('api:v1:instance-data:rules', 604800, function () {
				return config_cache('app.rules') ?
					collect(json_decode(config_cache('app.rules'), true))
					->map(function($rule, $key) {
						$id = $key + 1;
						return [
							'id' => "{$id}",
							'text' => $rule
						];
					})
					->toArray() : [];
			});

			return [
				'uri' => config('pixelfed.domain.app'),
				'title' => config('app.name'),
				'short_description' => 'Pixelfed is an image sharing platform, an ethical alternative to centralized platforms',
				'description' => 'Pixelfed is an image sharing platform, an ethical alternative to centralized platforms',
				'email' => config('instance.email'),
				'version' => '2.7.2 (compatible; Pixelfed ' . config('pixelfed.version') .')',
				'urls' => [],
				'stats' => $stats,
				'thumbnail' => url('headers/default.jpg'),
				'languages' => ['en'],
				'registrations' => (bool) config('pixelfed.open_registration'),
				'approval_required' => false,
				'contact_account' => $contact,
				'rules' => $rules
			];
		});

		return response()->json($res);
	}

	/**
	 * GET /api/v1/lists
	 *
	 *   Return empty array as we don't support lists
	 *
	 * @return null
	 */
	public function accountLists(Request $request)
	{
		abort_if(!$request->user(), 403);

		return response()->json([]);
	}

	/**
	 * GET /api/v1/accounts/{id}/lists
	 *
	 * @param  integer  $id
	 *
	 * @return null
	 */
	public function accountListsById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		return response()->json([]);
	}

	/**
	 * POST /api/v1/media
	 *
	 *
	 * @return MediaTransformer
	 */
	public function mediaUpload(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
		  'file.*'      => function() {
			return [
				'required',
				'mimes:' . config_cache('pixelfed.media_types'),
				'max:' . config_cache('pixelfed.max_photo_size'),
			];
		  },
		  'filter_name' => 'nullable|string|max:24',
		  'filter_class' => 'nullable|alpha_dash|max:24',
		  'description' => 'nullable|string|max:' . config_cache('pixelfed.max_altext_length')
		]);

		$user = $request->user();

		if($user->last_active_at == null) {
			return [];
		}

		$limitKey = 'compose:rate-limit:media-upload:' . $user->id;
		$limitTtl = now()->addMinutes(15);
		$limitReached = Cache::remember($limitKey, $limitTtl, function() use($user) {
			$dailyLimit = Media::whereUserId($user->id)->where('created_at', '>', now()->subDays(1))->count();

			return $dailyLimit >= 250;
		});
		abort_if($limitReached == true, 429);

		$profile = $user->profile;

		if(config_cache('pixelfed.enforce_account_limit') == true) {
			$size = Cache::remember($user->storageUsedKey(), now()->addDays(3), function() use($user) {
				return Media::whereUserId($user->id)->sum('size') / 1000;
			});
			$limit = (int) config_cache('pixelfed.max_account_size');
			if ($size >= $limit) {
			   abort(403, 'Account size limit reached.');
			}
		}

		$filterClass = in_array($request->input('filter_class'), Filter::classes()) ? $request->input('filter_class') : null;
		$filterName = in_array($request->input('filter_name'), Filter::names()) ? $request->input('filter_name') : null;

		$photo = $request->file('file');

		$mimes = explode(',', config_cache('pixelfed.media_types'));
		if(in_array($photo->getMimeType(), $mimes) == false) {
			abort(403, 'Invalid or unsupported mime type.');
		}

		$storagePath = MediaPathService::get($user, 2);
		$path = $photo->store($storagePath);
		$hash = \hash_file('sha256', $photo);
		$license = null;
		$mime = $photo->getMimeType();

		// if($photo->getMimeType() == 'image/heic') {
		// 	abort_if(config('image.driver') !== 'imagick', 422, 'Invalid media type');
		// 	abort_if(!in_array('HEIC', \Imagick::queryformats()), 422, 'Unsupported media type');
		// 	$oldPath = $path;
		// 	$path = str_replace('.heic', '.jpg', $path);
		// 	$mime = 'image/jpeg';
		// 	\Image::make($photo)->save(storage_path("app/{$path}"));
		// 	@unlink(storage_path("app/{$oldPath}"));
		// }

		$settings = UserSetting::whereUserId($user->id)->first();

		if($settings && !empty($settings->compose_settings)) {
			$compose = $settings->compose_settings;

			if(isset($compose['default_license']) && $compose['default_license'] != 1) {
				$license = $compose['default_license'];
			}
		}

		abort_if(MediaBlocklistService::exists($hash) == true, 451);

		$media = new Media();
		$media->status_id = null;
		$media->profile_id = $profile->id;
		$media->user_id = $user->id;
		$media->media_path = $path;
		$media->original_sha256 = $hash;
		$media->size = $photo->getSize();
		$media->mime = $mime;
		$media->caption = $request->input('description');
		$media->filter_class = $filterClass;
		$media->filter_name = $filterName;
		if($license) {
			$media->license = $license;
		}
		$media->save();

		switch ($media->mime) {
			case 'image/jpeg':
			case 'image/png':
				ImageOptimize::dispatch($media);
				break;

			case 'video/mp4':
				VideoThumbnail::dispatch($media);
				$preview_url = '/storage/no-preview.png';
				$url = '/storage/no-preview.png';
				break;
		}

		Cache::forget($limitKey);
		$resource = new Fractal\Resource\Item($media, new MediaTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		$res['preview_url'] = $media->url(). '?cb=1&_v=' . time();
		$res['url'] = $media->url(). '?cb=1&_v=' . time();
		return response()->json($res);
	}

	/**
	 * PUT /api/v1/media/{id}
	 *
	 * @param  integer  $id
	 *
	 * @return MediaTransformer
	 */
	public function mediaUpdate(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
		  'description' => 'nullable|string|max:' . config_cache('pixelfed.max_altext_length')
		]);

		$user = $request->user();

		$media = Media::whereUserId($user->id)
			->whereNull('status_id')
			->findOrFail($id);

		$media->caption = $request->input('description');
		$media->save();

		$resource = new Fractal\Resource\Item($media, new MediaTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		$res['preview_url'] = url('/storage/no-preview.png');
		$res['url'] = url('/storage/no-preview.png');
		return response()->json($res);
	}

	/**
	 * GET /api/v1/mutes
	 *
	 *
	 * @return AccountTransformer
	 */
	public function accountMutes(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'limit' => 'nullable|integer|min:1|max:40'
		]);

		$user = $request->user();
		$limit = $request->input('limit') ?? 40;

		$mutes = UserFilter::whereUserId($user->profile_id)
			->whereFilterableType('App\Profile')
			->whereFilterType('mute')
			->simplePaginate($limit)
			->pluck('filterable_id');

		$accounts = Profile::find($mutes);

		$resource = new Fractal\Resource\Collection($accounts, new AccountTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		return response()->json($res);
	}

	/**
	 * POST /api/v1/accounts/{id}/mute
	 *
	 * @param  integer  $id
	 *
	 * @return RelationshipTransformer
	 */
	public function accountMuteById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();
		$pid = $user->profile_id;

		$account = Profile::findOrFail($id);

		$filter = UserFilter::firstOrCreate([
			'user_id'         => $pid,
			'filterable_id'   => $account->id,
			'filterable_type' => 'App\Profile',
			'filter_type'     => 'mute',
		]);

		Cache::forget("user:filter:list:$pid");
		Cache::forget("feature:discover:posts:$pid");
		Cache::forget("api:local:exp:rec:$pid");

		$resource = new Fractal\Resource\Item($account, new RelationshipTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		return response()->json($res);
	}

	/**
	 * POST /api/v1/accounts/{id}/unmute
	 *
	 * @param  integer  $id
	 *
	 * @return RelationshipTransformer
	 */
	public function accountUnmuteById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();
		$pid = $user->profile_id;

		$account = Profile::findOrFail($id);

		$filter = UserFilter::whereUserId($pid)
			->whereFilterableId($account->id)
			->whereFilterableType('App\Profile')
			->whereFilterType('mute')
			->first();

		if($filter) {
			$filter->delete();
			Cache::forget("user:filter:list:$pid");
			Cache::forget("feature:discover:posts:$pid");
			Cache::forget("api:local:exp:rec:$pid");
		}

		$resource = new Fractal\Resource\Item($account, new RelationshipTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		return response()->json($res);
	}

	/**
	 * GET /api/v1/notifications
	 *
	 *
	 * @return NotificationTransformer
	 */
	public function accountNotifications(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'limit' => 'nullable|integer|min:1|max:80',
			'min_id' => 'nullable|integer|min:1|max:'.PHP_INT_MAX,
			'max_id' => 'nullable|integer|min:1|max:'.PHP_INT_MAX,
			'since_id' => 'nullable|integer|min:1|max:'.PHP_INT_MAX,
		]);

		$pid = $request->user()->profile_id;
		$limit = $request->input('limit', 20);

		$since = $request->input('since_id');
		$min = $request->input('min_id');
		$max = $request->input('max_id');

		if(!$since && !$min && !$max) {
			$min = 1;
		}

		$maxId = null;
		$minId = null;

		if($max) {
			$res = NotificationService::getMax($pid, $max, $limit);
			$ids = NotificationService::getRankedMaxId($pid, $max, $limit);
			if(!empty($ids)) {
				$maxId = max($ids);
				$minId = min($ids);
			}
		} else {
			$res = NotificationService::getMin($pid, $min ?? $since, $limit);
			$ids = NotificationService::getRankedMinId($pid, $min ?? $since, $limit);
			if(!empty($ids)) {
				$maxId = max($ids);
				$minId = min($ids);
			}
		}

		if(empty($res) && !Cache::has('pf:services:notifications:hasSynced:'.$pid)) {
			Cache::put('pf:services:notifications:hasSynced:'.$pid, 1, 1209600);
			NotificationService::warmCache($pid, 400, true);
		}

		$baseUrl = config('app.url') . '/api/v1/notifications?limit=' . $limit . '&';

		if($minId == $maxId) {
			$minId = null;
		}

		if($maxId) {
			$link = '<'.$baseUrl.'max_id='.$maxId.'>; rel="next"';
		}

		if($minId) {
			$link = '<'.$baseUrl.'min_id='.$minId.'>; rel="prev"';
		}

		if($maxId && $minId) {
			$link = '<'.$baseUrl.'max_id='.$maxId.'>; rel="next",<'.$baseUrl.'min_id='.$minId.'>; rel="prev"';
		}

		$res = response()->json($res);

		if(isset($link)) {
			$res->withHeaders([
				'Link' => $link,
			]);
		}

		return $res;
	}

	/**
	 * GET /api/v1/timelines/home
	 *
	 *
	 * @return StatusTransformer
	 */
	public function timelineHome(Request $request)
	{
		$this->validate($request,[
		  'page'        => 'nullable|integer|max:40',
		  'min_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
		  'max_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
		  'limit'       => 'nullable|integer|max:80'
		]);

		$page = $request->input('page');
		$min = $request->input('min_id');
		$max = $request->input('max_id');
		$limit = $request->input('limit') ?? 3;
		$pid = $request->user()->profile_id;

		$following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
			$following = Follower::whereProfileId($pid)->pluck('following_id');
			return $following->push($pid)->toArray();
		});

		if($min || $max) {
			$dir = $min ? '>' : '<';
			$id = $min ?? $max;
			$res = Status::select(
				'id',
				'profile_id',
				'type',
				'visibility',
				'created_at'
			)
			->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
			->where('id', $dir, $id)
			->whereIn('profile_id', $following)
			->whereIn('visibility',['public', 'unlisted', 'private'])
			->latest()
			->take($limit)
			->get()
			->map(function($s) use($pid) {
				$status = StatusService::getMastodon($s['id']);
				if(!$status || !isset($status['account']) || !isset($status['account']['id'])) {
					return false;
				}

				if($pid) {
					$status['favourited'] = (bool) LikeService::liked($pid, $s['id']);
					$status['reblogged'] = (bool) ReblogService::get($pid, $status['id']);
				}
				return $status;
			})
			->filter(function($status) {
				return $status && isset($status['account']);
			})
			->values()
			->toArray();
		} else {
			$res = Status::select(
				'id',
				'profile_id',
				'type',
				'visibility',
				'created_at'
			)
			->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
			->whereIn('profile_id', $following)
			->whereIn('visibility',['public', 'unlisted', 'private'])
			->latest()
			->take($limit)
			->get()
			->map(function($s) use($pid) {
				$status = StatusService::getMastodon($s['id']);
				if(!$status || !isset($status['account']) || !isset($status['account']['id'])) {
					return false;
				}

				if($pid) {
					$status['favourited'] = (bool) LikeService::liked($pid, $s['id']);
					$status['reblogged'] = (bool) ReblogService::get($pid, $status['id']);
				}
				return $status;
			})
			->filter(function($status) {
				return $status && isset($status['account']);
			})
			->values()
			->toArray();
		}

		return response()->json($res);
	}

	/**
	 * GET /api/v1/conversations
	 *
	 *   Not implemented
	 *
	 * @return array
	 */
	public function conversations(Request $request)
	{
		abort_if(!$request->user(), 403);
		$this->validate($request, [
			'limit' => 'min:1|max:40',
			'scope' => 'nullable|in:inbox,sent,requests'
		]);

		$limit = $request->input('limit', 20);
		$scope = $request->input('scope', 'inbox');
		$pid = $request->user()->profile_id;

		if(config('database.default') == 'pgsql') {
			$dms = DirectMessage::when($scope === 'inbox', function($q, $scope) use($pid) {
					return $q->whereIsHidden(false)->whereToId($pid)->orWhere('from_id', $pid);
				})
				->when($scope === 'sent', function($q, $scope) use($pid) {
					return $q->whereFromId($pid);
				})
				->when($scope === 'requests', function($q, $scope) use($pid) {
					return $q->whereToId($pid)->whereIsHidden(true);
				});
		} else {
			$dms = DirectMessage::when($scope === 'inbox', function($q, $scope) use($pid) {
					return $q->whereIsHidden(false)->whereToId($pid)->orWhere('from_id', $pid)->groupBy('to_id');
				})
				->when($scope === 'sent', function($q, $scope) use($pid) {
					return $q->whereFromId($pid)->groupBy('to_id');
				})
				->when($scope === 'requests', function($q, $scope) use($pid) {
					return $q->whereToId($pid)->whereIsHidden(true);
				});
		}

		$dms = $dms->latest()
			->simplePaginate($limit)
			->map(function($dm) use($pid) {
				$from = $pid == $dm->to_id ? $dm->from_id : $dm->to_id;
				$res = [
					'id' => $dm->id,
					'unread' => false,
					'accounts' => [
						AccountService::getMastodon($from)
					],
					'last_status' => StatusService::getDirectMessage($dm->status_id)
				];
				return $res;
			})
			->filter(function($dm) {
				return isset($dm['accounts']) && count($dm['accounts']);
			})
			->unique(function($item, $key) {
				return $item['accounts'][0]['id'];
			})
			->values();

		return response()->json($dms);
	}

	/**
	 * GET /api/v1/timelines/public
	 *
	 *
	 * @return StatusTransformer
	 */
	public function timelinePublic(Request $request)
	{
		$this->validate($request,[
		  'min_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
		  'max_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
		  'limit'       => 'nullable|integer|max:80'
		]);

		$min = $request->input('min_id');
		$max = $request->input('max_id');
		$limit = $request->input('limit') ?? 3;
		$user = $request->user();
        $filtered = $user ? UserFilterService::filters($user->profile_id) : [];

		Cache::remember('api:v1:timelines:public:cache_check', 10368000, function() {
			if(PublicTimelineService::count() == 0) {
				PublicTimelineService::warmCache(true, 400);
			}
		});

		if ($max) {
			$feed = PublicTimelineService::getRankedMaxId($max, $limit);
		} else if ($min) {
			$feed = PublicTimelineService::getRankedMinId($min, $limit);
		} else {
			$feed = PublicTimelineService::get(0, $limit);
		}

		$res = collect($feed)
		->map(function($k) use($user) {
			$status = StatusService::getMastodon($k);
			if(!$status || !isset($status['account']) || !isset($status['account']['id'])) {
				return false;
			}

			if($user) {
				$status['favourited'] = (bool) LikeService::liked($user->profile_id, $k);
				$status['reblogged'] = (bool) ReblogService::get($user->profile_id, $status['id']);
			}
			return $status;
		})
		->filter(function($s) use($filtered) {
			return $s && isset($s['account']) && in_array($s['account']['id'], $filtered) == false;
		})
		->values()
		->toArray();
		return response()->json($res);
	}

	/**
	 * GET /api/v1/statuses/{id}
	 *
	 * @param  integer  $id
	 *
	 * @return StatusTransformer
	 */
	public function statusById(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();

		$res = StatusService::getMastodon($id, false);
		if(!$res || !isset($res['visibility'])) {
			abort(404);
		}

		$scope = $res['visibility'];
		if(!in_array($scope, ['public', 'unlisted'])) {
			if($scope === 'private') {
				if($res['account']['id'] != $user->profile_id) {
					abort_unless(FollowerService::follows($user->profile_id, $res['account']['id']), 403);
				}
			} else {
				abort(400, 'Invalid request');
			}
		}

		$res['favourited'] = LikeService::liked($user->profile_id, $res['id']);
		$res['reblogged'] = ReblogService::get($user->profile_id, $res['id']);
		return response()->json($res);
	}

	/**
	 * GET /api/v1/statuses/{id}/context
	 *
	 * @param  integer  $id
	 *
	 * @return StatusTransformer
	 */
	public function statusContext(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();

		$status = Status::findOrFail($id);

		if($status->profile_id !== $user->profile_id) {
			if($status->scope == 'private') {
				abort_if(!$status->profile->followedBy($user->profile), 403);
			} else {
				abort_if(!in_array($status->scope, ['public','unlisted']), 403);
			}
		}

		if($status->comments_disabled) {
			$res = [
				'ancestors' => [],
				'descendants' => []
			];
		} else {
			$ancestors = $status->parent();
			if($ancestors) {
				$ares = new Fractal\Resource\Item($ancestors, new StatusTransformer());
				$ancestors = [
					$this->fractal->createData($ares)->toArray()
				];
			} else {
				$ancestors = [];
			}
			$descendants = Status::whereInReplyToId($id)->latest()->limit(20)->get();
			$dres = new Fractal\Resource\Collection($descendants, new StatusTransformer());
			$descendants = $this->fractal->createData($dres)->toArray();
			$res = [
				'ancestors' => $ancestors,
				'descendants' => $descendants
			];
		}

		return response()->json($res);
	}

	/**
	 * GET /api/v1/statuses/{id}/card
	 *
	 * @param  integer  $id
	 *
	 * @return StatusTransformer
	 */
	public function statusCard(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();

		$status = Status::findOrFail($id);

		if($status->profile_id !== $user->profile_id) {
			if($status->scope == 'private') {
				abort_if(!$status->profile->followedBy($user->profile), 403);
			} else {
				abort_if(!in_array($status->scope, ['public','unlisted']), 403);
			}
		}

		// Return empty response since we don't handle support cards
		$res = [];

		return response()->json($res);
	}

	/**
	 * GET /api/v1/statuses/{id}/reblogged_by
	 *
	 * @param  integer  $id
	 *
	 * @return AccountTransformer
	 */
	public function statusRebloggedBy(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'page'  => 'nullable|integer|min:1|max:40',
			'limit' => 'nullable|integer|min:1|max:80'
		]);

		$limit = $request->input('limit') ?? 40;
		$user = $request->user();
		$status = Status::findOrFail($id);

		if($status->profile_id !== $user->profile_id) {
			if($status->scope == 'private') {
				abort_if(!$status->profile->followedBy($user->profile), 403);
			} else {
				abort_if(!in_array($status->scope, ['public','unlisted']), 403);
			}
		}

		$shared = $status->sharedBy()->latest()->simplePaginate($limit);
		$resource = new Fractal\Resource\Collection($shared, new AccountTransformer());
		$res = $this->fractal->createData($resource)->toArray();

		$url = $request->url();
		$page = $request->input('page', 1);
		$next = $page < 40 ? $page + 1 : 40;
		$prev = $page > 1 ? $page - 1 : 1;
		$links = '<'.$url.'?page='.$next.'&limit='.$limit.'>; rel="next", <'.$url.'?page='.$prev.'&limit='.$limit.'>; rel="prev"';

		return response()->json($res, 200, ['Link' => $links]);
	}

	/**
	 * GET /api/v1/statuses/{id}/favourited_by
	 *
	 * @param  integer  $id
	 *
	 * @return AccountTransformer
	 */
	public function statusFavouritedBy(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'page'  => 'nullable|integer|min:1|max:40',
			'limit' => 'nullable|integer|min:1|max:80'
		]);

		$page = $request->input('page', 1);
		$limit = $request->input('limit') ?? 40;
		$user = $request->user();
		$status = Status::findOrFail($id);
		$offset = $page == 1 ? 0 : ($page * $limit - $limit);
		if($offset > 100) {
			if($user->profile_id != $status->profile_id) {
				return [];
			}
		}

		if($status->profile_id !== $user->profile_id) {
			if($status->scope == 'private') {
				abort_if(!$status->profile->followedBy($user->profile), 403);
			} else {
				abort_if(!in_array($status->scope, ['public','unlisted']), 403);
			}
		}

		$res = DB::table('likes')
			->select('likes.id', 'likes.profile_id', 'likes.status_id', 'followers.created_at')
			->leftJoin('followers', function($join) use($user, $status) {
				return $join->on('likes.profile_id', '=', 'followers.following_id')
					->where('followers.profile_id', $user->profile_id)
					->where('likes.status_id', $status->id);
			})
			->whereStatusId($status->id)
			->orderByDesc('followers.created_at')
			->offset($offset)
			->limit($limit)
			->get()
			->map(function($like) {
				$account = AccountService::getMastodon($like->profile_id);
				$account['follows'] = isset($like->created_at);
				return $account;
			})
			->filter(function($account) use($user) {
				return $account && isset($account['id']) && $account['id'] != $user->profile_id;
			})
			->values();

		$url = $request->url();
		$page = $request->input('page', 1);
		$next = $page < 40 ? $page + 1 : 40;
		$prev = $page > 1 ? $page - 1 : 1;
		$links = '<'.$url.'?page='.$next.'&limit='.$limit.'>; rel="next", <'.$url.'?page='.$prev.'&limit='.$limit.'>; rel="prev"';

		return response()->json($res, 200, ['Link' => $links]);
	}

	/**
	 * POST /api/v1/statuses
	 *
	 *
	 * @return StatusTransformer
	 */
	public function statusCreate(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'status' => 'nullable|string',
			'in_reply_to_id' => 'nullable|integer',
			'media_ids' => 'array|max:' . config_cache('pixelfed.max_album_length'),
			'media_ids.*' => 'integer|min:1',
			'sensitive' => 'nullable|boolean',
			'visibility' => 'string|in:private,unlisted,public',
		]);

		if(config('costar.enabled') == true) {
			$blockedKeywords = config('costar.keyword.block');
			if($blockedKeywords !== null && $request->status) {
				$keywords = config('costar.keyword.block');
				foreach($keywords as $kw) {
					if(Str::contains($request->status, $kw) == true) {
						abort(400, 'Invalid object. Contains banned keyword.');
					}
				}
			}
		}

		if(!$request->filled('media_ids') && !$request->filled('in_reply_to_id')) {
			abort(403, 'Empty statuses are not allowed');
		}

		$ids = $request->input('media_ids');
		$in_reply_to_id = $request->input('in_reply_to_id');
		$user = $request->user();
		$profile = $user->profile;

		$limitKey = 'compose:rate-limit:store:' . $user->id;
		$limitTtl = now()->addMinutes(15);
		$limitReached = Cache::remember($limitKey, $limitTtl, function() use($user) {
			$dailyLimit = Status::whereProfileId($user->profile_id)
				->whereNull('in_reply_to_id')
				->whereNull('reblog_of_id')
				->where('created_at', '>', now()->subDays(1))
				->count();

			return $dailyLimit >= 100;
		});

		abort_if($limitReached == true, 429);

		$visibility = $profile->is_private ? 'private' : (
			$profile->unlisted == true &&
			$request->input('visibility', 'public') == 'public' ?
			'unlisted' :
			$request->input('visibility', 'public'));

		if($user->last_active_at == null) {
			return [];
		}

		$content = strip_tags($request->input('status'));
		$rendered = Autolink::create()->autolink($content);

		if($in_reply_to_id) {
			$parent = Status::findOrFail($in_reply_to_id);

			$status = new Status;
			$status->caption = $content;
			$status->rendered = $rendered;
			$status->scope = $visibility;
			$status->visibility = $visibility;
			$status->profile_id = $user->profile_id;
			$status->is_nsfw = $user->profile->cw == true ? true : $request->input('sensitive', false);
			$status->in_reply_to_id = $parent->id;
			$status->in_reply_to_profile_id = $parent->profile_id;
			$status->save();
			StatusService::del($parent->id);
			Cache::forget('status:replies:all:' . $parent->id);
		}

		if($ids) {
			if(Media::whereUserId($user->id)
				->whereNull('status_id')
				->find($ids)
				->count() == 0
			) {
				abort(400, 'Invalid media_ids');
			}

			if(!$in_reply_to_id) {
				$status = new Status;
				$status->caption = $content;
				$status->rendered = $rendered;
				$status->profile_id = $user->profile_id;
				$status->scope = 'draft';
				$status->is_nsfw = $user->profile->cw == true ? true : $request->input('sensitive', false);
				$status->save();
			}

			$mimes = [];

			foreach($ids as $k => $v) {
				if($k + 1 > config_cache('pixelfed.max_album_length')) {
					continue;
				}
				$m = Media::whereUserId($user->id)->whereNull('status_id')->findOrFail($v);
				if($m->profile_id !== $user->profile_id || $m->status_id) {
					abort(403, 'Invalid media id');
				}
				$m->status_id = $status->id;
				$m->save();
				array_push($mimes, $m->mime);
			}

			if(empty($mimes)) {
				$status->delete();
				abort(400, 'Invalid media ids');
			}

			$status->scope = $visibility;
			$status->visibility = $visibility;
			$status->type = StatusController::mimeTypeCheck($mimes);
			$status->save();
		}

		if(!$status) {
			abort(500, 'An error occured.');
		}

		NewStatusPipeline::dispatch($status);
		if($status->in_reply_to_id) {
        	CommentPipeline::dispatch($parent, $status);
		}
		Cache::forget('user:account:id:'.$user->id);
		Cache::forget('_api:statuses:recent_9:'.$user->profile_id);
		Cache::forget('profile:status_count:'.$user->profile_id);
		Cache::forget($user->storageUsedKey());
		Cache::forget('profile:embed:' . $status->profile_id);
		Cache::forget($limitKey);

		$resource = new Fractal\Resource\Item($status, new StatusTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		return response()->json($res);
	}

	/**
	 * DELETE /api/v1/statuses
	 *
	 * @param  integer  $id
	 *
	 * @return null
	 */
	public function statusDelete(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$status = Status::whereProfileId($request->user()->profile->id)
		->findOrFail($id);

		$resource = new Fractal\Resource\Item($status, new StatusTransformer());

		Cache::forget('profile:status_count:'.$status->profile_id);
		StatusDelete::dispatch($status);

		$res = $this->fractal->createData($resource)->toArray();
		$res['text'] = $res['content'];
		unset($res['content']);
		return response()->json($res);
	}

	/**
	 * POST /api/v1/statuses/{id}/reblog
	 *
	 * @param  integer  $id
	 *
	 * @return StatusTransformer
	 */
	public function statusShare(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();
		$status = Status::whereScope('public')->findOrFail($id);

		if($status->profile_id !== $user->profile_id) {
			if($status->scope == 'private') {
				abort_if(!$status->profile->followedBy($user->profile), 403);
			} else {
				abort_if(!in_array($status->scope, ['public','unlisted']), 403);
			}
		}

		$share = Status::firstOrCreate([
			'profile_id' => $user->profile_id,
			'reblog_of_id' => $status->id,
			'in_reply_to_profile_id' => $status->profile_id,
			'scope' => 'public',
			'visibility' => 'public'
		]);

		if($share->wasRecentlyCreated == true) {
			SharePipeline::dispatch($share);
		}

		StatusService::del($status->id);
		ReblogService::add($user->profile_id, $status->id);
		$res = StatusService::getMastodon($status->id);
		$res['reblogged'] = true;

		return response()->json($res);
	}

	/**
	 * POST /api/v1/statuses/{id}/unreblog
	 *
	 * @param  integer  $id
	 *
	 * @return StatusTransformer
	 */
	public function statusUnshare(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$user = $request->user();
		$status = Status::whereScope('public')->findOrFail($id);

		if($status->profile_id !== $user->profile_id) {
			if($status->scope == 'private') {
				abort_if(!$status->profile->followedBy($user->profile), 403);
			} else {
				abort_if(!in_array($status->scope, ['public','unlisted']), 403);
			}
		}

		$reblog = Status::whereProfileId($user->profile_id)
		  ->whereReblogOfId($status->id)
		  ->first();

		if(!$reblog) {
			$resource = new Fractal\Resource\Item($status, new StatusTransformer());
			$res = $this->fractal->createData($resource)->toArray();
			return response()->json($res);
		}

		UndoSharePipeline::dispatch($reblog);
		ReblogService::del($user->profile_id, $status->id);

		$res = StatusService::getMastodon($status->id);
		$res['reblogged'] = true;
		return response()->json($res);
	}

	/**
	 * GET /api/v1/timelines/tag/{hashtag}
	 *
	 * @param  string  $hashtag
	 *
	 * @return StatusTransformer
	 */
	public function timelineHashtag(Request $request, $hashtag)
	{
		$this->validate($request,[
		  'page'        => 'nullable|integer|max:40',
		  'min_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
		  'max_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
		  'limit'       => 'nullable|integer|max:40'
		]);

		$tag = Hashtag::whereName($hashtag)
		  ->orWhere('slug', $hashtag)
		  ->first();

		if(!$tag) {
			return response()->json([]);
		}

		$min = $request->input('min_id');
		$max = $request->input('max_id');
		$limit = $request->input('limit', 20);

		if(!$min && !$max) {
			$id = 1;
			$dir = '>';
		} else {
			$dir = $min ? '>' : '<';
			$id = $min ?? $max;
		}

		$res = StatusHashtag::whereHashtagId($tag->id)
			->whereStatusVisibility('public')
			->whereHas('media')
			->where('status_id', $dir, $id)
			->latest()
			->limit($limit)
			->pluck('status_id')
			->filter(function($i) {
				return StatusService::getMastodon($i);
			})
			->map(function ($i) {
				return StatusService::getMastodon($i);
			})
			->filter()
			->values()
			->toArray();

		return response()->json($res, 200, [], JSON_PRETTY_PRINT);
	}

	/**
	 * GET /api/v1/bookmarks
	 *
	 *
	 *
	 * @return StatusTransformer
	 */
	public function bookmarks(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'limit' => 'nullable|integer|min:1|max:40',
			'max_id' => 'nullable|integer|min:0',
			'since_id' => 'nullable|integer|min:0',
			'min_id' => 'nullable|integer|min:0'
		]);

		$pid = $request->user()->profile_id;
		$limit = $request->input('limit') ?? 20;
		$max_id = $request->input('max_id');
		$since_id = $request->input('since_id');
		$min_id = $request->input('min_id');

		$dir = $min_id ? '>' : '<';
		$id = $min_id ?? $max_id;

		if($id) {
			$bookmarks = Bookmark::whereProfileId($pid)
				->where('status_id', $dir, $id)
				->limit($limit)
				->pluck('status_id');
		} else {
			$bookmarks = Bookmark::whereProfileId($pid)
				->latest()
				->limit($limit)
				->pluck('status_id');
		}

		$res = [];
		foreach($bookmarks as $id) {
			$res[] = \App\Services\StatusService::getMastodon($id);
		}
		return $res;
	}

	/**
	 * POST /api/v1/statuses/{id}/bookmark
	 *
	 *
	 *
	 * @return StatusTransformer
	 */
	public function bookmarkStatus(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$status = Status::whereNull('uri')
			->whereScope('public')
			->findOrFail($id);

		Bookmark::firstOrCreate([
			'status_id' => $status->id,
			'profile_id' => $request->user()->profile_id
		]);
		$resource = new Fractal\Resource\Item($status, new StatusTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		return response()->json($res);
	}

	/**
	 * POST /api/v1/statuses/{id}/unbookmark
	 *
	 *
	 *
	 * @return StatusTransformer
	 */
	public function unbookmarkStatus(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$status = Status::whereNull('uri')
			->whereScope('public')
			->findOrFail($id);

		Bookmark::firstOrCreate([
			'status_id' => $status->id,
			'profile_id' => $request->user()->profile_id
		]);
		$bookmark = Bookmark::whereStatusId($status->id)
			->whereProfileId($request->user()->profile_id)
			->firstOrFail();
		$bookmark->delete();

		$resource = new Fractal\Resource\Item($status, new StatusTransformer());
		$res = $this->fractal->createData($resource)->toArray();
		return response()->json($res);
	}

	/**
	 * GET /api/v2/search
	 *
	 *
	 * @return array
	 */
	public function searchV2(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'q' => 'required|string|min:1|max:80',
			'account_id' => 'nullable|string',
			'max_id' => 'nullable|string',
			'min_id' => 'nullable|string',
			'type' => 'nullable|in:accounts,hashtags,statuses',
			'exclude_unreviewed' => 'nullable',
			'resolve' => 'nullable',
			'limit' => 'nullable|integer|max:40',
			'offset' => 'nullable|integer',
			'following' => 'nullable'
		]);

		return SearchApiV2Service::query($request);
	}

	/**
	 * GET /api/v1/discover/posts
	 *
	 *
	 * @return array
	 */
	public function discoverPosts(Request $request)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'limit' => 'integer|min:1|max:40'
		]);

		$limit = $request->input('limit', 40);
		$pid = $request->user()->profile_id;
		$filters = UserFilterService::filters($pid);
		$forYou = DiscoverService::getForYou();
		$posts = $forYou->take(50)->map(function($post) {
			return StatusService::getMastodon($post);
		})
		->filter(function($post) use($filters) {
			return $post &&
				isset($post['account']) &&
				isset($post['account']['id']) &&
				!in_array($post['account']['id'], $filters);
		})
		->take(12)
		->values();
		return response()->json(compact('posts'));
	}

	/**
	* GET /api/v2/statuses/{id}/replies
	*
	*
	* @return array
	*/
	public function statusReplies(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$this->validate($request, [
			'limit' => 'int|min:1|max:10',
			'sort' => 'in:all,newest,popular'
		]);

		$limit = $request->input('limit', 3);
		$pid = $request->user()->profile_id;
		$status = StatusService::getMastodon($id, false);

		abort_if(!$status, 404);

		if($status['visibility'] == 'private') {
			if($pid != $status['account']['id']) {
				abort_unless(FollowerService::follows($pid, $status['account']['id']), 404);
			}
		}

		$sortBy = $request->input('sort', 'all');

		if($sortBy == 'all' && $status['replies_count'] && $request->has('refresh_cache')) {
			if(!Cache::has('status:replies:all-rc:' . $id)) {
				Cache::forget('status:replies:all:' . $id);
				Cache::put('status:replies:all-rc:' . $id, true, 300);
			}
		}

		if($sortBy == 'all' && !$request->has('cursor')) {
			$ids = Cache::remember('status:replies:all:' . $id, 86400, function() use($id) {
				return DB::table('statuses')
					->where('in_reply_to_id', $id)
					->orderBy('id')
					->cursorPaginate(3);
			});
		} else {
			$ids = DB::table('statuses')
				->where('in_reply_to_id', $id)
				->when($sortBy, function($q, $sortBy) {
					if($sortBy === 'all') {
						return $q->orderBy('id');
					}

					if($sortBy === 'newest') {
						return $q->orderByDesc('created_at');
					}

					if($sortBy === 'popular') {
						return $q->orderByDesc('likes_count');
					}
				})
				->cursorPaginate($limit);
		}

		$data = $ids->map(function($post) use($pid) {
			$status = StatusService::get($post->id);

			if(!$status || !isset($status['id'])) {
				return false;
			}

			$status['favourited'] = LikeService::liked($pid, $post->id);
			return $status;
		})
		->filter(function($post) {
			return $post && isset($post['id']) && isset($post['account']);
		})
		->values();

		$res = [
			'data' => $data,
			'next' => $ids->nextPageUrl()
		];

		return $res;
	}

	/**
	* GET /api/v2/statuses/{id}/state
	*
	*
	* @return array
	*/
	public function statusState(Request $request, $id)
	{
		abort_if(!$request->user(), 403);

		$status = Status::findOrFail($id);
		$pid = $request->user()->profile_id;
		abort_if(!in_array($status->scope, ['public', 'unlisted', 'private']), 404);

		return StatusService::getState($status->id, $pid);
	}

   /**
	* GET /api/v1/discover/accounts/popular
	*
	*
	* @return array
	*/
	public function discoverAccountsPopular(Request $request)
	{
		abort_if(!$request->user(), 403);
		$pid = $request->user()->profile_id;

		$ids = DB::table('profiles')
			->where('is_private', false)
			->whereNull('status')
			->orderByDesc('profiles.followers_count')
			->limit(20)
			->get();

		$ids = $ids->map(function($profile) {
				return AccountService::getMastodon($profile->id);
			})
			->filter(function($profile) use($pid) {
				return $profile &&
					isset($profile['id']) &&
					!FollowerService::follows($pid, $profile['id']) &&
					$profile['id'] != $pid;
			})
			->take(6)
			->values();

		return response()->json($ids, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
	}
}
