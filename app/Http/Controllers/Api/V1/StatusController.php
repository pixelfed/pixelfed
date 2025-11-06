<?php

namespace App\Http\Controllers\Api\V1;

use App\Bookmark;
use App\Http\Controllers\Controller;
use App\Jobs\LikePipeline\LikePipeline;
use App\Jobs\SharePipeline\SharePipeline;
use App\Jobs\SharePipeline\UndoSharePipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Like;
use App\Profile;
use App\Services\AccountService;
use App\Services\BookmarkService;
use App\Services\FollowerService;
use App\Services\LikeService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Status;
use App\Transformer\Api\Mastodon\v1\AccountTransformer;
use App\Transformer\Api\Mastodon\v1\StatusTransformer;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class StatusController extends Controller
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
 /**
     * GET /api/v1/statuses/{id}
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function statusById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $status = StatusService::getMastodon($id, false);

        if (! $status || ! isset($status['visibility'])) {
            return response('', 404);
        }

        if (in_array($status['visibility'], ['public', 'unlisted'])) {
            return $this->json($status);
        }

        if ($status['visibility'] == 'private') {
            if ($status['account']['id'] == $user->profile_id) {
                return $this->json($status);
            }
            $follows = FollowerService::follows($user->profile_id, $status['account']['id']);
            if ($follows == false) {
                return response('', 404);
            }
            return $this->json($status);
        }

        if ($status['visibility'] == 'direct') {
            abort(404);
        }

        return $this->json($status);
    }

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
        $status = Status::findOrFail($id);

        $like = Like::firstOrCreate([
            'profile_id' => $user->profile_id,
            'status_id' => $status->id,
        ]);

        if ($like->wasRecentlyCreated == true) {
            $status->likes_count = $status->likes()->count();
            $status->save();
            LikePipeline::dispatch($like);
        }

        $res = StatusService::getMastodon($status->id, false);
        $res['favourited'] = true;

        return $this->json($res);
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
        $status = Status::findOrFail($id);

        $like = Like::whereProfileId($user->profile_id)->whereStatusId($status->id)->first();

        if ($like) {
            $like->forceDelete();
            $status->likes_count = $status->likes()->count();
            $status->save();
        }

        $res = StatusService::getMastodon($status->id, false);
        $res['favourited'] = false;

        return $this->json($res);
    }

    /**
     * POST /api/v1/statuses/{id}/reblog
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function statusShare(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $status = Status::whereScope('public')->findOrFail($id);

        if ($status->profile_id !== $user->profile_id) {
            if ($status->scope == 'private') {
                abort_if(! FollowerService::follows($user->profile_id, $status->profile_id), 403);
            } else {
                abort_if(! in_array($status->scope, ['public', 'unlisted']), 403);
            }
        }

        $share = Status::firstOrCreate([
            'profile_id' => $user->profile_id,
            'reblog_of_id' => $status->id,
            'in_reply_to_id' => null,
            'type' => 'share',
            'scope' => 'public',
            'visibility' => 'public',
        ]);

        if ($share->wasRecentlyCreated == true) {
            $status->reblogs_count = $status->shares()->count();
            $status->save();
            SharePipeline::dispatch($share);
        }

        $res = StatusService::getMastodon($status->id, false);
        $res['reblogged'] = true;

        return $this->json($res);
    }

    /**
     * POST /api/v1/statuses/{id}/unreblog
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function statusUnshare(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $status = Status::findOrFail($id);

        $share = Status::whereProfileId($user->profile_id)
            ->whereReblogOfId($status->id)
            ->first();

        if ($share) {
            UndoSharePipeline::dispatch($share);
            $share->delete();
            $status->reblogs_count = $status->shares()->count();
            $status->save();
        }

        $res = StatusService::getMastodon($status->id, false);
        $res['reblogged'] = false;

        return $this->json($res);
    }

    /**
     * POST /api/v1/statuses/{id}/bookmark
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function bookmarkStatus(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $status = Status::findOrFail($id);

        Bookmark::firstOrCreate([
            'status_id' => $id,
            'profile_id' => $user->profile_id,
        ]);

        $res = StatusService::getMastodon($status->id, false);
        $res['bookmarked'] = true;

        return $this->json($res);
    }

    /**
     * POST /api/v1/statuses/{id}/unbookmark
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function unbookmarkStatus(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $status = Status::findOrFail($id);

        $bookmark = Bookmark::whereStatusId($id)->whereProfileId($user->profile_id)->first();
        if ($bookmark) {
            $bookmark->delete();
        }

        $res = StatusService::getMastodon($status->id, false);
        $res['bookmarked'] = false;

        return $this->json($res);
    }

    /**
     * DELETE /api/v1/statuses/{id}
     *
     * @param  int  $id
     * @return null
     */
    public function statusDelete(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $status = Status::whereProfileId($user->profile_id)->findOrFail($id);

        Cache::forget('profile:status_count:'.$user->profile_id);
        StatusDelete::dispatch($status);

        $res = StatusService::getMastodon($status->id, false);

        return $this->json($res);
    }
}
    /**
     * GET /api/v1/statuses/{id}/context
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function statusContext(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $status = Status::findOrFail($id);

        if ($status->profile_id !== $user->profile_id) {
            if ($status->scope == 'private') {
                abort_if(! FollowerService::follows($user->profile_id, $status->profile_id), 403);
            } else {
                abort_if(! in_array($status->scope, ['public', 'unlisted']), 403);
            }
        }

        if ($status->in_reply_to_id) {
            $ancestors = Status::findAncestors($status);
        } else {
            $ancestors = [];
        }

        $descendants = Status::findDescendants($status);

        $res = [
            'ancestors' => $ancestors,
            'descendants' => $descendants,
        ];

        return $this->json($res);
    }

    /**
     * GET /api/v1/statuses/{id}/card
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function statusCard(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        return $this->json([]);
    }

    /**
     * GET /api/v1/statuses/{id}/reblogged_by
     *
     * @param  int  $id
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function statusRebloggedBy(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
        ]);

        $user = $request->user();
        $status = Status::findOrFail($id);
        $limit = $request->input('limit') ?? 40;

        if ($status->profile_id !== $user->profile_id) {
            if ($status->scope == 'private') {
                abort_if(! FollowerService::follows($user->profile_id, $status->profile_id), 403);
            } else {
                abort_if(! in_array($status->scope, ['public', 'unlisted']), 403);
            }
        }

        $shares = Status::whereReblogOfId($status->id)
            ->whereIn('scope', ['public', 'unlisted'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($share) {
                return AccountService::getMastodon($share->profile_id, true);
            })
            ->filter()
            ->values();

        return $this->json($shares);
    }

    /**
     * GET /api/v1/statuses/{id}/favourited_by
     *
     * @param  int  $id
     * @return \App\Transformer\Api\AccountTransformer
     */
    public function statusFavouritedBy(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
        ]);

        $user = $request->user();
        $status = Status::findOrFail($id);
        $limit = $request->input('limit') ?? 40;

        if ($status->profile_id !== $user->profile_id) {
            if ($status->scope == 'private') {
                abort_if(! FollowerService::follows($user->profile_id, $status->profile_id), 403);
            } else {
                abort_if(! in_array($status->scope, ['public', 'unlisted']), 403);
            }
        }

        $likes = Like::whereStatusId($status->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($like) {
                return AccountService::getMastodon($like->profile_id, true);
            })
            ->filter()
            ->values();

        return $this->json($likes);
    }

    /**
     * POST /api/v1/statuses/{id}/pin
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function statusPin(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $status = Status::whereProfileId($user->profile_id)->findOrFail($id);

        $res = StatusService::getMastodon($status->id, false);
        $res['pinned'] = true;

        return $this->json($res);
    }

    /**
     * POST /api/v1/statuses/{id}/unpin
     *
     * @param  int  $id
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function statusUnpin(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $user = $request->user();
        $status = Status::whereProfileId($user->profile_id)->findOrFail($id);

        $res = StatusService::getMastodon($status->id, false);
        $res['pinned'] = false;

        return $this->json($res);
    }  
