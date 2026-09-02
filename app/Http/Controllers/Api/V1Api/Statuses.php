<?php

namespace App\Http\Controllers\Api\V1Api;

use App\Http\Controllers\StatusController;
use App\Jobs\CommentPipeline\CommentPipeline;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Media;
use App\Models\Status;
use App\Services\AccountService;
use App\Services\BookmarkService;
use App\Services\CollectionService;
use App\Services\FollowerService;
use App\Services\InstanceService;
use App\Services\LikeService;
use App\Services\ReblogService;
use App\Services\SnowflakeService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use App\Services\UserRoleService;
use App\Transformer\Api\Mastodon\v1\StatusTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Fractal;

trait Statuses
{
    /**
     * GET /api/v1/statuses/{id}
     *
     * @param  int  $id
     * @return StatusTransformer
     */
    public function statusById(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        AccountService::setLastActive($request->user()->id);
        $pid = $request->user()->profile_id;

        $res = $request->has(self::PF_API_ENTITY_KEY) ? StatusService::get($id, false) : StatusService::getMastodon($id, false);
        if (! $res || ! isset($res['visibility'])) {
            abort(404);
        }

        if ($res && isset($res['account'], $res['account']['acct'], $res['account']['url']) && strpos($res['account']['acct'], '@') != -1) {
            $domain = parse_url($res['account']['url'], PHP_URL_HOST);
            abort_if(in_array($domain, InstanceService::getBannedDomains()), 404);
        }

        $scope = $res['visibility'];
        if (! in_array($scope, ['public', 'unlisted'])) {
            if ($scope === 'private') {
                if (intval($res['account']['id']) !== intval($pid)) {
                    abort_unless(FollowerService::follows($pid, $res['account']['id']), 403);
                }
            } else {
                abort(400, 'Invalid request');
            }
        }

        if (! empty($res['reblog']) && isset($res['reblog']['id'])) {
            $res['reblog']['favourited'] = (bool) LikeService::liked($pid, $res['reblog']['id']);
            $res['reblog']['reblogged'] = (bool) ReblogService::get($pid, $res['reblog']['id']);
            $res['reblog']['bookmarked'] = BookmarkService::get($pid, $res['reblog']['id']);
        }

        $res['favourited'] = LikeService::liked($pid, $res['id']);
        $res['reblogged'] = ReblogService::get($pid, $res['id']);
        $res['bookmarked'] = BookmarkService::get($pid, $res['id']);

        return $this->json($res);
    }

    /**
     * GET /api/v1/statuses/{id}/context
     *
     * @param  int  $id
     * @return StatusTransformer
     */
    public function statusContext(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $user = $request->user();
        $pid = $user->profile_id;
        $pe = $request->has(self::PF_API_ENTITY_KEY);

        $status = StatusService::getMastodon(
            $id,
            false,
            $pid
        );

        if (! $status || ! isset($status['account'])) {
            return response('', 404);
        }

        if (
            isset($status['account']['acct']) &&
            strpos($status['account']['acct'], '@') !== false
        ) {
            $domain = parse_url(
                $status['account']['url'],
                PHP_URL_HOST
            );

            abort_if(
                in_array($domain, InstanceService::getBannedDomains()),
                404
            );
        }

        $filters = UserFilterService::filters($pid);

        $ancestors = [];
        $descendants = [];

        if ($status['in_reply_to_id']) {
            $ancestor = $pe
                ? StatusService::get(
                    $status['in_reply_to_id'],
                    false,
                    false,
                    $pid
                )
                : StatusService::getMastodon(
                    $status['in_reply_to_id'],
                    false,
                    $pid
                );

            if (
                $ancestor &&
                isset($ancestor['account']['id']) &&
                ! in_array($ancestor['account']['id'], $filters)
            ) {
                $ancestors[] = $ancestor;
            }
        }

        if ($status['replies_count']) {
            $descendants = DB::table('statuses')
                ->where('in_reply_to_id', $id)
                ->limit(20)
                ->pluck('id')
                ->map(function ($sid) use ($pe, $pid) {
                    return $pe
                        ? StatusService::get(
                            $sid,
                            false,
                            false,
                            $pid
                        )
                        : StatusService::getMastodon(
                            $sid,
                            false,
                            $pid
                        );
                })
                ->filter(function ($post) use ($filters) {
                    return $post &&
                        isset($post['account']['id']) &&
                        ! in_array($post['account']['id'], $filters);
                })
                ->map(function ($status) use ($pid) {
                    $status['favourited'] = LikeService::liked(
                        $pid,
                        $status['id']
                    );

                    $status['reblogged'] = ReblogService::get(
                        $pid,
                        $status['id']
                    );

                    return $status;
                })
                ->values();
        }

        return $this->json([
            'ancestors' => $ancestors,
            'descendants' => $descendants,
        ]);
    }

