<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Jobs\LikePipeline\LikePipeline;
use App\Jobs\LikePipeline\UnlikePipeline;
use App\Jobs\SharePipeline\SharePipeline;
use App\Jobs\SharePipeline\UndoSharePipeline;
use App\Models\Bookmark;
use App\Models\Like;
use App\Models\Status;
use App\Services\AccountService;
use App\Services\BookmarkService;
use App\Services\FollowerService;
use App\Services\InstanceService;
use App\Services\LikeService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use App\Services\UserRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait StatusInteractions
{
    /**
     * POST /api/v1/statuses/{id}/favourite
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function statusFavouriteById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-like', $user->id), 403, 'Invalid permissions for this action');

        $napi = $request->has(self::PF_API_ENTITY_KEY);
        $status = $napi ? StatusService::get($id, false) : StatusService::getMastodon($id, false);

        abort_unless($status, 404);
        abort_if(isset($status['moved'], $status['moved']['id']), 422, 'Cannot like a post from an account that has migrated');

        if ($status && isset($status['account'], $status['account']['acct']) && strpos($status['account']['acct'], '@') != -1) {
            $domain = parse_url($status['account']['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        $spid = $status['account']['id'];

        AccountService::setLastActive($user->id);

        if (intval($spid) !== intval($user->profile_id)) {
            if ($status['visibility'] == 'private') {
                abort_if(! FollowerService::follows($user->profile_id, $spid), 403);
            } else {
                abort_if(! in_array($status['visibility'], ['public', 'unlisted']), 403);
            }
        }

        abort_if(
            Like::whereProfileId($user->profile_id)
                ->where('created_at', '>', now()->subDay())
                ->count() >= Like::MAX_PER_DAY,
            429
        );

        $blocks = UserFilterService::blocks($spid);
        if ($blocks && in_array($user->profile_id, $blocks)) {
            abort(422);
        }

        $like = DB::transaction(function () use ($user, $status, $spid) {
            $statusModel = Status::lockForUpdate()->find($status['id']);

            if (! $statusModel) {
                abort(404, 'Status not found');
            }

            $like = Like::firstOrCreate(
                [
                    'profile_id' => $user->profile_id,
                    'status_id' => $status['id'],
                ],
                [
                    'status_profile_id' => $spid,
                    'is_comment' => ! empty($status['in_reply_to_id']),
                ]
            );

            if ($like->wasRecentlyCreated) {
                $statusModel->increment('likes_count');

                DB::afterCommit(function () use ($like) {
                    LikePipeline::dispatch($like)->onQueue('feed');
                });
            }

            return $like;
        });

        StatusService::del($status['id']);
        $freshStatus = $napi ? StatusService::get($id, false) : StatusService::getMastodon($id, false);

        if ($freshStatus) {
            $freshStatus['favourited'] = true;
            $freshStatus['bookmarked'] = BookmarkService::get($user->profile_id, $status['id']);
            $freshStatus['reblogged'] = ReblogService::get($user->profile_id, $status['id']);

            return $this->json($freshStatus);
        }

        $status['favourited'] = true;
        $status['favourites_count'] = ($status['favourites_count'] ?? 0) + ($like->wasRecentlyCreated ? 1 : 0);
        $status['bookmarked'] = BookmarkService::get($user->profile_id, $status['id']);
        $status['reblogged'] = ReblogService::get($user->profile_id, $status['id']);

        return $this->json($status);
    }

    /**
     * POST /api/v1/statuses/{id}/unfavourite
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function statusUnfavouriteById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-like', $user->id), 403, 'Invalid permissions for this action');

        $napi = $request->has(self::PF_API_ENTITY_KEY);
        $status = $napi ? StatusService::get($id, false) : StatusService::getMastodon($id, false);

        abort_unless($status && isset($status['account']), 404);
        abort_if(isset($status['moved'], $status['moved']['id']), 422, 'Cannot unlike a post from an account that has migrated');

        if ($status && isset($status['account'], $status['account']['acct']) && strpos($status['account']['acct'], '@') != -1) {
            $domain = parse_url($status['account']['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        $spid = $status['account']['id'];

        AccountService::setLastActive($user->id);

        if (intval($spid) !== intval($user->profile_id)) {
            if ($status['visibility'] == 'private') {
                abort_if(! FollowerService::follows($user->profile_id, $spid), 403);
            } else {
                abort_if(! in_array($status['visibility'], ['public', 'unlisted']), 403);
            }
        }

        $didUnlike = DB::transaction(function () use ($user, $status) {
            $like = Like::with(['actor', 'status'])
                ->lockForUpdate()
                ->whereProfileId($user->profile_id)
                ->whereStatusId($status['id'])
                ->first();

            if (! $like) {
                return false;
            }

            DB::afterCommit(function () use ($like) {
                UnlikePipeline::dispatch($like)->onQueue('feed');
            });

            return true;
        });

        StatusService::del($status['id']);
        $freshStatus = $napi ? StatusService::get($id, false) : StatusService::getMastodon($id, false);

        if ($freshStatus) {
            $freshStatus['favourited'] = false;
            $freshStatus['bookmarked'] = BookmarkService::get($user->profile_id, $status['id']);
            $freshStatus['reblogged'] = ReblogService::get($user->profile_id, $status['id']);

            return $this->json($freshStatus);
        }

        $status['favourited'] = false;
        $status['favourites_count'] = max(0, ($status['favourites_count'] ?? 0) - ($didUnlike ? 1 : 0));
        $status['bookmarked'] = BookmarkService::get($user->profile_id, $status['id']);
        $status['reblogged'] = ReblogService::get($user->profile_id, $status['id']);

        return $this->json($status);
    }

    /**
     * GET /api/v1/statuses/{id}/reblogged_by
     *
     * @param  int  $id
     * @return AccountTransformer
     */
    public function statusRebloggedBy(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1|max:80',
        ]);

        $limit = $request->input('limit', 10);
        $user = $request->user();
        $pid = $user->profile_id;
        $status = Status::findOrFail($id);
        $account = AccountService::get($status->profile_id, true);
        abort_if(! $account, 404);
        abort_if(isset($account['moved'], $account['moved']['id']), 404, 'Account moved');
        if ($account && strpos($account['acct'], '@') != -1) {
            $domain = parse_url($account['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }
        $author = intval($status->profile_id) === intval($pid) || $user->is_admin;
        $napi = $request->has(self::PF_API_ENTITY_KEY);

        abort_if(
            ! $status->type ||
                ! in_array($status->type, ['photo', 'photo:album', 'photo:video:album', 'reply', 'text', 'video', 'video:album']),
            404,
        );

        if (! $author) {
            if ($status->scope == 'private') {
                abort_if(! FollowerService::follows($pid, $status->profile_id), 403);
            } else {
                abort_if(! in_array($status->scope, ['public', 'unlisted']), 403);
            }

            if ($request->has('cursor')) {
                return $this->json([]);
            }
        }

        $res = Status::where('reblog_of_id', $status->id)
            ->orderByDesc('id')
            ->cursorPaginate($limit)
            ->withQueryString();

        if (! $res) {
            return $this->json([]);
        }

        $headers = [];
        if ($author && $res->hasPages()) {
            $links = '';
            if ($res->onFirstPage()) {
                if ($res->nextPageUrl()) {
                    $links = '<'.$res->nextPageUrl().'>; rel="prev"';
                }
            } else {
                if ($res->previousPageUrl()) {
                    $links = '<'.$res->previousPageUrl().'>; rel="next"';
                }

                if ($res->nextPageUrl()) {
                    if (! empty($links)) {
                        $links .= ', ';
                    }
                    $links .= '<'.$res->nextPageUrl().'>; rel="prev"';
                }
            }

            $headers = ['Link' => $links];
        }

        $res = $res->map(function ($status) use ($pid, $napi) {
            $account = $napi ? AccountService::get($status->profile_id, true) : AccountService::getMastodon($status->profile_id, true);
            if (! $account) {
                return false;
            }
            if ($napi) {
                $account['follows'] = $status->profile_id == $pid ? null : FollowerService::follows($pid, $status->profile_id);
            }

            return $account;
        })
            ->filter(function ($account) {
                return $account && isset($account['id']);
            })
            ->values();

        return $this->json($res, 200, $headers);
    }

    /**
     * GET /api/v1/statuses/{id}/favourited_by
     *
     * @param  int  $id
     * @return AccountTransformer
     */
    public function statusFavouritedBy(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1',
        ]);

        $limit = $request->input('limit', 40);
        if ($limit > 80) {
            $limit = 80;
        }
        $user = $request->user();
        $pid = $user->profile_id;
        $status = Status::findOrFail($id);
        $account = AccountService::get($status->profile_id, true);
        abort_if(! $account, 404);
        abort_if(isset($account['moved'], $account['moved']['id']), 404, 'Account moved');
        if ($account && strpos($account['acct'], '@') != -1) {
            $domain = parse_url($account['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }
        $author = intval($status->profile_id) === intval($pid) || $user->is_admin;
        $napi = $request->has(self::PF_API_ENTITY_KEY);

        abort_if(
            ! $status->type ||
                ! in_array($status->type, ['photo', 'photo:album', 'photo:video:album', 'reply', 'text', 'video', 'video:album']),
            404,
        );

        if (! $author) {
            if ($status->scope == 'private') {
                abort_if(! FollowerService::follows($pid, $status->profile_id), 403);
            } else {
                abort_if(! in_array($status->scope, ['public', 'unlisted']), 403);
            }

            if ($request->has('cursor')) {
                return $this->json([]);
            }
        }

        $res = Like::where('status_id', $status->id)
            ->orderByDesc('id')
            ->cursorPaginate($limit)
            ->withQueryString();

        if (! $res) {
            return $this->json([]);
        }

        $headers = [];
        if ($author && $res->hasPages()) {
            $links = '';

            if ($res->onFirstPage()) {
                if ($res->nextPageUrl()) {
                    $links = '<'.$res->nextPageUrl().'>; rel="prev"';
                }
            } else {
                if ($res->previousPageUrl()) {
                    $links = '<'.$res->previousPageUrl().'>; rel="next"';
                }

                if ($res->nextPageUrl()) {
                    if (! empty($links)) {
                        $links .= ', ';
                    }
                    $links .= '<'.$res->nextPageUrl().'>; rel="prev"';
                }
            }

            $headers = ['Link' => $links];
        }

        $res = $res->map(function ($like) use ($pid, $napi) {
            $account = $napi ? AccountService::get($like->profile_id, true) : AccountService::getMastodon($like->profile_id, true);
            if (! $account) {
                return false;
            }

            if ($napi) {
                $account['follows'] = $like->profile_id == $pid ? null : FollowerService::follows($pid, $like->profile_id);
            }

            return $account;
        })
            ->filter(function ($account) {
                return $account && isset($account['id']);
            })
            ->values();

        return $this->json($res, 200, $headers);
    }

    /**
     * POST /api/v1/statuses/{id}/reblog
     *
     * @param  int  $id
     * @return StatusTransformer
     */
    public function statusShare(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-share', $user->id), 403, 'Invalid permissions for this action');
        AccountService::setLastActive($user->id);
        $status = Status::whereScope('public')->findOrFail($id);
        $account = AccountService::get($status->profile_id);
        abort_if(isset($account['moved'], $account['moved']['id']), 422, 'Cannot share a post from an account that has migrated');
        if ($status && ($status->uri || $status->url || $status->object_url)) {
            $url = $status->uri ?? $status->url ?? $status->object_url;
            $domain = parse_url($url, PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }
        if (intval($status->profile_id) !== intval($user->profile_id)) {
            if ($status->scope == 'private') {
                abort_if(! FollowerService::follows($user->profile_id, $status->profile_id), 403);
            } else {
                abort_if(! in_array($status->scope, ['public', 'unlisted']), 403);
            }

            $blocks = UserFilterService::blocks($status->profile_id);
            if ($blocks && in_array($user->profile_id, $blocks)) {
                abort(422);
            }
        }

        $defaultCaption = config_cache('database.default') === 'mysql' ? null : '';
        $share = Status::firstOrCreate([
            'caption' => $defaultCaption,
            'rendered' => $defaultCaption,
            'profile_id' => $user->profile_id,
            'reblog_of_id' => $status->id,
            'type' => 'share',
            'in_reply_to_profile_id' => $status->profile_id,
            'scope' => 'public',
            'visibility' => 'public',
        ]);

        SharePipeline::dispatch($share)->onQueue('low');

        StatusService::del($status->id);
        ReblogService::add($user->profile_id, $status->id);
        $res = StatusService::getMastodon($status->id);
        $res['reblogged'] = true;
        $res['favourited'] = LikeService::liked($user->profile_id, $status->id);
        $res['bookmarked'] = BookmarkService::get($user->profile_id, $status->id);

        return $this->json($res);
    }

    /**
     * POST /api/v1/statuses/{id}/unreblog
     *
     * @param  int  $id
     * @return StatusTransformer
     */
    public function statusUnshare(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        abort_if($user->has_roles && ! UserRoleService::can('can-share', $user->id), 403, 'Invalid permissions for this action');
        AccountService::setLastActive($user->id);
        $status = Status::whereScope('public')->findOrFail($id);
        $account = AccountService::get($status->profile_id);
        abort_if(isset($account['moved'], $account['moved']['id']), 422, 'Cannot unshare a post from an account that has migrated');

        if (intval($status->profile_id) !== intval($user->profile_id)) {
            if ($status->scope == 'private') {
                abort_if(! FollowerService::follows($user->profile_id, $status->profile_id), 403);
            } else {
                abort_if(! in_array($status->scope, ['public', 'unlisted']), 403);
            }
        }

        $reblog = Status::whereProfileId($user->profile_id)
            ->whereReblogOfId($status->id)
            ->first();

        if (! $reblog) {
            $res = StatusService::getMastodon($status->id);
            $res['reblogged'] = false;

            return $this->json($res);
        }

        UndoSharePipeline::dispatch($reblog)->onQueue('low');
        ReblogService::del($user->profile_id, $status->id);

        $res = StatusService::getMastodon($status->id);
        $res['reblogged'] = false;
        $res['favourited'] = LikeService::liked($user->profile_id, $status->id);
        $res['bookmarked'] = BookmarkService::get($user->profile_id, $status->id);

        return $this->json($res);
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
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1',
            'max_id' => 'nullable|integer|min:0',
            'since_id' => 'nullable|integer|min:0',
            'min_id' => 'nullable|integer|min:0',
        ]);

        $pe = $request->has('_pe');
        $pid = $request->user()->profile_id;
        $limit = $request->input('limit') ?? 20;
        if ($limit > 40) {
            $limit = 40;
        }
        $max_id = $request->input('max_id');
        $since_id = $request->input('since_id');
        $min_id = $request->input('min_id');

        $dir = $min_id ? '>' : '<';
        $id = $min_id ?? $max_id;

        $bookmarkQuery = Bookmark::whereProfileId($pid)
            ->orderByDesc('id')
            ->cursorPaginate($limit);

        $bookmarks = $bookmarkQuery->map(function ($bookmark) use ($pid, $pe) {
            $status = $pe ? StatusService::get($bookmark->status_id, false) : StatusService::getMastodon($bookmark->status_id, false);

            if ($status) {
                $status['bookmarked'] = true;
                $status['favourited'] = LikeService::liked($pid, $status['id']);
                $status['reblogged'] = ReblogService::get($pid, $status['id']);
            }

            return $status;
        })
            ->filter()
            ->values()
            ->toArray();

        $links = null;
        $headers = [];

        if ($bookmarkQuery->nextCursor()) {
            $links .= '<'.$bookmarkQuery->nextPageUrl().'&limit='.$limit.'>; rel="next"';
        }

        if ($bookmarkQuery->previousCursor()) {
            if ($links != null) {
                $links .= ', ';
            }
            $links .= '<'.$bookmarkQuery->previousPageUrl().'&limit='.$limit.'>; rel="prev"';
        }

        if ($links) {
            $headers = ['Link' => $links];
        }

        return $this->json($bookmarks, 200, $headers);
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
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $status = Status::findOrFail($id);
        $user = $request->user();
        $pid = $request->user()->profile_id;
        $account = AccountService::get($status->profile_id);
        abort_if(isset($account['moved'], $account['moved']['id']), 422, 'Cannot bookmark a post from an account that has migrated');
        abort_if($user->has_roles && ! UserRoleService::can('can-bookmark', $user->id), 403, 'Invalid permissions for this action');
        abort_if($status->in_reply_to_id || $status->reblog_of_id, 404);
        abort_if(! in_array($status->scope, ['public', 'unlisted', 'private']), 404);
        abort_if(! in_array($status->type, ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album']), 404);

        if ($status->scope == 'private') {
            abort_if(
                $pid !== $status->profile_id && ! FollowerService::follows($pid, $status->profile_id),
                404,
                'Error: You cannot bookmark private posts from accounts you do not follow.'
            );
        }

        Bookmark::firstOrCreate([
            'status_id' => $status->id,
            'profile_id' => $pid,
        ]);

        BookmarkService::add($pid, $status->id);

        $res = StatusService::getMastodon($status->id, false);
        $res['bookmarked'] = true;

        return $this->json($res);
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
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $status = Status::findOrFail($id);
        $pid = $request->user()->profile_id;
        $user = $request->user();

        abort_if($user->has_roles && ! UserRoleService::can('can-bookmark', $user->id), 403, 'Invalid permissions for this action');
        abort_if($status->in_reply_to_id || $status->reblog_of_id, 404);
        abort_if(! in_array($status->scope, ['public', 'unlisted', 'private']), 404);
        abort_if(! in_array($status->type, ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album']), 404);

        $bookmark = Bookmark::whereStatusId($status->id)
            ->whereProfileId($pid)
            ->first();

        if ($bookmark) {
            BookmarkService::del($pid, $status->id);
            $bookmark->delete();
        }
        $res = StatusService::getMastodon($status->id, false);
        $res['bookmarked'] = false;

        return $this->json($res);
    }
}
