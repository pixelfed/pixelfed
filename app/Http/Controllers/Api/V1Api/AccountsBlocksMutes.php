<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Http\Controllers\AccountController;
use App\Models\Follower;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\UserFilter;
use App\Services\AccountService;
use App\Services\FollowerService;
use App\Services\InstanceService;
use App\Services\NotificationService;
use App\Services\RelationshipService;
use App\Services\UserFilterService;
use App\Transformer\Api\RelationshipTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal;

trait AccountsBlocksMutes
{
    /**
     * GET /api/v1/blocks
     *
     *
     *
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function accountBlocks(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1',
            'page' => 'sometimes|integer|min:1',
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 40;
        if ($limit > 80) {
            $limit = 80;
        }

        $blocks = UserFilter::select('filterable_id', 'filterable_type', 'filter_type', 'user_id')
            ->whereUserId($user->profile_id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->orderByDesc('id')
            ->simplePaginate($limit)
            ->withQueryString();

        $res = $blocks->pluck('filterable_id')
            ->map(function ($id) {
                return AccountService::get($id, true);
            })
            ->filter(function ($account) {
                return $account && isset($account['id']);
            })
            ->values();

        $baseUrl = config('app.url').'/api/v1/blocks?limit='.$limit.'&';
        $next = $blocks->nextPageUrl();
        $prev = $blocks->previousPageUrl();

        if ($next && ! $prev) {
            $link = '<'.$next.'>; rel="next"';
        }

        if (! $next && $prev) {
            $link = '<'.$prev.'>; rel="prev"';
        }

        if ($next && $prev) {
            $link = '<'.$next.'>; rel="next",<'.$prev.'>; rel="prev"';
        }
        $headers = isset($link) ? ['Link' => $link] : [];

        return $this->json($res, 200, $headers);
    }

    /**
     * POST /api/v1/accounts/{id}/block
     *
     * @param  int  $id
     * @return RelationshipTransformer
     */
    public function accountBlockById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $pid = $user->profile_id ?? $user->profile->id;
        AccountService::setLastActive($user->id);

        if (intval($id) === intval($pid)) {
            abort(400, 'You cannot block yourself');
        }

        $profile = Profile::findOrFail($id);

        abort_if($profile->moved_to_profile_id, 422, 'Cannot block an account that has migrated!');

        if ($profile->user && $profile->user->is_admin == true) {
            abort(400, 'You cannot block an admin');
        }

        $count = UserFilterService::blockCount($pid);
        $maxLimit = (int) config_cache('instance.user_filters.max_user_blocks');
        if ($count == 0) {
            $filterCount = UserFilter::whereUserId($pid)
                ->whereFilterType('block')
                ->get()
                ->map(function ($rec) {
                    return AccountService::get($rec->filterable_id, true);
                })
                ->filter(function ($account) {
                    return $account && isset($account['id']);
                })
                ->values()
                ->count();
            abort_if($filterCount >= $maxLimit, 422, AccountController::FILTER_LIMIT_BLOCK_TEXT.$maxLimit.' accounts');
        } else {
            abort_if($count >= $maxLimit, 422, AccountController::FILTER_LIMIT_BLOCK_TEXT.$maxLimit.' accounts');
        }

        $followed = Follower::whereProfileId($profile->id)->whereFollowingId($pid)->first();
        if ($followed) {
            $followed->delete();
            $profile->following_count = Follower::whereProfileId($profile->id)->count();
            $profile->save();
            $selfProfile = $user->profile;
            $selfProfile->followers_count = Follower::whereFollowingId($pid)->count();
            $selfProfile->save();
            FollowerService::remove($profile->id, $pid);
            AccountService::del($pid);
            AccountService::del($profile->id);
        }

        $following = Follower::whereProfileId($pid)->whereFollowingId($profile->id)->first();
        if ($following) {
            $following->delete();
            $profile->followers_count = Follower::whereFollowingId($profile->id)->count();
            $profile->save();
            $selfProfile = $user->profile;
            $selfProfile->following_count = Follower::whereProfileId($pid)->count();
            $selfProfile->save();
            FollowerService::remove($pid, $profile->id);
            AccountService::del($pid);
            AccountService::del($profile->id);
        }

        Notification::whereProfileId($pid)
            ->whereActorId($profile->id)
            ->get()
            ->map(function ($n) use ($pid) {
                NotificationService::del($pid, $n['id']);
                $n->forceDelete();
            });

        $filter = UserFilter::firstOrCreate([
            'user_id' => $pid,
            'filterable_id' => $profile->id,
            'filterable_type' => Profile::class,
            'filter_type' => 'block',
        ]);

