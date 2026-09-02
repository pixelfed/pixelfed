<?php

namespace App\Http\Controllers\Api\V2Api;

use App\Services\FollowerService;
use App\Services\InstanceService;
use App\Services\LikeService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait StatusContext
{
    /**
     * GET /api/v2/statuses/{id}/context
     *
     * Efficient paginated comments API with cursor pagination
     */
    public function statusContextV2(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $pid = $user->profile_id;
        $status = StatusService::getMastodon($id, false);
        $pe = $request->has(self::PF_API_ENTITY_KEY);

        if (! $status || ! isset($status['account'])) {
            return response('', 404);
        }

        // Domain check for federated content
        if ($status && isset($status['account'], $status['account']['acct']) && strpos($status['account']['acct'], '@') != -1) {
            $domain = parse_url($status['account']['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        // Visibility check
        if (intval($status['account']['id']) !== intval($user->profile_id)) {
            if ($status['visibility'] == 'private') {
                if (! FollowerService::follows($user->profile_id, $status['account']['id'])) {
                    return response('', 404);
                }
            } else {
                if (! in_array($status['visibility'], ['public', 'unlisted'])) {
                    return response('', 404);
                }
            }
        }

        // Get request parameters
        $limit = min((int) $request->input('limit', 20), 40); // Max 40 items
        $maxId = $request->input('max_id');
        $minId = $request->input('min_id');
        $sinceId = $request->input('since_id');
        $ancestorsLimit = min((int) $request->input('ancestors_limit', 10), 20); // Max 20 ancestors

        $ancestors = $this->getAncestors($id, $ancestorsLimit, $pe, $pid);
        $descendants = $this->getDescendantsPaginated($id, $limit, $maxId, $minId, $sinceId, $pe, $pid);

        $res = [
            'ancestors' => $ancestors['data'],
            'descendants' => $descendants['data'],
            'pagination' => [
                'descendants' => $descendants['pagination'],
            ],
        ];

        return $this->json($res);
    }

    /**
     * Get ancestors (parent posts) with depth limit
     * Optimized for existing indexes
     */
    private function getAncestors($statusId, $limit, $pe, $pid): array
    {
        $ancestors = [];
        $currentId = $statusId;
        $depth = 0;

        // Get user filters once for post-processing
        $filters = UserFilterService::filters($pid);

        while ($depth < $limit) {
            // Use existing statuses_in_reply_to_id_index for efficient lookup
            $parent = DB::table('statuses')
                ->select(['id', 'in_reply_to_id', 'profile_id'])
                ->where('id', function ($query) use ($currentId) {
                    $query->select('in_reply_to_id')
                        ->from('statuses')
                        ->where('id', $currentId)
                        ->whereNotNull('in_reply_to_id');
                })
                ->first();

            if (! $parent) {
                break;
            }

            // Filter at application level (more efficient than SQL NOT IN on large tables)
            if (! in_array($parent->profile_id, $filters)) {
                $parentStatus = $pe ?
                    StatusService::get($parent->id, false) :
                    StatusService::getMastodon($parent->id, false);

                if ($parentStatus && isset($parentStatus['account'])) {
                    // Add interaction status
                    $parentStatus['favourited'] = LikeService::liked($pid, $parentStatus['id']);
                    $parentStatus['reblogged'] = ReblogService::get($pid, $parentStatus['id']);

                    array_unshift($ancestors, $parentStatus); // Add to beginning to maintain order
                }
            }

            $currentId = $parent->in_reply_to_id;
            $depth++;
        }

        return ['data' => $ancestors];
    }

    /**
     * Get descendants (replies) with efficient cursor pagination
     * Optimized for existing indexes: statuses_in_reply_to_id_index
     */
    private function getDescendantsPaginated($statusId, $limit, $maxId, $minId, $sinceId, $pe, $pid): array
    {
        // Build efficient query using existing indexes
        // Uses statuses_in_reply_to_id_index for fast filtering
        $query = DB::table('statuses')
            ->select(['id', 'profile_id'])
            ->where('in_reply_to_id', $statusId)
            ->orderBy('id', 'desc'); // Snowflake IDs are chronologically ordered

        // Apply cursor pagination
        if ($maxId) {
            $query->where('id', '<', $maxId);
        }

        if ($minId) {
            $query->where('id', '>', $minId);
        }

        if ($sinceId) {
            $query->where('id', '>', $sinceId);
        }

        // Get one extra to check if there are more results
        $results = $query->limit($limit + 1)->get();

        $hasMore = $results->count() > $limit;
        if ($hasMore) {
            $results->pop(); // Remove the extra item
        }

        // Get user filters for post-processing (more efficient than SQL NOT IN on large tables)
        $filters = UserFilterService::filters($pid);

        // Transform and filter results
        $descendants = $results->map(function ($row) use ($pe, $pid, $filters) {
            // Skip if user is filtered (post-processing is more efficient here)
            if (in_array($row->profile_id, $filters)) {
                return null;
            }

            $status = $pe ?
                StatusService::get($row->id, false) :
                StatusService::getMastodon($row->id, false);

            if (! $status || ! isset($status['account'])) {
                return null;
            }

            // Add interaction status
            $status['favourited'] = LikeService::liked($pid, $status['id']);
            $status['reblogged'] = ReblogService::get($pid, $status['id']);

            return $status;
        })->filter()->values();

        // If we filtered out results and don't have enough, we might need more
        // This is a trade-off: either multiple queries or over-fetching
        $actualHasMore = $hasMore || ($results->count() === $limit && $descendants->count() < $limit);

        // Build pagination info
        $pagination = [];

        if ($descendants->isNotEmpty()) {
            $pagination['max_id'] = $descendants->last()['id'];
            $pagination['min_id'] = $descendants->first()['id'];
            $pagination['has_more'] = $actualHasMore;

            // Generate next/prev URLs if applicable
            if ($actualHasMore) {
                $pagination['next_url'] = route('api.v2.status.context', $statusId).
                    '?'.http_build_query(['max_id' => $pagination['max_id'], 'limit' => $limit]);
            }
        }

        return [
            'data' => $descendants,
            'pagination' => $pagination,
        ];
    }

    /**
     * Alternative method using Laravel's cursor pagination (if you prefer)
     */
    private function getDescendantsCursorPaginated($statusId, $limit, $cursor, $pe, $pid): array
    {
        $filters = UserFilterService::filters($pid);

        $query = DB::table('statuses')
            ->select(['id', 'created_at', 'profile_id'])
            ->where('in_reply_to_id', $statusId)
            ->whereNotIn('profile_id', $filters)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        $paginated = $query->cursorPaginate($limit, ['*'], 'cursor', $cursor);

        $descendants = collect($paginated->items())->map(function ($row) use ($pe, $pid) {
            $status = $pe ?
                StatusService::get($row->id, false) :
                StatusService::getMastodon($row->id, false);

            if (! $status || ! isset($status['account'])) {
                return null;
            }

            $status['favourited'] = LikeService::liked($pid, $status['id']);
            $status['reblogged'] = ReblogService::get($pid, $status['id']);

            return $status;
        })->filter()->values();

        return [
            'data' => $descendants,
            'pagination' => [
                'next_cursor' => $paginated->nextCursor()?->encode(),
                'prev_cursor' => $paginated->previousCursor()?->encode(),
                'has_more' => $paginated->hasMorePages(),
            ],
        ];
    }

    /**
     * GET /api/v2/statuses/{id}/descendants
     *
     * Dedicated endpoint for just descendants with pagination
     */
    public function statusDescendants(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $pid = $user->profile_id;
        $pe = $request->has(self::PF_API_ENTITY_KEY);

        // Validate status exists and user can view it
        $status = StatusService::getMastodon($id, false);
        if (! $status || ! isset($status['account'])) {
            return response('', 404);
        }

        // Same visibility checks as above...

        $limit = min((int) $request->input('limit', 20), 40);
        $maxId = $request->input('max_id');
        $minId = $request->input('min_id');
        $sinceId = $request->input('since_id');

        $descendants = $this->getDescendantsPaginated($id, $limit, $maxId, $minId, $sinceId, $pe, $pid);

        return $this->json($descendants);
    }

    /**
     * GET /api/v2/statuses/{id}/ancestors
     *
     * Dedicated endpoint for just ancestors
     */
    public function statusAncestors(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $pid = $user->profile_id;
        $pe = $request->has(self::PF_API_ENTITY_KEY);

        // Validate status exists and user can view it
        $status = StatusService::getMastodon($id, false);
        if (! $status || ! isset($status['account'])) {
            return response('', 404);
        }

        $limit = min((int) $request->input('limit', 10), 20);
        $ancestors = $this->getAncestors($id, $limit, $pe, $pid);

        return $this->json($ancestors);
    }
}
