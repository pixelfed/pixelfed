<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Jobs\NotificationPipeline\NotificationWarmUserCache;
use App\Models\Like;
use App\Services\AccountService;
use App\Services\BookmarkService;
use App\Services\LikeService;
use App\Services\MarkerService;
use App\Services\NotificationService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Transformer\Api\StatusTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

trait AccountsMisc
{
    /**
     * GET /api/v1/endorsements
     *
     * Return empty array
     *
     * @return array
     */
    public function accountEndorsements(Request $request): JsonResponse
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return response()->json([]);
    }

    /**
     * GET /api/v1/favourites
     *
     * Returns collection of liked statuses
     *
     * @return StatusTransformer
     */
    public function accountFavourites(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1',
        ]);

        $user = $request->user();
        $maxId = $request->input('max_id');
        $minId = $request->input('min_id');
        $limit = $request->input('limit') ?? 10;
        if ($limit > 40) {
            $limit = 40;
        }

        $res = Like::whereProfileId($user->profile_id)
            ->when($maxId, function ($q, $maxId) {
                return $q->where('id', '<', $maxId);
            })
            ->when($minId, function ($q, $minId) {
                return $q->where('id', '>', $minId);
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function ($like) {
                $status = StatusService::getMastodon($like['status_id'], false);
                $status['favourited'] = true;
                $status['like_id'] = $like->id;
                $status['liked_at'] = str_replace('+00:00', 'Z', $like->created_at->format(DATE_RFC3339_EXTENDED));

                return $status;
            })
            ->filter(function ($status) {
                return $status && isset($status['id'], $status['like_id']);
            })
            ->values();

        if ($res->count()) {
            $ids = $res->map(function ($status) {
                return $status['like_id'];
            })->filter();

            $max = $ids->min() - 1;
            $min = $ids->max();

            $baseUrl = config('app.url').'/api/v1/favourites?limit='.$limit.'&';
            if ($maxId) {
                $link = '<'.$baseUrl.'max_id='.$max.'>; rel="next",<'.$baseUrl.'min_id='.$min.'>; rel="prev"';
            } else {
                $link = '<'.$baseUrl.'max_id='.$max.'>; rel="next"';
            }

            return $this->json($res, 200, ['Link' => $link]);
        } else {
            return $this->json($res);
        }
    }

    /**
     * GET /api/v1/filters
     *
     *  Return empty response since we filter server side
     *
     * @return array
     */
    public function accountFilters(Request $request): JsonResponse
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return response()->json([]);
    }

    /**
     * GET /api/v1/lists
     *
     *   Return empty array as we don't support lists
     *
     * @return null
     */
    public function accountLists(Request $request): JsonResponse
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return response()->json([]);
    }

    /**
     * GET /api/v1/accounts/{id}/lists
     *
     * @param  int  $id
     * @return null
     */
    public function accountListsById(Request $request, $id): JsonResponse
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return response()->json([]);
    }

    /**
     * GET /api/v1/notifications
     *
     *
     * @return NotificationTransformer
     */
    public function accountNotifications(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1',
            'min_id' => 'nullable|integer|min:1|max:'.PHP_INT_MAX,
            'max_id' => 'nullable|integer|min:1|max:'.PHP_INT_MAX,
            'since_id' => 'nullable|integer|min:1|max:'.PHP_INT_MAX,
            'types[]' => 'sometimes|array',
            'types[].*' => 'string|in:mention,reblog,follow,favourite',
            'type' => 'sometimes|string|in:mention,reblog,follow,favourite',
            '_pe' => 'sometimes',
        ]);

        $pid = $request->user()->profile_id;
        $limit = $request->input('limit', 20);
        $ogLimit = $request->input('limit', 20);
        if ($limit > 40) {
            $limit = 40;
            $ogLimit = 40;
        }

        $since = $request->input('since_id');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $pe = $request->filled('_pe');

        if (! $since && ! $min && ! $max) {
            $min = 1;
        }

        if ($since) {
            $min = $since + 1;
        }

        $types = $request->input('types');

        if ($request->has('types')) {
            $limit = 150;
        }

        $maxId = null;
        $minId = null;
        AccountService::setLastActive($request->user()->id);

        $res = $max ?
            NotificationService::getMaxMastodon($pid, $max, $limit) :
            NotificationService::getMinMastodon($pid, $min ?? $since, $limit);
        $ids = $max ?
            NotificationService::getRankedMaxId($pid, $max, $limit) :
            NotificationService::getRankedMinId($pid, $min ?? $since, $limit);
        if (! empty($ids)) {
            $maxId = max($ids);
            $minId = min($ids);
        }

        if (empty($res)) {
            if (! Cache::has('pf:services:notifications:hasSynced:'.$pid)) {
                Cache::put('pf:services:notifications:hasSynced:'.$pid, 1, 1209600);
                NotificationWarmUserCache::dispatch($pid);
            }
        }

        if ($request->has('types')) {
            $typesParams = collect($types)->implode('&types[]=');
            $baseUrl = config('app.url').'/api/v1/notifications?types[]='.$typesParams.'&limit='.$ogLimit.'&';
        } else {
            $baseUrl = config('app.url').'/api/v1/notifications?limit='.$ogLimit.'&';
        }

        if ($minId == $maxId) {
            $minId = null;
        }

        $res = collect($res)
            ->map(function ($n) use ($pe) {
                if (! $pe) {
                    if ($n['type'] == 'comment') {
                        $n['type'] = 'mention';

                        return $n;
                    }

                    return $n;
                }

                return $n;
            })
            ->filter(function ($n) use ($pe) {
                if (in_array($n['type'], ['mention', 'reblog', 'favourite'])) {
                    return isset($n['status'], $n['status']['id']);
                }

                if (in_array($n['type'], ['follow'])) {
                    return isset($n['account'], $n['account']['id']);
                }

                if (! $pe) {
                    if (in_array($n['type'], [
                        'tagged',
                        'modlog',
                        'story:react',
                        'story:comment',
                        'group:comment',
                        'group:join:approved',
                        'group:join:rejected',
                    ])) {
                        return false;
                    }

                    return isset($n['account'], $n['account']['id']);
                }

                return true;
            })
            ->map(function ($n) use ($pid) {
                if (isset($n['status'])) {
                    $n['status']['favourited'] = (bool) LikeService::liked($pid, $n['status']['id']);
                    $n['status']['reblogged'] = (bool) ReblogService::get($pid, $n['status']['id']);
                    $n['status']['bookmarked'] = (bool) BookmarkService::get($pid, $n['status']['id']);
                }

                return $n;
            })
            ->filter(function ($n) use ($types) {
                if (! $types) {
                    return true;
                }

                return in_array($n['type'], $types);
            })
            ->take($ogLimit)
            ->values();

        if ($maxId) {
            $link = '<'.$baseUrl.'max_id='.$minId.'>; rel="next"';
        }

        if ($minId) {
            $link = '<'.$baseUrl.'min_id='.$maxId.'>; rel="prev"';
        }

        if ($maxId && $minId) {
            $link = '<'.$baseUrl.'max_id='.$minId.'>; rel="next",<'.$baseUrl.'min_id='.$maxId.'>; rel="prev"';
        }

        $headers = isset($link) ? ['Link' => $link] : [];

        return $this->json($res, 200, $headers);
    }

    /**
     * GET /api/v1/preferences
     *
     *
     * @return array
     */
    public function getPreferences(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $pid = $request->user()->profile_id;
        $account = AccountService::get($pid);

        return $this->json([
            'posting:default:visibility' => $account['locked'] ? 'private' : 'public',
            'posting:default:sensitive' => false,
            'posting:default:language' => null,
            'reading:expand:media' => 'default',
            'reading:expand:spoilers' => false,
        ]);
    }

    /**
     * GET /api/v1/markers
     *
     *
     * @return array
     */
    public function getMarkers(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $type = $request->input('timeline');
        if (is_array($type)) {
            $type = $type[0];
        }
        if (! $type || ! in_array($type, ['home', 'notifications'])) {
            return $this->json([]);
        }
        $pid = $request->user()->profile_id;

        return $this->json(MarkerService::get($pid, $type));
    }

    /**
     * POST /api/v1/markers
     *
     *
     * @return array
     */
    public function setMarkers(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $pid = $request->user()->profile_id;
        $home = $request->input('home[last_read_id]');
        $notifications = $request->input('notifications[last_read_id]');

        if ($home) {
            return $this->json(MarkerService::set($pid, 'home', $home));
        }

        if ($notifications) {
            return $this->json(MarkerService::set($pid, 'notifications', $notifications));
        }

        return $this->json([]);
    }
}
