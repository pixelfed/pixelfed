<?php

namespace App\Http\Controllers\Api\V1;

use App\Collection;
use App\CollectionItem;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StatusController;
use App\Jobs\CommentPipeline\CommentPipeline;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use App\Media;
use App\Services\CollectionService;
use App\Services\SnowflakeService;
use App\Services\StatusService;
use App\Services\UserFilterService;
use App\Services\UserRoleService;
use App\Status;
use Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class StatusCreateController extends Controller
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
     * POST /api/v1/statuses
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

        if ($in_reply_to_id) {
            $parent = Status::findOrFail($in_reply_to_id);
            if ($parent->comments_disabled) {
                return $this->json('Comments have been disabled on this post', 422);
            }
            $blocks = UserFilterService::blocks($parent->profile_id);
            abort_if(in_array($profile->id, $blocks), 422, 'Cannot reply to this post at this time.');

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
            if (Media::whereUserId($user->id)
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
                        'object_type' => 'App\Status',
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
}