        UserFilterService::block($pid, $id);
        RelationshipService::refresh($pid, $id);
        $resource = new Fractal\Resource\Item($profile, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * POST /api/v1/accounts/{id}/unblock
     *
     * @param  int  $id
     * @return RelationshipTransformer
     */
    public function accountUnblockById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $pid = $user->profile_id ?? $user->profile->id;
        AccountService::setLastActive($user->id);

        if (intval($id) === intval($pid)) {
            abort(400, 'You cannot unblock yourself');
        }

        $profile = Profile::findOrFail($id);

        abort_if($profile->moved_to_profile_id, 422, 'Cannot unblock an account that has migrated!');

        $filter = UserFilter::whereUserId($pid)
            ->whereFilterableId($profile->id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('block')
            ->first();

        if ($filter) {
            $filter->delete();
            UserFilterService::unblock($pid, $profile->id);
        }
        RelationshipService::refresh($pid, $id);

        $resource = new Fractal\Resource\Item($profile, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * GET /api/v1/mutes
     *
     *
     * @return AccountTransformer
     */
    public function accountMutes(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1',
        ]);

        $user = $request->user();
        $limit = $request->input('limit', 40);
        if ($limit > 80) {
            $limit = 80;
        }

        $mutes = UserFilter::whereUserId($user->profile_id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('mute')
            ->orderByDesc('id')
            ->simplePaginate($limit)
            ->withQueryString();

        $res = $mutes->pluck('filterable_id')
            ->map(function ($id) {
                return AccountService::get($id, true);
            })
            ->filter(function ($account) {
                return $account && isset($account['id']);
            })
            ->values();

        $baseUrl = config('app.url').'/api/v1/mutes?limit='.$limit.'&';
        $next = $mutes->nextPageUrl();
        $prev = $mutes->previousPageUrl();

        if ($next && ! $prev) {
            $link = '<'.$next.'>; rel="next"';
        }

        if (! $next && $prev) {
            $link = '<'.$prev.'>; rel="prev"';
        }

        if ($next && $prev) {
            $link = '<'.$next.'>; rel="next",<'.$prev.'>; rel="prev"';
        }
        $headers = isset($link) ? ['Link' => $link] : [];

        return $this->json($res, 200, $headers);
    }

    /**
     * POST /api/v1/accounts/{id}/mute
     *
     * @param  int  $id
     * @return RelationshipTransformer
     */
    public function accountMuteById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $pid = $user->profile_id;

        if (intval($pid) === intval($id)) {
            return $this->json(['error' => 'You cannot mute yourself'], 500);
        }

        $account = Profile::findOrFail($id);

        abort_if($account->moved_to_profile_id, 422, 'Cannot mute an account that has migrated!');

        if ($account && $account->domain) {
            $domain = $account->domain;
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        $count = UserFilterService::muteCount($pid);
        $maxLimit = (int) config_cache('instance.user_filters.max_user_mutes');
        if ($count == 0) {
            $filterCount = UserFilter::whereUserId($pid)
                ->whereFilterType('mute')
                ->get()
                ->map(function ($rec) {
                    return AccountService::get($rec->filterable_id, true);
                })
                ->filter(function ($account) {
                    return $account && isset($account['id']);
                })
                ->values()
                ->count();
            abort_if($filterCount >= $maxLimit, 422, AccountController::FILTER_LIMIT_MUTE_TEXT.$maxLimit.' accounts');
        } else {
            abort_if($count >= $maxLimit, 422, AccountController::FILTER_LIMIT_MUTE_TEXT.$maxLimit.' accounts');
        }

        $filter = UserFilter::firstOrCreate([
            'user_id' => $pid,
            'filterable_id' => $account->id,
            'filterable_type' => Profile::class,
            'filter_type' => 'mute',
        ]);

        RelationshipService::refresh($pid, $id);

        $resource = new Fractal\Resource\Item($account, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * POST /api/v1/accounts/{id}/unmute
     *
     * @param  int  $id
     * @return RelationshipTransformer
     */
    public function accountUnmuteById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $pid = $user->profile_id;

        if (intval($pid) === intval($id)) {
            return $this->json(['error' => 'You cannot unmute yourself'], 500);
        }

        $profile = Profile::findOrFail($id);

        abort_if($profile->moved_to_profile_id, 422, 'Cannot unmute an account that has migrated!');

        $filter = UserFilter::whereUserId($pid)
            ->whereFilterableId($profile->id)
            ->whereFilterableType(Profile::class)
            ->whereFilterType('mute')
            ->first();

        if ($filter) {
            $filter->delete();
            UserFilterService::unmute($pid, $profile->id);
        }

        RelationshipService::refresh($pid, $id);

        $resource = new Fractal\Resource\Item($profile, new RelationshipTransformer);
        $res = $this->fractal->createData($resource)->toArray();

        return $this->json($res);
    }

    /**
     * GET /api/v1/domain_blocks
     *
     * Return empty array
     *
     * @return array
     */
    public function accountDomainBlocks(Request $request): JsonResponse
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return response()->json([]);
    }
}
