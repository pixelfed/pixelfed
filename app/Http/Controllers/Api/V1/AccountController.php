<?php

namespace App\Http\Controllers\Api\V1;

use App\Avatar;
use App\Follower;
use App\FollowRequest;
use App\Http\Controllers\Controller;
use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Jobs\FollowPipeline\FollowAcceptPipeline;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Jobs\FollowPipeline\FollowRejectPipeline;
use App\Jobs\FollowPipeline\UnfollowPipeline;
use App\Jobs\MediaPipeline\MediaSyncLicensePipeline;
use App\Profile;
use App\Services\AccountService;
use App\Services\BouncerService;
use App\Services\DiscoverService;
use App\Services\FollowerService;
use App\Services\InstanceService;
use App\Services\RelationshipService;
use App\Services\UserFilterService;
use App\Transformer\Api\Mastodon\v1\AccountTransformer;
use App\Transformer\Api\RelationshipTransformer;
use App\User;
use App\UserFilter;
use App\Util\Lexer\Autolink;
use App\Util\Localization\Localization;
use App\Util\Media\License;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class AccountController extends Controller
{
    protected $fractal;

    const PF_API_ENTITY_KEY = '_pe';

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }


    public function verifyCredentials(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();

        abort_if($user->status != null, 403);
        AccountService::setLastActive($user->id);

        $res = $request->has(self::PF_API_ENTITY_KEY) ? AccountService::get($user->profile_id) : AccountService::getMastodon($user->profile_id);

        $res['source'] = [
            'privacy' => $res['locked'] ? 'private' : 'public',
            'sensitive' => false,
            'language' => $user->language ?? 'en',
            'note' => strip_tags($res['note']),
            'fields' => [],
        ];

        if ($request->has(self::PF_API_ENTITY_KEY)) {
            $res['settings'] = AccountService::getAccountSettings($user->profile_id);
        }

        return $this->json($res);
    }


    public function accountById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $withInstanceMeta = $request->has('_wim');
        $res = $request->has(self::PF_API_ENTITY_KEY) ? AccountService::get($id, true) : AccountService::getMastodon($id, true);
        if (! $res) {
            return response()->json(['error' => 'Record not found'], 404);
        }
        if ($res && strpos($res['acct'], '@') != -1) {
            $domain = parse_url($res['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        return $this->json($res);
    }    /**

     * PATCH /api/v1/accounts/update_credentials
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountUpdateCredentials(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        if (config('pixelfed.bouncer.cloud_ips.ban_api')) {
            abort_if(BouncerService::checkIp($request->ip()), 404);
        }

        $this->validate($request, [
            'avatar' => 'sometimes|mimetypes:image/jpeg,image/jpg,image/png|max:'.config('pixelfed.max_avatar_size'),
            'display_name' => 'nullable|string|max:30',
            'note' => 'nullable|string|max:200',
            'locked' => 'nullable',
            'website' => 'nullable|string|max:120',
        ], [
            'required' => 'The :attribute field is required.',
            'avatar.mimetypes' => 'The file must be in jpeg or png format',
            'avatar.max' => 'The :attribute exceeds the file size limit of '.config('pixelfed.max_avatar_size'),
        ]);

        $user = $request->user();
        AccountService::setLastActive($user->id);
        $profile = $user->profile;
        $settings = $user->settings;

        $changes = false;
        $other = array_merge(AccountService::defaultSettings()['other'], $settings->other ?? []);
        $syncLicenses = false;
        $licenseChanged = false;
        $composeSettings = array_merge(AccountService::defaultSettings()['compose_settings'], $settings->compose_settings ?? []);

        if ($request->has('avatar')) {
            $av = Avatar::whereProfileId($profile->id)->first();
            if ($av) {
                $currentAvatar = storage_path('app/'.$av->media_path);
                $file = $request->file('avatar');
                $path = "public/avatars/{$profile->id}";
                $name = strtolower(str_random(6)).'.'.$file->guessExtension();
                $request->file('avatar')->storePubliclyAs($path, $name);
                $av->media_path = "{$path}/{$name}";
                $av->save();
                Cache::forget("avatar:{$profile->id}");
                Cache::forget('user:account:id:'.$user->id);
                AvatarOptimize::dispatch($user->profile, $currentAvatar);
            }
            $changes = true;
        }

        if ($request->has('source[language]')) {
            $lang = $request->input('source[language]');
            if (in_array($lang, Localization::languages())) {
                $user->language = $lang;
                $changes = true;
                $other['language'] = $lang;
            }
        }

        if ($request->has('website')) {
            $website = $request->input('website');
            if ($website != $profile->website) {
                if ($website) {
                    if (! strpos($website, '.')) {
                        $website = null;
                    }

                    if ($website && ! strpos($website, '://')) {
                        $website = 'https://'.$website;
                    }

                    $host = parse_url($website, PHP_URL_HOST);

                    $bannedInstances = InstanceService::getBannedDomains();
                    if (in_array($host, $bannedInstances)) {
                        $website = null;
                    }
                }
                $profile->website = $website ? $website : null;
                $changes = true;
            }
        }

        if ($request->has('display_name')) {
            $displayName = $request->input('display_name');
            if ($displayName !== $user->name) {
                $user->name = $displayName;
                $profile->name = $displayName;
                $changes = true;
            }
        }

        if ($request->has('note')) {
            $note = $request->input('note');
            if ($note !== strip_tags($profile->bio)) {
                $profile->bio = Autolink::create()->autolink(strip_tags($note));
                $changes = true;
            }
        }

        if ($request->has('locked')) {
            $locked = $request->boolean('locked');
            if ($profile->is_private != $locked) {
                $profile->is_private = $locked;
                $changes = true;
            }
        }

        if ($changes) {
            $settings->other = $other;
            $settings->compose_settings = $composeSettings;
            $settings->save();
            $user->save();
            $profile->save();
            Cache::forget('profile:settings:'.$profile->id);
            Cache::forget('user:account:id:'.$profile->user_id);
            AccountService::del($user->profile_id);
            AccountService::forgetAccountSettings($profile->id);
        }

        if ($request->has(self::PF_API_ENTITY_KEY)) {
            $res = AccountService::get($user->profile_id, true);
        } else {
            $res = AccountService::getMastodon($user->profile_id, true);
            $res['bio'] = strip_tags($res['note']);
            $res = array_merge($res, $other);
        }

        return $this->json($res);
    }   
 /**
     * GET /api/v1/accounts/relationships
     *
     * @return \App\Services\RelationshipService
     */
    public function accountRelationshipsById(Request $request)
    {
        abort_if(! $request->user(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'id' => 'required|array|min:1|max:20',
            'id.*' => 'required|integer',
        ]);
        $pid = $request->user()->profile_id ?? $request->user()->profile->id;
        $ids = collect($request->input('id'));
        $res = $ids->filter(function ($v) use ($pid) {
            return $v != $pid;
        })
            ->map(function ($id) use ($pid) {
                return RelationshipService::get($pid, $id);
            });

        return $this->json($res);
    }

    /**
     * GET /api/v1/accounts/search
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountSearch(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'q' => 'required|string|min:1|max:50',
            'limit' => 'nullable|integer|min:1|max:40',
            'resolve' => 'nullable',
        ]);

        $user = $request->user();
        $query = $request->input('q');
        $limit = $request->input('limit') ?? 20;
        $resolve = $request->boolean('resolve', false);
        $q = '%'.$query.'%';

        $profiles = Profile::whereNull('status')
            ->where('username', 'like', $q)
            ->orWhere('name', 'like', $q)
            ->limit($limit)
            ->get();

        $resource = new Fractal\Resource\Collection($profiles, new AccountTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * POST /api/v1/accounts/{id}/follow
     *
     * @param  int  $id
     * @return \App\Transformer\Api\RelationshipTransformer
     */
    public function accountFollowById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        $target = Profile::where('id', '!=', $user->profile_id)->whereNull('status')->findOrFail($id);

        $private = (bool) $target->is_private;
        $remote = (bool) $target->domain;
        $blocked = UserFilter::whereUserId($target->id)
            ->whereFilterType('block')
            ->whereFilterableId($user->profile_id)
            ->whereFilterableType('App\Profile')
            ->exists();

        if ($blocked == true) {
            abort(400, 'You cannot follow this user.');
        }

        $isFollowing = Follower::whereProfileId($user->profile_id)->whereFollowingId($target->id)->exists();
        $isRequested = FollowRequest::whereFollowerId($user->profile_id)->whereFollowingId($target->id)->exists();

        if ($remote == true && config('federation.activitypub.enabled') == true) {
            (new FollowerController())->sendFollow($user->profile, $target);
        }

        if ($isFollowing == true) {
            $follower = Follower::whereProfileId($user->profile_id)->whereFollowingId($target->id)->first();
            $resource = new Fractal\Resource\Item($follower, new RelationshipTransformer);
            $res = $this->fractal->createData($resource)->toArray();

            return $this->json($res);
        }

        if ($isRequested == true) {
            $follower = FollowRequest::whereFollowerId($user->profile_id)->whereFollowingId($target->id)->first();
            $resource = new Fractal\Resource\Item($follower, new RelationshipTransformer);
            $res = $this->fractal->createData($resource)->toArray();

            return $this->json($res);
        }

        if ($private == true) {
            $follower = new FollowRequest;
            $follower->follower_id = $user->profile_id;
            $follower->following_id = $target->id;
            $follower->save();

            FollowPipeline::dispatch($follower);

            $resource = new Fractal\Resource\Item($follower, new RelationshipTransformer);
            $res = $this->fractal->createData($resource)->toArray();

            return $this->json($res);
        } else {
            $follower = new Follower;
            $follower->profile_id = $user->profile_id;
            $follower->following_id = $target->id;
            $follower->save();

            FollowPipeline::dispatch($follower);

            $resource = new Fractal\Resource\Item($follower, new RelationshipTransformer);
            $res = $this->fractal->createData($resource)->toArray();

            return $this->json($res);
        }
    } 
   /**
     * POST /api/v1/accounts/{id}/unfollow
     *
     * @param  int  $id
     * @return \App\Transformer\Api\RelationshipTransformer
     */
    public function accountUnfollowById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        $target = Profile::where('id', '!=', $user->profile_id)->whereNull('status')->findOrFail($id);

        $isFollowing = Follower::whereProfileId($user->profile_id)->whereFollowingId($target->id)->exists();
        $isRequested = FollowRequest::whereFollowerId($user->profile_id)->whereFollowingId($target->id)->exists();

        if ($isFollowing == true) {
            $follower = Follower::whereProfileId($user->profile_id)->whereFollowingId($target->id)->first();
            if ($follower) {
                $follower->delete();
                UnfollowPipeline::dispatch($user->profile_id, $target->id);
            }
        }

        if ($isRequested == true) {
            $follower = FollowRequest::whereFollowerId($user->profile_id)->whereFollowingId($target->id)->first();
            if ($follower) {
                $follower->delete();
            }
        }

        $resource = new Fractal\Resource\Item($target, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * GET /api/v1/blocks
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountBlocks(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
            'max_id' => 'nullable|integer|min:1',
            'since_id' => 'nullable|integer|min:1',
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 40;
        $max_id = $request->input('max_id');
        $since_id = $request->input('since_id');

        $blocks = UserFilter::select('filterable_id')
            ->whereUserId($user->profile_id)
            ->whereFilterableType('App\Profile')
            ->whereFilterType('block')
            ->when($max_id, function ($query, $max_id) {
                return $query->where('id', '<', $max_id);
            })
            ->when($since_id, function ($query, $since_id) {
                return $query->where('id', '>', $since_id);
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('filterable_id')
            ->toArray();

        $profiles = Profile::find($blocks);
        $resource = new Fractal\Resource\Collection($profiles, new AccountTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * POST /api/v1/accounts/{id}/block
     *
     * @param  int  $id
     * @return \App\Transformer\Api\RelationshipTransformer
     */
    public function accountBlockById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        $target = Profile::where('id', '!=', $user->profile_id)->whereNull('status')->findOrFail($id);

        $exists = UserFilter::whereUserId($user->profile_id)
            ->whereFilterType('block')
            ->whereFilterableId($target->id)
            ->whereFilterableType('App\Profile')
            ->exists();

        if ($exists == false) {
            $filter = new UserFilter;
            $filter->user_id = $user->profile_id;
            $filter->filterable_id = $target->id;
            $filter->filterable_type = 'App\Profile';
            $filter->filter_type = 'block';
            $filter->save();

            $follower = Follower::whereProfileId($user->profile_id)->whereFollowingId($target->id)->first();
            if ($follower) {
                $follower->delete();
                UnfollowPipeline::dispatch($user->profile_id, $target->id);
            }

            $follower = Follower::whereProfileId($target->id)->whereFollowingId($user->profile_id)->first();
            if ($follower) {
                $follower->delete();
                UnfollowPipeline::dispatch($target->id, $user->profile_id);
            }
        }

        $resource = new Fractal\Resource\Item($target, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * POST /api/v1/accounts/{id}/unblock
     *
     * @param  int  $id
     * @return \App\Transformer\Api\RelationshipTransformer
     */
    public function accountUnblockById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        $target = Profile::where('id', '!=', $user->profile_id)->whereNull('status')->findOrFail($id);

        $filter = UserFilter::whereUserId($user->profile_id)
            ->whereFilterType('block')
            ->whereFilterableId($target->id)
            ->whereFilterableType('App\Profile')
            ->first();

        if ($filter) {
            $filter->delete();
        }

        $resource = new Fractal\Resource\Item($target, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }}
    /**
     * GET /api/v1/accounts/{id}/followers
     *
     * @param  int  $id
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountFollowersById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $account = AccountService::get($id);
        if (! $account) {
            return $this->json([]);
        }

        $followers = FollowerService::followersPaginate($id, 40);
        $res = collect($followers)
            ->map(function ($follower) {
                return AccountService::get($follower);
            })
            ->filter()
            ->values();

        return $this->json($res);
    }

    /**
     * GET /api/v1/accounts/{id}/following
     *
     * @param  int  $id
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountFollowingById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $account = AccountService::get($id);
        if (! $account) {
            return $this->json([]);
        }

        $following = FollowerService::followingPaginate($id, 40);
        $res = collect($following)
            ->map(function ($following) {
                return AccountService::get($following);
            })
            ->filter()
            ->values();

        return $this->json($res);
    }

    /**
     * GET /api/v1/accounts/{id}/statuses
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function accountStatusesById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'only_media' => 'nullable',
            'pinned' => 'nullable',
            'exclude_replies' => 'nullable',
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'since_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'limit' => 'nullable|integer|min:1|max:40',
        ]);

        $profile = AccountService::get($id);

        if (! $profile || ! isset($profile['id'])) {
            return $this->json([]);
        }

        $limit = $request->input('limit') ?? 20;
        $max_id = $request->input('max_id');
        $min_id = $request->input('min_id');
        $since_id = $request->input('since_id');
        $only_media = $request->input('only_media');
        $pinned = $request->input('pinned');
        $exclude_replies = $request->input('exclude_replies');

        if ($limit > 40) {
            $limit = 40;
        }

        $pid = $request->user()->profile_id;
        $scope = $profile['locked'] == true ? ['public', 'unlisted', 'private'] : ['public', 'unlisted'];

        if ($pid == $profile['id']) {
            $scope = ['public', 'unlisted', 'private'];
        }

        $path = $pid == $profile['id'] ? 'profile:statuses:'.$profile['id'] : 'profile:statuses:'.$profile['id'].':public';

        if ($min_id || $max_id) {
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
                'reply_count',
                'comments_disabled',
                'created_at',
                'updated_at'
            )->whereProfileId($profile['id'])
                ->whereIn('scope', $scope)
                ->where('id', $dir, $id)
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id')
                ->orderByDesc('id')
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
                'reply_count',
                'comments_disabled',
                'created_at',
                'updated_at'
            )->whereProfileId($profile['id'])
                ->whereIn('scope', $scope)
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id')
                ->orderByDesc('id')
                ->limit($limit)
                ->get();
        }

        $res = $timeline->map(function ($status) use ($request) {
            $s = StatusService::getMastodon($status->id, false);
            if ($s && $request->user()) {
                $s['favourited'] = (bool) LikeService::liked($request->user()->profile_id, $status->id);
                $s['reblogged'] = (bool) ReblogService::get($request->user()->profile_id, $status->id);
                $s['bookmarked'] = (bool) BookmarkService::get($request->user()->profile_id, $status->id);
            }

            return $s;
        })
            ->filter(function ($s) {
                return $s && isset($s['id']);
            })
            ->values();

        return $this->json($res);
    }  
  /**
     * POST /api/v1/accounts/{id}/remove_from_followers
     *
     * @param  int  $id
     * @return \App\Transformer\Api\RelationshipTransformer
     */
    public function accountRemoveFollowById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        $target = Profile::where('id', '!=', $user->profile_id)->whereNull('status')->findOrFail($id);

        $follower = Follower::whereProfileId($target->id)->whereFollowingId($user->profile_id)->first();
        if ($follower) {
            $follower->delete();
            UnfollowPipeline::dispatch($target->id, $user->profile_id);
        }

        $resource = new Fractal\Resource\Item($target, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * GET /api/v1/endorsements
     * POST /api/v1/accounts/{id}/pin
     * POST /api/v1/accounts/{id}/unpin
     *
     * @return null
     */
    public function accountEndorsements(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }

    /**
     * POST /api/v1/accounts/{id}/mute
     *
     * @param  int  $id
     * @return \App\Transformer\Api\RelationshipTransformer
     */
    public function accountMuteById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        $target = Profile::where('id', '!=', $user->profile_id)->whereNull('status')->findOrFail($id);

        $exists = UserFilter::whereUserId($user->profile_id)
            ->whereFilterType('mute')
            ->whereFilterableId($target->id)
            ->whereFilterableType('App\Profile')
            ->exists();

        if ($exists == false) {
            $filter = new UserFilter;
            $filter->user_id = $user->profile_id;
            $filter->filterable_id = $target->id;
            $filter->filterable_type = 'App\Profile';
            $filter->filter_type = 'mute';
            $filter->save();
        }

        $resource = new Fractal\Resource\Item($target, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * POST /api/v1/accounts/{id}/unmute
     *
     * @param  int  $id
     * @return \App\Transformer\Api\RelationshipTransformer
     */
    public function accountUnmuteById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        $target = Profile::where('id', '!=', $user->profile_id)->whereNull('status')->findOrFail($id);

        $filter = UserFilter::whereUserId($user->profile_id)
            ->whereFilterType('mute')
            ->whereFilterableId($target->id)
            ->whereFilterableType('App\Profile')
            ->first();

        if ($filter) {
            $filter->delete();
        }

        $resource = new Fractal\Resource\Item($target, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * GET /api/v1/mutes
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountMutes(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
            'max_id' => 'nullable|integer|min:1',
            'since_id' => 'nullable|integer|min:1',
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 40;
        $max_id = $request->input('max_id');
        $since_id = $request->input('since_id');

        $mutes = UserFilter::select('filterable_id')
            ->whereUserId($user->profile_id)
            ->whereFilterableType('App\Profile')
            ->whereFilterType('mute')
            ->when($max_id, function ($query, $max_id) {
                return $query->where('id', '<', $max_id);
            })
            ->when($since_id, function ($query, $since_id) {
                return $query->where('id', '>', $since_id);
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('filterable_id')
            ->toArray();

        $profiles = Profile::find($mutes);
        $resource = new Fractal\Resource\Collection($profiles, new AccountTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * GET /api/v1/favourites
     *
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function accountFavourites(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'since_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 20;
        $max_id = $request->input('max_id');
        $since_id = $request->input('since_id');
        $min_id = $request->input('min_id');

        $favourites = Like::whereProfileId($user->profile_id)
            ->when($max_id, function ($query, $max_id) {
                return $query->where('status_id', '<', $max_id);
            })
            ->when($since_id, function ($query, $since_id) {
                return $query->where('status_id', '>', $since_id);
            })
            ->when($min_id, function ($query, $min_id) {
                return $query->where('status_id', '>', $min_id);
            })
            ->orderByDesc('status_id')
            ->limit($limit)
            ->pluck('status_id');

        $res = $favourites->map(function ($id) use ($user) {
            $status = StatusService::getMastodon($id, false);
            if ($status && isset($status['account']) && isset($status['account']['id'])) {
                $status['favourited'] = true;
                $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $id);
                $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $id);
            }

            return $status;
        })
            ->filter(function ($s) {
                return $s && isset($s['id']);
            })
            ->values();

        return $this->json($res);
    }

    /**
     * GET /api/v1/follow_requests
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountFollowRequests(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
            'page' => 'nullable|integer|min:1|max:10',
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 40;

        $requests = FollowRequest::whereFollowingId($user->profile_id)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($follow) {
                return AccountService::getMastodon($follow->follower_id, true);
            })
            ->filter()
            ->values();

        return $this->json($requests);
    }

    /**
     * POST /api/v1/follow_requests/{id}/authorize
     *
     * @param  int  $id
     * @return null
     */
    public function accountFollowRequestAccept(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        $followRequest = FollowRequest::whereFollowingId($user->profile_id)->whereFollowerId($id)->first();

        if ($followRequest) {
            $follower = new Follower;
            $follower->profile_id = $followRequest->follower_id;
            $follower->following_id = $followRequest->following_id;
            $follower->save();

            FollowAcceptPipeline::dispatch($followRequest);

            $followRequest->delete();
        }

        return $this->json([]);
    }

    /**
     * POST /api/v1/follow_requests/{id}/reject
     *
     * @param  int  $id
     * @return null
     */
    public function accountFollowRequestReject(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        $followRequest = FollowRequest::whereFollowingId($user->profile_id)->whereFollowerId($id)->first();

        if ($followRequest) {
            FollowRejectPipeline::dispatch($followRequest);
            $followRequest->delete();
        }

        return $this->json([]);
    }

    /**
     * GET /api/v1/suggestions
     *
     * @return null
     */
    public function accountSuggestions(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }

    /**
     * GET /api/v1/lists
     * GET /api/v1/accounts/{id}/lists
     * GET /api/v1/lists/{id}/accounts
     *
     * @return null
     */
    public function accountLists(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }

    public function accountListsById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }

    /**
     * GET /api/v1/filters
     *
     * @return array
     */
    public function accountFilters(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }