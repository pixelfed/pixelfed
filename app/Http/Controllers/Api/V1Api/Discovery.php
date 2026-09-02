<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Services\AccountService;
use App\Services\AdminShadowFilterService;
use App\Services\DiscoverService;
use App\Services\FollowerService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

trait Discovery
{
    /**
     * GET /api/v1/discover/posts
     *
     *
     * @return array
     */
    public function discoverPosts(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'integer|min:1|max:40',
        ]);

        $limit = $request->input('limit', 40);
        $pid = $request->user()->profile_id;
        $filters = UserFilterService::filters($pid);
        $forYou = DiscoverService::getForYou();
        $posts = $forYou->take(50)->map(function ($post) {
            return StatusService::getMastodon($post);
        })
            ->filter(function ($post) use ($filters) {
                return $post &&
                    isset($post['account']) &&
                    isset($post['account']['id']) &&
                    ! in_array($post['account']['id'], $filters);
            })
            ->take(12)
            ->values();

        return $this->json(compact('posts'));
    }

    /**
     * GET /api/v1.1/discover/accounts/popular
     *
     *
     * @return array
     */
    public function discoverAccountsPopular(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $pid = $request->user()->profile_id;

        $ids = Cache::remember('api:v1.1:discover:accounts:popular', 14400, function () {
            return DB::table('profiles')
                ->where('is_private', false)
                ->whereNull('status')
                ->orderByDesc('profiles.followers_count')
                ->limit(30)
                ->get();
        });
        $filters = UserFilterService::filters($pid);
        $asf = AdminShadowFilterService::getHideFromPublicFeedsList();
        $ids = $ids->map(function ($profile) {
            return AccountService::get($profile->id, true);
        })
            ->filter(function ($profile) {
                return $profile && isset($profile['id'], $profile['locked']) && ! $profile['locked'];
            })
            ->filter(function ($profile) use ($pid) {
                return $profile['id'] != $pid;
            })
            ->filter(function ($profile) use ($pid) {
                return ! FollowerService::follows($pid, $profile['id'], true);
            })
            ->filter(function ($profile) use ($asf) {
                return ! in_array($profile['id'], $asf);
            })
            ->filter(function ($profile) use ($filters) {
                return ! in_array($profile['id'], $filters);
            })
            ->take(16)
            ->values();

        return $this->json($ids);
    }

    /**
     * GET /api/v1/trends
     *
     *
     * @return array
     */
    public function getTrends(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }

    /**
     * GET /api/v1/announcements
     *
     *
     * @return array
     */
    public function getAnnouncements(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }
}
