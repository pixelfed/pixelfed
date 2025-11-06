<?php

namespace App\Http\Controllers\Api\V1;

use App\Follower;
use App\Hashtag;
use App\Http\Controllers\Controller;
use App\Services\BookmarkService;
use App\Services\HomeTimelineService;
use App\Services\LikeService;
use App\Services\PublicTimelineService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\StatusHashtag;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class TimelineController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
     * GET /api/v1/timelines/home
     *
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function timelineHome(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'page' => 'nullable|integer|max:40',
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'limit' => 'nullable|integer|max:40',
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 20;
        $max_id = $request->input('max_id');
        $min_id = $request->input('min_id');

        if ($limit > 40) {
            $limit = 40;
        }

        $pid = $user->profile_id;

        $following = Cache::remember('profile:following:'.$pid, 1209600, function () use ($pid) {
            $following = Follower::whereProfileId($pid)->pluck('following_id');
            return $following->push($pid)->toArray();
        });

        if (empty($following)) {
            return $this->json([]);
        }

        $key = 'feed:home:'.$pid;
        $ttl = now()->addMinutes(15);

        if ($max_id || $min_id) {
            $res = HomeTimelineService::getRankedMaxId($pid, $max_id, $limit);
        } else {
            $res = HomeTimelineService::get($pid, $limit);
        }

        if (empty($res)) {
            return $this->json([]);
        }

        $res = collect($res)
            ->map(function ($k) use ($user) {
                $status = StatusService::getMastodon($k, false);
                if ($status && isset($status['account']) && isset($status['account']['id'])) {
                    $status['favourited'] = (bool) LikeService::liked($user->profile_id, $k);
                    $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $k);
                    $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $k);
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
     * GET /api/v1/timelines/public
     *
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function timelinePublic(Request $request)
    {
        $this->validate($request, [
            'page' => 'nullable|integer|max:40',
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'limit' => 'nullable|integer|max:40',
            'local' => 'nullable|boolean',
            'remote' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 20;
        $local = $request->boolean('local', false);
        $remote = $request->boolean('remote', false);
        $max_id = $request->input('max_id');
        $min_id = $request->input('min_id');

        if ($limit > 40) {
            $limit = 40;
        }

        if ($max_id || $min_id) {
            $res = PublicTimelineService::getRankedMaxId($max_id, $limit);
        } else {
            $res = PublicTimelineService::get($limit);
        }

        if (empty($res)) {
            return $this->json([]);
        }

        $res = collect($res)
            ->map(function ($k) use ($user) {
                $status = StatusService::getMastodon($k, false);
                if ($user && $status && isset($status['account']) && isset($status['account']['id'])) {
                    $status['favourited'] = (bool) LikeService::liked($user->profile_id, $k);
                    $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $k);
                    $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $k);
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
     * GET /api/v1/timelines/tag/{hashtag}
     *
     * @param  string  $hashtag
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function timelineHashtag(Request $request, $hashtag)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'page' => 'nullable|integer|max:40',
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'limit' => 'nullable|integer|max:40',
            'local' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 20;
        $max_id = $request->input('max_id');
        $min_id = $request->input('min_id');

        if ($limit > 40) {
            $limit = 40;
        }

        $tag = Hashtag::whereName($hashtag)
            ->orWhere('slug', $hashtag)
            ->first();

        if (! $tag) {
            return $this->json([]);
        }

        $res = StatusHashtag::whereHashtagId($tag->id)
            ->whereHas('status', function ($q) {
                $q->whereNull('uri')
                    ->whereScope('public')
                    ->whereNull('in_reply_to_id')
                    ->whereNull('reblog_of_id');
            })
            ->when($max_id, function ($query, $max_id) {
                return $query->where('status_id', '<', $max_id);
            })
            ->when($min_id, function ($query, $min_id) {
                return $query->where('status_id', '>', $min_id);
            })
            ->orderByDesc('status_id')
            ->limit($limit)
            ->pluck('status_id')
            ->map(function ($id) use ($user) {
                $status = StatusService::getMastodon($id, false);
                if ($status && isset($status['account']) && isset($status['account']['id'])) {
                    $status['favourited'] = (bool) LikeService::liked($user->profile_id, $id);
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
}