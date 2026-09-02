<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Http\Controllers\FollowerController;
use App\Jobs\FollowPipeline\FollowAcceptPipeline;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Jobs\FollowPipeline\FollowRejectPipeline;
use App\Jobs\FollowPipeline\UnfollowPipeline;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\Profile;
use App\Models\UserFilter;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\InstanceService;
use App\Services\RelationshipService;
use App\Services\UserRoleService;
use App\Transformer\Api\AccountTransformer;
use App\Transformer\Api\RelationshipTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use League\Fractal;

trait AccountsRelationships
{
    /**
     * GET /api/v1/accounts/{id}/followers
     *
     * @param  int  $id
     * @return AccountTransformer
     */
    public function accountFollowersById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $account = AccountService::get($id);
        abort_if(! $account, 404);
        abort_if(isset($account['moved'], $account['moved']['id']), 404, 'Account moved');
        $pid = $request->user()->profile_id;
        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1',
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
        ]);
        $limit = $request->input('limit', 10);
        if ($limit > 80) {
            $limit = 80;
        }
        $max_id = $request->max_id;
        $min_id = $request->min_id;

        if (! $max_id && ! $min_id) {
            $min_id = 0;
        }
        $napi = $request->has(self::PF_API_ENTITY_KEY);

        if ($account && strpos($account['acct'], '@') != -1) {
            $domain = parse_url($account['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        if (intval($pid) !== intval($account['id'])) {
            if ($account['locked']) {
                if (! FollowerService::follows($pid, $account['id'])) {
                    return [];
                }
            }

            if (AccountService::hiddenFollowers($id)) {
                return [];
            }

            if ($request->has('page') && $request->user()->is_admin == false) {
                $page = (int) $request->input('page');
                if (($page * $limit) >= 100) {
                    return [];
                }
            }
        }
        $dir = $min_id !== null ? '>' : '<';
        $id = $min_id ?? $max_id;
        if ($request->has('page')) {
            $res = DB::table('followers')
                ->select('id', 'profile_id', 'following_id')
                ->whereFollowingId($account['id'])
                ->where('id', $dir, $id)
                ->orderByDesc('id')
                ->simplePaginate($limit)
                ->map(function ($follower) use ($napi) {
                    return $napi ? AccountService::get($follower->profile_id, true) : AccountService::getMastodon($follower->profile_id, true);
                })
                ->filter(function ($account) {
                    return $account && isset($account['id']);
                })
                ->values()
                ->toArray();

            return $this->json($res);
        }

        $paginator = DB::table('followers')
            ->select('id', 'profile_id', 'following_id')
            ->whereFollowingId($account['id'])
            ->where('id', $dir, $id)
            ->orderByDesc('id')
            ->cursorPaginate($limit)
            ->withQueryString();

        $link = null;

        if ($paginator->onFirstPage()) {
            if ($paginator->hasMorePages()) {
                $link = '<'.$paginator->nextPageUrl().'>; rel="prev"';
            }
        } else {
            if ($paginator->previousPageUrl()) {
                $link = '<'.$paginator->previousPageUrl().'>; rel="next"';
            }

            if ($paginator->hasMorePages()) {
                $link .= ($link ? ', ' : '').'<'.$paginator->nextPageUrl().'>; rel="prev"';
            }
        }

        $res = $paginator->map(function ($follower) use ($napi) {
            return $napi ? AccountService::get($follower->profile_id, true) : AccountService::getMastodon($follower->profile_id, true);
        })
            ->filter(function ($account) {
                return $account && isset($account['id']);
            })
            ->values()
            ->toArray();

        $headers = isset($link) ? ['Link' => $link] : [];

        return $this->json($res, 200, $headers);
    }

    /**
     * GET /api/v1/accounts/{id}/following
     *
     * @param  int  $id
     * @return AccountTransformer
     */
    public function accountFollowingById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $account = AccountService::get($id);
        abort_if(! $account, 404);
        abort_if(isset($account['moved'], $account['moved']['id']), 404, 'Account moved');
        $pid = $request->user()->profile_id;
        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1',
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
        ]);
        $limit = $request->input('limit', 10);
        if ($limit > 80) {
            $limit = 80;
        }
        $max_id = $request->max_id;
        $min_id = $request->min_id;

        if (! $max_id && ! $min_id) {
            $min_id = 0;
        }

        $napi = $request->has(self::PF_API_ENTITY_KEY);

        if ($account && strpos($account['acct'], '@') != -1) {
            $domain = parse_url($account['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        if (intval($pid) !== intval($account['id'])) {
            if ($account['locked']) {
                if (! FollowerService::follows($pid, $account['id'])) {
                    return [];
                }
            }

            if (AccountService::hiddenFollowing($id)) {
                return [];
            }

            if ($request->has('page') && $request->user()->is_admin == false) {
                $page = (int) $request->input('page');
                if (($page * $limit) >= 100) {
                    return [];
                }
            }
        }

        $dir = $min_id !== null ? '>' : '<';
        $id = $min_id ?? $max_id;
        if ($request->has('page')) {
            $res = DB::table('followers')
                ->select('id', 'profile_id', 'following_id')
                ->whereProfileId($account['id'])
                ->where('id', $dir, $id)
                ->orderByDesc('id')
                ->simplePaginate($limit)
                ->map(function ($follower) use ($napi) {
                    return $napi ? AccountService::get($follower->following_id, true) : AccountService::getMastodon($follower->following_id, true);
                })
                ->filter(function ($account) {
                    return $account && isset($account['id']);
                })
                ->values()
                ->toArray();

            return $this->json($res);
        }

        $paginator = DB::table('followers')
            ->select('id', 'profile_id', 'following_id')
            ->whereProfileId($account['id'])
            ->where('id', $dir, $id)
            ->orderByDesc('id')
            ->cursorPaginate($limit)
            ->withQueryString();

        $link = null;

        if ($paginator->onFirstPage()) {
            if ($paginator->hasMorePages()) {
                $link = '<'.$paginator->nextPageUrl().'>; rel="prev"';
            }
        } else {
            if ($paginator->previousPageUrl()) {
                $link = '<'.$paginator->previousPageUrl().'>; rel="next"';
            }

            if ($paginator->hasMorePages()) {
                $link .= ($link ? ', ' : '').'<'.$paginator->nextPageUrl().'>; rel="prev"';
            }
        }

        $res = $paginator->map(function ($follower) use ($napi) {
            return $napi ? AccountService::get($follower->following_id, true) : AccountService::getMastodon($follower->following_id, true);
        })
            ->filter(function ($account) {
                return $account && isset($account['id']);
            })
            ->values()
            ->toArray();

        $headers = isset($link) ? ['Link' => $link] : [];

        return $this->json($res, 200, $headers);
    }

    /**
     * POST /api/v1/accounts/{id}/follow
     *
     * @param  int  $id
     * @return RelationshipTransformer
     */
    public function accountFollowById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();
        abort_if($user->profile_id == $id, 400, 'Invalid profile');

        abort_if($user->has_roles && ! UserRoleService::can('can-follow', $user->id), 403, 'Invalid permissions for this action');

        AccountService::setLastActive($user->id);

        $target = Profile::whereNull('status')->findOrFail($id);

        abort_if($target && $target->moved_to_profile_id, 400, 'Cannot follow an account that has moved!');

        if ($target && $target->domain) {
            $domain = $target->domain;
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        $private = (bool) $target->is_private;
        $remote = (bool) $target->domain;
        $blocked = UserFilter::whereUserId($target->id)
            ->whereFilterType('block')
            ->whereFilterableId($user->profile_id)
            ->whereFilterableType(Profile::class)
            ->exists();

        if ($blocked == true) {
            abort(400, 'You cannot follow this user.');
        }

        $isFollowing = Follower::whereProfileId($user->profile_id)
            ->whereFollowingId($target->id)
            ->exists();

        // Following already, return empty relationship
        if ($isFollowing == true) {
            $res = RelationshipService::get($user->profile_id, $target->id) ?? [];

            return $this->json($res);
        }

        // Rate limits, max 7500 followers per account
        if ($user->profile->following_count && $user->profile->following_count >= Follower::MAX_FOLLOWING) {
            abort(400, 'You cannot follow more than '.Follower::MAX_FOLLOWING.' accounts');
        }

        if ($private == true) {
            $follow = FollowRequest::firstOrCreate([
                'follower_id' => $user->profile_id,
                'following_id' => $target->id,
            ]);
            if ($remote == true && config('federation.activitypub.remoteFollow') == true) {
                (new FollowerController)->sendFollow($user->profile, $target);
            }
        } elseif ($remote == true) {
            $follow = FollowRequest::firstOrCreate([
                'follower_id' => $user->profile_id,
                'following_id' => $target->id,
            ]);

            if (config('federation.activitypub.remoteFollow') == true) {
                (new FollowerController)->sendFollow($user->profile, $target);
            }
        } else {
            $follower = Follower::firstOrCreate([
                'profile_id' => $user->profile_id,
                'following_id' => $target->id,
            ]);
            FollowPipeline::dispatch($follower)->onQueue('high');
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

        return $this->json($res);
    }

    /**
     * POST /api/v1/accounts/{id}/unfollow
     *
     * @param  int  $id
     * @return RelationshipTransformer
     */
    public function accountUnfollowById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $user = $request->user();

        abort_if($user->profile_id == $id, 400, 'Invalid profile');

        AccountService::setLastActive($user->id);

        $target = Profile::whereNull('status')
            ->findOrFail($id);

        $private = (bool) $target->is_private;
        $remote = (bool) $target->domain;

        $isFollowing = Follower::whereProfileId($user->profile_id)
            ->whereFollowingId($target->id)
            ->exists();

        if ($isFollowing == false) {
            $followRequest = FollowRequest::whereFollowerId($user->profile_id)
                ->whereFollowingId($target->id)
                ->first();
            if ($followRequest) {
                $followRequest->delete();
                RelationshipService::refresh($target->id, $user->profile_id);
                if ($target->domain) {
                    UnfollowPipeline::dispatch($user->profile_id, $target->id)->onQueue('high');
                }
            }
            $resource = new Fractal\Resource\Item($target, new RelationshipTransformer);
            $res = $this->fractal->createData($resource)->toArray();

            return $this->json($res);
        }

        Follower::whereProfileId($user->profile_id)
            ->whereFollowingId($target->id)
            ->delete();

        UnfollowPipeline::dispatch($user->profile_id, $target->id)->onQueue('high');

        if ($remote == true && config('federation.activitypub.remoteFollow') == true) {
            (new FollowerController)->sendUndoFollow($user->profile, $target);
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

        return $this->json($res);
    }

    public function accountRemoveFollowById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('follow'), 403);

        $pid = $request->user()->profile_id;

        if ($pid === $id) {
            return $this->json(['error' => 'Request invalid! target_id is same user id.'], 500);
        }

        $exists = Follower::whereProfileId($id)
            ->whereFollowingId($pid)
            ->first();

        abort_unless($exists, 404);

        $exists->delete();

        RelationshipService::refresh($pid, $id);
        RelationshipService::refresh($pid, $id);

        UnfollowPipeline::dispatch($id, $pid)->onQueue('high');

        Cache::forget('profile:following:'.$id);
        Cache::forget('profile:followers:'.$id);
        Cache::forget('profile:following:'.$pid);
        Cache::forget('profile:followers:'.$pid);
        Cache::forget('api:local:exp:rec:'.$pid);
        Cache::forget('user:account:id:'.$id);
        Cache::forget('user:account:id:'.$pid);
        Cache::forget('profile:follower_count:'.$id);
        Cache::forget('profile:follower_count:'.$pid);
        Cache::forget('profile:following_count:'.$id);
        Cache::forget('profile:following_count:'.$pid);
        AccountService::del($pid);
        AccountService::del($id);

        $res = RelationshipService::get($id, $pid);

        return $this->json($res);
    }

    /**
     * GET /api/v1/accounts/relationships
     *
     * @param  array|int  $id
     * @return RelationshipService
     */
    public function accountRelationshipsById(Request $request)
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'id' => 'required|array|min:1',
            'id.*' => 'required|integer|min:1|max:'.PHP_INT_MAX,
        ]);
        $ids = $request->input('id');
        if (count($ids) > 20) {
            $ids = collect($ids)->take(20)->toArray();
        }
        $napi = $request->has(self::PF_API_ENTITY_KEY);
        $pid = $request->user()->profile_id ?? $request->user()->profile->id;
        $res = collect($ids)
            ->map(function ($id) use ($pid, $napi) {
                if (intval($id) === intval($pid)) {
                    return [
                        'id' => $id,
                        'following' => false,
                        'followed_by' => false,
                        'blocking' => false,
                        'muting' => false,
                        'muting_notifications' => false,
                        'requested' => false,
                        'domain_blocking' => false,
                        'showing_reblogs' => false,
                        'endorsed' => false,
                    ];
                }

                return $napi ?
                    RelationshipService::getWithDate($pid, $id) :
                    RelationshipService::get($pid, $id);
            });

        return $this->json($res);
    }

    /**
     * GET /api/v1/follow_requests
     *
     *  Return array of Accounts that have sent follow requests
     *
     * @return AccountTransformer
     */
    public function accountFollowRequests(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $user = $request->user();

        $res = FollowRequest::whereFollowingId($user->profile_id)
            ->limit($request->input('limit', 40))
            ->pluck('follower_id')
            ->map(function ($id) {
                return AccountService::getMastodon($id, true);
            })
            ->filter(function ($acct) {
                return $acct && isset($acct['id']);
            })
            ->values();

        return $this->json($res);
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

        $pid = $request->user()->profile_id;
        $target = AccountService::getMastodon($id);

        abort_if(isset($target['moved'], $target['moved']['id']), 422, 'Cannot accept a request from an account that has migrated!');

        if (! $target) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        if ($target && strpos($target['acct'], '@') != -1) {
            $domain = parse_url($target['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        $followRequest = FollowRequest::whereFollowingId($pid)->whereFollowerId($id)->first();

        if (! $followRequest) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        $follower = $followRequest->follower;
        $follow = new Follower;
        $follow->profile_id = $follower->id;
        $follow->following_id = $pid;
        $follow->save();

        $profile = Profile::findOrFail($pid);
        $profile->followers_count++;
        $profile->save();
        AccountService::del($profile->id);

        $profile = Profile::findOrFail($follower->id);
        $profile->following_count++;
        $profile->save();
        AccountService::del($profile->id);

        if ($follower->domain != null && $follower->private_key === null) {
            FollowAcceptPipeline::dispatch($followRequest)->onQueue('follow');
        } else {
            FollowPipeline::dispatch($follow);
            $followRequest->delete();
        }

        RelationshipService::refresh($pid, $id);
        $res = RelationshipService::get($pid, $id);
        $res['followed_by'] = true;

        return $this->json($res);
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

        $pid = $request->user()->profile_id;
        $target = AccountService::getMastodon($id);

        if (! $target) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        abort_if(isset($target['moved'], $target['moved']['id']), 422, 'Cannot reject a request from an account that has migrated!');

        $followRequest = FollowRequest::whereFollowingId($pid)->whereFollowerId($id)->first();

        if (! $followRequest) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        $follower = $followRequest->follower;

        if ($follower->domain != null && $follower->private_key === null) {
            FollowRejectPipeline::dispatch($followRequest)->onQueue('follow');
        } else {
            $followRequest->delete();
        }

        RelationshipService::refresh($pid, $id);
        $res = RelationshipService::get($pid, $id);

        return $this->json($res);
    }
}