    /**
     * GET /api/v1/statuses/{id}/card
     *
     * @param  int  $id
     * @return StatusTransformer
     */
    public function statusCard(Request $request, $id): JsonResponse
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $res = [];

        return response()->json($res);
    }

    /**
     * POST /api/v1/statuses
     *
     *
     * @return StatusTransformer
     */
    public function statusCreate(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        $this->validate($request, [
            'status' => 'nullable|string|max:'.(int) config_cache('pixelfed.max_caption_length'),
            'in_reply_to_id' => 'nullable',
            'media_ids' => 'sometimes|array|max:'.(int) config_cache('pixelfed.max_album_length'),
            'sensitive' => 'nullable',
            'visibility' => 'string|in:private,unlisted,public,direct',
            'spoiler_text' => 'sometimes|max:140',
            'place_id' => 'sometimes|integer|min:1|max:128769',
            'collection_ids' => 'sometimes|array|max:3',
            'comments_disabled' => 'sometimes|boolean',
        ]);

        if ($request->filled('visibility') && $request->input('visibility') === 'direct') {
            return $this->json([
                'error' => 'Direct visibility is not available.',
            ], 400);
        }

        if ($request->hasHeader('idempotency-key')) {
            $key = 'pf:api:v1:status:idempotency-key:'.$request->user()->id.':'.hash('sha1', $request->header('idempotency-key'));
            $exists = Cache::has($key);
            abort_if($exists, 400, 'Duplicate idempotency key.');
            Cache::put($key, 1, 3600);
        }

        if (config('costar.enabled') == true) {
            $blockedKeywords = config('costar.keyword.block');
            if ($blockedKeywords !== null && $request->status) {
                $keywords = config('costar.keyword.block');
                foreach ($keywords as $kw) {
                    if (Str::contains($request->status, $kw) == true) {
                        abort(400, 'Invalid object. Contains banned keyword.');
                    }
                }
            }
        }

        if (! $request->filled('media_ids') && ! $request->filled('in_reply_to_id')) {
            abort(403, 'Empty statuses are not allowed');
        }

        $ids = $request->input('media_ids');
        $in_reply_to_id = $request->input('in_reply_to_id');

        $user = $request->user();

        if ($user->has_roles) {
            if ($in_reply_to_id != null) {
                abort_if(! UserRoleService::can('can-comment', $user->id), 403, 'Invalid permissions for this action');
            } else {
                abort_if(! UserRoleService::can('can-post', $user->id), 403, 'Invalid permissions for this action');
            }
        }

        $profile = $user->profile;

        $limitKey = 'compose:rate-limit:store:'.$user->id;
        $limitTtl = now()->addMinutes(15);
        $limitReached = Cache::remember($limitKey, $limitTtl, function () use ($user) {
            $minId = SnowflakeService::byDate(now()->subDays(1));
            $dailyLimit = Status::whereProfileId($user->profile_id)
                ->where('id', '>', $minId)
                ->count();

            return $dailyLimit >= 1000;
        });

        abort_if($limitReached == true, 429);

        $visibility = $profile->is_private ? 'private' : (
            $profile->unlisted == true &&
            $request->input('visibility', 'public') == 'public' ?
            'unlisted' :
            $request->input('visibility', 'public'));

        if ($user->last_active_at == null) {
            return [];
        }

        $defaultCaption = '';
        $content = $request->filled('status') ? strip_tags($request->input('status')) : $defaultCaption;
        $cw = $user->profile->cw == true ? true : $request->boolean('sensitive', false);
        $spoilerText = $cw && $request->filled('spoiler_text') ? $request->input('spoiler_text') : null;

        $status = null;
        $parent = null;

        if ($in_reply_to_id) {
            $parent = Status::findOrFail($in_reply_to_id);

            abort_unless(
                StatusService::isVisibleTo(
                    $parent->profile_id,
                    $parent->scope,
                    $profile->id
                ),
                404
            );
            if ($parent->comments_disabled) {
                return $this->json('Comments have been disabled on this post', 422);
            }
            $blocks = UserFilterService::blocks($parent->profile_id);
            abort_if(in_array($profile->id, $blocks), 422, 'Cannot reply to this post at this time.');

            $visibility = StatusService::clampReplyVisibility(
                $visibility,
                $parent->scope
            );

            $status = new Status;
            $status->caption = $content;
            $status->rendered = $defaultCaption;
            $status->scope = $visibility;
            $status->visibility = $visibility;
            $status->profile_id = $user->profile_id;
            $status->is_nsfw = $cw;
            $status->cw_summary = $spoilerText;
            $status->in_reply_to_id = $parent->id;
            $status->in_reply_to_profile_id = $parent->profile_id;
            $status->save();
            StatusService::del($parent->id);
            Cache::forget('status:replies:all:'.$parent->id);
        }

        if ($ids) {
            if (
                Media::whereUserId($user->id)
                    ->whereNull('status_id')
                    ->find($ids)
                    ->count() == 0
            ) {
                abort(400, 'Invalid media_ids');
            }

            if (! $in_reply_to_id) {
                $status = new Status;
                $status->caption = $content;
                $status->rendered = $defaultCaption;
                $status->profile_id = $user->profile_id;
                $status->is_nsfw = $cw;
                $status->cw_summary = $spoilerText;
                $status->scope = 'draft';
                $status->visibility = 'draft';
                if ($request->has('place_id')) {
                    $status->place_id = $request->input('place_id');
                }
                $status->save();
            }

            $mimes = [];

            foreach ($ids as $k => $v) {
                if ($k + 1 > (int) config_cache('pixelfed.max_album_length')) {
                    continue;
                }
                $m = Media::whereUserId($user->id)->whereNull('status_id')->findOrFail($v);
                if ($m->profile_id !== $user->profile_id || $m->status_id) {
                    abort(403, 'Invalid media id');
                }
                $m->order = $k + 1;
                $m->status_id = $status->id;
                $m->save();
                array_push($mimes, $m->mime);
            }

            if (empty($mimes)) {
                $status->delete();
                abort(400, 'Invalid media ids');
            }

            if ($request->has('comments_disabled') && $request->input('comments_disabled')) {
                $status->comments_disabled = true;
            }

            $status->scope = $visibility;
            $status->visibility = $visibility;
            $status->type = StatusController::mimeTypeCheck($mimes);
            $status->save();
        }

        if (! $status) {
            abort(500, 'An error occured.');
        }

        Cache::forget('pf:status:ap:v1:sid:'.$status->id);
        Cache::forget('status:transformer:media:attachments:'.$status->id);
        Cache::forget('user:account:id:'.$user->id);
        Cache::forget('_api:statuses:recent_9:'.$user->profile_id);
        Cache::forget('profile:status_count:'.$user->profile_id);
        Cache::forget($user->storageUsedKey());
        Cache::forget('profile:embed:'.$status->profile_id);
        Cache::forget($limitKey);

        NewStatusPipeline::dispatch($status);
        if ($status->in_reply_to_id) {
            CommentPipeline::dispatch($parent, $status);
        }

        if ($request->has('collection_ids') && $ids) {
            $collections = Collection::whereProfileId($user->profile_id)
                ->find($request->input('collection_ids'))
                ->each(function ($collection) use ($status) {
                    $count = $collection->items()->count();
                    $item = CollectionItem::firstOrCreate([
                        'collection_id' => $collection->id,
                        'object_type' => Status::class,
                        'object_id' => $status->id,
                    ], [
                        'order' => $count,
                    ]);

                    CollectionService::addItem(
                        $collection->id,
                        $status->id,
                        $count
                    );
                    $collection->updated_at = now();
                    $collection->save();
                    CollectionService::setCollection($collection->id, $collection);
                });
        }

        $res = StatusService::getMastodon($status->id, false);
        $res['favourited'] = false;
        $res['language'] = 'en';
        $res['bookmarked'] = false;
        $res['card'] = null;

        return $this->json($res);
    }

    /**
     * DELETE /api/v1/statuses
     *
     * @param  int  $id
     * @return null
     */
    public function statusDelete(Request $request, $id)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);

        AccountService::setLastActive($request->user()->id);
        $status = Status::whereProfileId($request->user()->profile->id)
            ->findOrFail($id);

        $resource = new Fractal\Resource\Item($status, new StatusTransformer);

        Cache::forget('profile:status_count:'.$status->profile_id);
        StatusDelete::dispatch($status);

        $res = $this->fractal->createData($resource)->toArray();
        $res['text'] = $res['content'];
        unset($res['content']);

        return $this->json($res);
    }

    /**
     * GET /api/v2/statuses/{id}/replies
     *
     *
     * @return array
     */
    public function statusReplies(Request $request, $id)
    {
        abort_if(! $request->user(), 403);

        $this->validate($request, [
            'limit' => 'sometimes|integer|min:1',
            'sort' => 'in:all,newest,popular',
        ]);

        $limit = $request->input('limit', 3);
        if ($limit > 10) {
            $limit = 10;
        }
        $pid = $request->user()->profile_id;
        $status = StatusService::getMastodon(
            $id,
            false,
            $pid
        );

        abort_if(! $status, 404);
        abort_if(isset($status['account'], $status['account']['moved'], $status['account']['moved']['id']), 404, 'Account moved');

        $sortBy = $request->input('sort', 'all');

        if ($sortBy == 'all' && isset($status['replies_count']) && $status['replies_count'] && $request->has('refresh_cache')) {
            if (! Cache::has('status:replies:all-rc:'.$id)) {
                Cache::forget('status:replies:all:'.$id);
                Cache::put('status:replies:all-rc:'.$id, true, 300);
            }
        }

        if ($sortBy == 'all' && ! $request->has('cursor')) {
            $ids = Cache::remember('status:replies:all:'.$id, 3600, function () use ($id) {
                return DB::table('statuses')
                    ->where('in_reply_to_id', $id)
                    ->orderBy('id')
                    ->cursorPaginate(3);
            });
        } else {
            $ids = DB::table('statuses')
                ->where('in_reply_to_id', $id)
                ->when($sortBy, function ($q, $sortBy) {
                    if ($sortBy === 'all') {
                        return $q->orderBy('id');
                    }

                    if ($sortBy === 'newest') {
                        return $q->orderByDesc('created_at');
                    }

                    if ($sortBy === 'popular') {
                        return $q->orderByDesc('likes_count');
                    }
                })
                ->cursorPaginate($limit);
        }

        $filters = UserFilterService::filters($pid);
        $data = $ids->filter(function ($post) use ($filters) {
            return ! in_array($post->profile_id, $filters);
        })
            ->map(function ($post) use ($pid) {
                $status = StatusService::get(
                    $post->id,
                    false,
                    false,
                    $pid
                );

                if (! $status || ! isset($status['id'])) {
                    return false;
                }

                $status['favourited'] = LikeService::liked($pid, $post->id);

                return $status;
            })
            ->map(function ($post) {
                if (isset($post['account']) && isset($post['account']['id'])) {
                    $account = AccountService::get($post['account']['id'], true);
                    $post['account'] = $account;
                }

                return $post;
            })
            ->filter(function ($post) {
                return $post && isset($post['id']) && isset($post['account']) && isset($post['account']['id']);
            })
            ->values();

        $res = [
            'data' => $data,
            'next' => $ids->nextPageUrl(),
        ];

        return $this->json($res);
    }

    /**
     * GET /api/v2/statuses/{id}/state
     *
     *
     * @return array
     */
    public function statusState(Request $request, $id)
    {
        abort_if(! $request->user(), 403);

        $status = StatusService::get($id, false, true);
        abort_if(! $status, 404);
        abort_if(! in_array($status['visibility'], ['public', 'unlisted', 'private']), 404);

        return $this->json(StatusService::getState($status['id'], $request->user()->profile_id));
    }

    /**
     *  GET /api/v1/statuses/{id}/pin
     */
    public function statusPin(Request $request, $id)
    {
        abort_if(! $request->user(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);
        $user = $request->user();
        $status = Status::whereScope('public')->find($id);

        if (! $status) {
            return $this->json(['error' => 'Record not found'], 404);
        }

        if ($status->profile_id != $user->profile_id) {
            return $this->json(['error' => "Validation failed: Someone else's post cannot be pinned"], 422);
        }

        $res = StatusService::markPin($status->id);

        if (! $res['success']) {
            return $this->json([
                'error' => $res['error'],
            ], 422);
        }

        $statusRes = StatusService::get($status->id, true, true);
        $status['pinned'] = true;

        return $this->json($statusRes);
    }

    /**
     *  GET /api/v1/statuses/{id}/unpin
     */
    public function statusUnpin(Request $request, $id)
    {
        abort_if(! $request->user(), 403);
        abort_unless($request->user()->tokenCan('write'), 403);
        $status = Status::whereScope('public')->findOrFail($id);
        $user = $request->user();

        if ($status->profile_id != $user->profile_id) {
            return $this->json(['error' => 'Record not found'], 404);
        }

        $res = StatusService::unmarkPin($status->id);
        if (! $res) {
            return $this->json($res, 422);
        }

        $status = StatusService::get($status->id, true, true);
        $status['pinned'] = false;

        return $this->json($status);
    }
}
