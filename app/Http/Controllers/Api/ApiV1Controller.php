<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Util\ActivityPub\Helpers;
use App\Jobs\LikePipeline\LikePipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Jobs\FollowPipeline\FollowPipeline;
use Laravel\Passport\Passport;
use Auth, Cache, DB;
use App\{
    Follower,
    FollowRequest,
    Like,
    Media,
    Notification,
    Profile,
    Status,
    UserFilter,
};
use League\Fractal;
use App\Transformer\Api\{
    AccountTransformer,
    RelationshipTransformer,
    StatusTransformer,
};
use App\Http\Controllers\FollowerController;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

use App\Services\NotificationService;

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
		abort_if(!config('pixelfed.oauth_enabled'), 404);

		$this->validate($request, [
			'client_name' 		=> 'required',
			'redirect_uris' 	=> 'required',
			'scopes' 			=> 'nullable',
			'website' 			=> 'nullable'
		]);

        $client = Passport::client()->forceFill([
            'user_id' => null,
            'name' => e($request->client_name),
            'secret' => Str::random(40),
            'redirect' => $request->redirect_uris,
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
        return $res;
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
		$profile = Profile::whereNull('status')->findOrFail($id);
		$resource = new Fractal\Resource\Item($profile, new AccountTransformer());
		$res = $this->fractal->createData($resource)->toArray();

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
            'display_name'      => 'nullable|string',
            'note'              => 'nullable|string',
            'locked'            => 'nullable|boolean',
            // 'source.privacy'    => 'nullable|in:unlisted,public,private',
            // 'source.sensitive'  => 'nullable|boolean'
        ]);

        $user = $request->user();
        $profile = $user->profile;

        $displayName = $request->input('display_name');
        $note = $request->input('note');
        $locked = $request->input('locked');
        // $privacy = $request->input('source.privacy');
        // $sensitive = $request->input('source.sensitive');

        $changes = false;

        if($displayName !== $user->name) {
            $user->name = $displayName;
            $profile->name = $displayName;
            $changes = true;
        }

        if($note !== $profile->bio) {
            $profile->bio = e($note);
            $changes = true;
        }

        if(!is_null($locked)) {
            $profile->is_private = $locked;
            $changes = true;
        }

        if($changes) {
            $user->save();
            $profile->save();
        }

        $resource = new Fractal\Resource\Item($profile, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

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
        $profile = Profile::whereNull('status')->findOrFail($id);

        $settings = $profile->user->settings;
        if($settings->show_profile_followers == true) {
            $limit = $request->input('limit') ?? 40;
            $followers = $profile->followers()->paginate($limit);
            $resource = new Fractal\Resource\Collection($followers, new AccountTransformer());
            $res = $this->fractal->createData($resource)->toArray();
        } else {
            $res = [];
        }
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
        $profile = Profile::whereNull('status')->findOrFail($id);

        $settings = $profile->user->settings;
        if($settings->show_profile_following == true) {
            $limit = $request->input('limit') ?? 40;
            $following = $profile->following()->paginate($limit);
            $resource = new Fractal\Resource\Collection($following, new AccountTransformer());
            $res = $this->fractal->createData($resource)->toArray();
        } else {
            $res = [];
        }
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
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'only_media' => 'nullable',
            'pinned' => 'nullable',
            'exclude_replies' => 'nullable',
            'max_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
            'since_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
            'min_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
            'limit' => 'nullable|integer|min:1|max:40'
        ]);

        $profile = Profile::whereNull('status')->findOrFail($id);

        $limit = $request->limit ?? 20;
        $max_id = $request->max_id;
        $min_id = $request->min_id;
        $pid = $request->user()->profile_id;
        $scope = $request->only_media == true ? 
            ['photo', 'photo:album', 'video', 'video:album'] :
            ['photo', 'photo:album', 'video', 'video:album', 'share', 'reply'];
       
        if($pid == $profile->id) {
            $visibility = ['public', 'unlisted', 'private'];
        } else if($profile->is_private) {
            $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
                $following = Follower::whereProfileId($pid)->pluck('following_id');
                return $following->push($pid)->toArray();
            });
            $visibility = true == in_array($profile->id, $following) ? ['public', 'unlisted', 'private'] : [];
        } else {
            $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
                $following = Follower::whereProfileId($pid)->pluck('following_id');
                return $following->push($pid)->toArray();
            });
            $visibility = true == in_array($profile->id, $following) ? ['public', 'unlisted', 'private'] : ['public', 'unlisted'];
        }

        if($min_id || $max_id) {
            $dir = $min_id ? '>' : '<';
            $id = $min_id ?? $max_id;
            $timeline = Status::select(
                'id', 
                'uri',
                'caption',
                'rendered',
                'profile_id', 
                'type',
                'in_reply_to_id',
                'reblog_of_id',
                'is_nsfw',
                'scope',
                'local',
                'place_id',
                'created_at',
                'updated_at'
              )->whereProfileId($profile->id)
              ->whereIn('type', $scope)
              ->where('id', $dir, $id)
              ->whereIn('visibility', $visibility)
              ->latest()
              ->limit($limit)
              ->get();
        } else {
            $timeline = Status::select(
                'id', 
                'uri',
                'caption',
                'rendered',
                'profile_id', 
                'type',
                'in_reply_to_id',
                'reblog_of_id',
                'is_nsfw',
                'scope',
                'local',
                'place_id',
                'created_at',
                'updated_at'
              )->whereProfileId($profile->id)
              ->whereIn('type', $scope)
              ->whereIn('visibility', $visibility)
              ->latest()
              ->limit($limit)
              ->get();
        }

        $resource = new Fractal\Resource\Collection($timeline, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();
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

        $target = Profile::where('id', '!=', $user->id)
            ->whereNull('status')
            ->findOrFail($item);

        $private = (bool) $target->is_private;
        $remote = (bool) $target->domain;
        $blocked = UserFilter::whereUserId($target->id)
                ->whereFilterType('block')
                ->whereFilterableId($user->id)
                ->whereFilterableType('App\Profile')
                ->exists();

        if($blocked == true) {
            abort(400, 'You cannot follow this user.');
        }

        $isFollowing = Follower::whereProfileId($user->id)
            ->whereFollowingId($target->id)
            ->exists();

        // Following already, return empty relationship
        if($isFollowing == true) {
            $resource = new Fractal\Resource\Item($target, new RelationshipTransformer());
            $res = $this->fractal->createData($resource)->toArray();

            return response()->json($res);
        }

        // Rate limits, max 7500 followers per account
        if($user->following()->count() >= Follower::MAX_FOLLOWING) {
            abort(400, 'You cannot follow more than ' . Follower::MAX_FOLLOWING . ' accounts');
        }

        // Rate limits, follow 30 accounts per hour max
        if($user->following()->where('followers.created_at', '>', now()->subHour())->count() >= Follower::FOLLOW_PER_HOUR) {
            abort(400, 'You can only follow ' . Follower::FOLLOW_PER_HOUR . ' users per hour');
        }

        if($private == true) {
            $follow = FollowRequest::firstOrCreate([
                'follower_id' => $user->id,
                'following_id' => $target->id
            ]);
            if($remote == true && config('federation.activitypub.remoteFollow') == true) {
                (new FollowerController())->sendFollow($user, $target);
            } 
        } else {
            $follower = new Follower();
            $follower->profile_id = $user->id;
            $follower->following_id = $target->id;
            $follower->save();

            if($remote == true && config('federation.activitypub.remoteFollow') == true) {
                (new FollowerController())->sendFollow($user, $target);
            } 
            FollowPipeline::dispatch($follower);
        } 

        Cache::forget('profile:following:'.$target->id);
        Cache::forget('profile:followers:'.$target->id);
        Cache::forget('profile:following:'.$user->id);
        Cache::forget('profile:followers:'.$user->id);
        Cache::forget('api:local:exp:rec:'.$user->id);
        Cache::forget('user:account:id:'.$target->user_id);
        Cache::forget('user:account:id:'.$user->user_id);

        $resource = new Fractal\Resource\Item($target, new RelationshipTransformer());
        $res = $this->fractal->createData($resource)->toArray();

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

        $target = Profile::where('id', '!=', $user->id)
            ->whereNull('status')
            ->findOrFail($item);

        $private = (bool) $target->is_private;
        $remote = (bool) $target->domain;

        $isFollowing = Follower::whereProfileId($user->id)
            ->whereFollowingId($target->id)
            ->exists();

        if($isFollowing == false) {
            $resource = new Fractal\Resource\Item($target, new RelationshipTransformer());
            $res = $this->fractal->createData($resource)->toArray();

            return response()->json($res);
        }

        // Rate limits, follow 30 accounts per hour max
        if($user->following()->where('followers.updated_at', '>', now()->subHour())->count() >= Follower::FOLLOW_PER_HOUR) {
            abort(400, 'You can only follow or unfollow ' . Follower::FOLLOW_PER_HOUR . ' users per hour');
        }

        FollowRequest::whereFollowerId($user->id)
            ->whereFollowingId($target->id)
            ->delete(); 

        Follower::whereProfileId($user->id)
            ->whereFollowingId($target->id)
            ->delete();

        if($remote == true && config('federation.activitypub.remoteFollow') == true) {
            (new FollowerController())->sendUndoFollow($user, $target);
        } 

        Cache::forget('profile:following:'.$target->id);
        Cache::forget('profile:followers:'.$target->id);
        Cache::forget('profile:following:'.$user->id);
        Cache::forget('profile:followers:'.$user->id);
        Cache::forget('api:local:exp:rec:'.$user->id);
        Cache::forget('user:account:id:'.$target->user_id);
        Cache::forget('user:account:id:'.$user->user_id);

        $resource = new Fractal\Resource\Item($target, new RelationshipTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    /**
     * GET /api/v1/accounts/relationships
     *
     * @param  array|integer  $id
     *
     * @return \App\Transformer\Api\RelationshipTransformer
     */
    public function accountRelationshipsById(Request $request)
    {
        abort_if(!$request->user(), 403);

        $this->validate($request, [
            'id'    => 'required|array|min:1|max:20',
            'id.*'  => 'required|integer|min:1|max:' . PHP_INT_MAX
        ]);
        $pid = $request->user()->profile_id ?? $request->user()->profile->id;
        $ids = collect($request->input('id'));
        $filtered = $ids->filter(function($v) use($pid) { 
            return $v != $pid;
        });
        $relations = Profile::whereNull('status')->findOrFail($filtered->values());
        $fractal = new Fractal\Resource\Collection($relations, new RelationshipTransformer());
        $res = $this->fractal->createData($fractal)->toArray();
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
        return response()->json([]);
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

        $user = $request->user();

        $limit = $request->input('limit') ?? 20;
        $favourites = Like::whereProfileId($user->profile_id)
            ->latest()
            ->simplePaginate($limit)
            ->pluck('status_id');

        $statuses = Status::findOrFail($favourites);
        $resource = new Fractal\Resource\Collection($statuses, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res);
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

        $like = Like::firstOrCreate([
            'profile_id' => $user->profile_id,
            'status_id' => $status->id
        ]);

        if($like->wasRecentlyCreated == true) {
            LikePipeline::dispatch($like);
        }

        $resource = new Fractal\Resource\Item($status, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res);
    }

    public function statusById(Request $request, $id)
    {
        $status = Status::whereVisibility('public')->findOrFail($id);
        $resource = new Fractal\Resource\Item($status, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function instance(Request $request)
    {
        $res = [
            'description' => 'Pixelfed - Photo sharing for everyone',
            'email' => config('instance.email'),
            'languages' => ['en'],
            'max_toot_chars' => config('pixelfed.max_caption_length'),
            'registrations' => config('pixelfed.open_registration'),
            'stats' => [
                'user_count' => 0,
                'status_count' => 0,
                'domain_count' => 0
            ],
            'thumbnail' => config('app.url') . '/img/pixelfed-icon-color.png',
            'title' => 'Pixelfed (' . config('pixelfed.domain.app') . ')',
            'uri' => config('app.url'),
            'urls' => [],
            'version' => '2.7.2 (compatible; Pixelfed ' . config('pixelfed.version') . ')'
        ];
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function filters(Request $request)
    {
        // Pixelfed does not yet support keyword filters
        return response()->json([]);
    }

    public function context(Request $request)
    {
        // todo
        $res = [
            'ancestors' => [],
            'descendants' => []
        ];

        return response()->json($res);
    }

    public function createStatus(Request $request)
    {
        abort_if(!$request->user(), 403);
        
        $this->validate($request, [
            'status' => 'string',
            'media_ids' => 'array',
            'media_ids.*' => 'integer|min:1',
            'sensitive' => 'nullable|boolean',
            'visibility' => 'string|in:private,unlisted,public',
            'in_reply_to_id' => 'integer'
        ]);

        if(!$request->filled('media_ids') && !$request->filled('in_reply_to_id')) {
            abort(403, 'Empty statuses are not allowed');
        }
    }
}