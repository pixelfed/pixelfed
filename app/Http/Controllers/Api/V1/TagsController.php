<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Hashtag;
use App\HashtagFollow;
use App\StatusHashtag;
use App\Services\AccountService;
use App\Services\HashtagService;
use App\Services\HashtagFollowService;
use App\Services\HashtagRelatedService;
use App\Http\Resources\MastoApi\FollowedTagResource;
use App\Jobs\HomeFeedPipeline\FeedWarmCachePipeline;
use App\Jobs\HomeFeedPipeline\HashtagUnfollowPipeline;

class TagsController extends Controller
{
    const PF_API_ENTITY_KEY = "_pe";

    public function json($res, $code = 200, $headers = [])
    {
        return response()->json($res, $code, $headers, JSON_UNESCAPED_SLASHES);
    }

    /**
    * GET /api/v1/tags/:id/related
    *
    *
    * @return array
    */
    public function relatedTags(Request $request, $tag)
    {
        abort_unless($request->user(), 403);
        $tag = Hashtag::whereSlug($tag)->firstOrFail();
        return HashtagRelatedService::get($tag->id);
    }

    /**
    * POST /api/v1/tags/:id/follow
    *
    *
    * @return object
    */
    public function followHashtag(Request $request, $id)
    {
        abort_if(!$request->user(), 403);

        $pid = $request->user()->profile_id;
        $account = AccountService::get($pid);

        $operator = config('database.default') == 'pgsql' ? 'ilike' : 'like';
        $tag = Hashtag::where('name', $operator, $id)
            ->orWhere('slug', $operator, $id)
            ->first();

        abort_if(!$tag, 422, 'Unknown hashtag');

        abort_if(
            HashtagFollow::whereProfileId($pid)->count() >= HashtagFollow::MAX_LIMIT,
            422,
            'You cannot follow more than ' . HashtagFollow::MAX_LIMIT . ' hashtags.'
        );

        $follows = HashtagFollow::updateOrCreate(
            [
                'profile_id' => $account['id'],
                'hashtag_id' => $tag->id
            ],
            [
                'user_id' => $request->user()->id
            ]
        );

        HashtagService::follow($pid, $tag->id);
        HashtagFollowService::add($tag->id, $pid);

        return response()->json(FollowedTagResource::make($follows)->toArray($request));
    }

    /**
    * POST /api/v1/tags/:id/unfollow
    *
    *
    * @return object
    */
    public function unfollowHashtag(Request $request, $id)
    {
        abort_if(!$request->user(), 403);

        $pid = $request->user()->profile_id;
        $account = AccountService::get($pid);

        $operator = config('database.default') == 'pgsql' ? 'ilike' : 'like';
        $tag = Hashtag::where('name', $operator, $id)
            ->orWhere('slug', $operator, $id)
            ->first();

        abort_if(!$tag, 422, 'Unknown hashtag');

        $follows = HashtagFollow::whereProfileId($pid)
            ->whereHashtagId($tag->id)
            ->first();

        if(!$follows) {
            return [
                'name' => $tag->name,
                'url' => config('app.url') . '/i/web/hashtag/' . $tag->slug,
                'history' => [],
                'following' => false
            ];
        }

        if($follows) {
            HashtagService::unfollow($pid, $tag->id);
            HashtagFollowService::unfollow($tag->id, $pid);
            HashtagUnfollowPipeline::dispatch($tag->id, $pid, $tag->slug)->onQueue('feed');
            $follows->delete();
        }

        $res = FollowedTagResource::make($follows)->toArray($request);
        $res['following'] = false;
        return response()->json($res);
    }

    /**
    * GET /api/v1/tags/:id
    *
    *
    * @return object
    */
    public function getHashtag(Request $request, $id)
    {
        abort_if(!$request->user(), 403);

        $pid = $request->user()->profile_id;
        $account = AccountService::get($pid);
        $operator = config('database.default') == 'pgsql' ? 'ilike' : 'like';
        $tag = Hashtag::where('name', $operator, $id)
            ->orWhere('slug', $operator, $id)
            ->first();

        if(!$tag) {
            return [
                'name' => $id,
                'url' => config('app.url') . '/i/web/hashtag/' . $id,
                'history' => [],
                'following' => false
            ];
        }

        $res = [
            'name' => $tag->name,
            'url' => config('app.url') . '/i/web/hashtag/' . $tag->slug,
            'history' => [],
            'following' => HashtagService::isFollowing($pid, $tag->id)
        ];

        if($request->has(self::PF_API_ENTITY_KEY)) {
            $res['count'] = HashtagService::count($tag->id);
        }

        return $this->json($res);
    }

    /**
    * GET /api/v1/followed_tags
    *
    *
    * @return array
    */
    public function getFollowedTags(Request $request)
    {
        abort_if(!$request->user(), 403);

        $account = AccountService::get($request->user()->profile_id);

        $this->validate($request, [
            'cursor' => 'sometimes',
            'limit' => 'sometimes|integer|min:1|max:200'
        ]);
        $limit = $request->input('limit', 100);

        $res = HashtagFollow::whereProfileId($account['id'])
            ->orderByDesc('id')
            ->cursorPaginate($limit)
            ->withQueryString();

        $pagination = false;
        $prevPage = $res->nextPageUrl();
        $nextPage = $res->previousPageUrl();
        if($nextPage && $prevPage) {
            $pagination = '<' . $nextPage . '>; rel="next", <' . $prevPage . '>; rel="prev"';
        } else if($nextPage && !$prevPage) {
            $pagination = '<' . $nextPage . '>; rel="next"';
        } else if(!$nextPage && $prevPage) {
            $pagination = '<' . $prevPage . '>; rel="prev"';
        }

        if($pagination) {
            return response()->json(FollowedTagResource::collection($res)->collection)
                ->header('Link', $pagination);
        }
        return response()->json(FollowedTagResource::collection($res)->collection);
    }
}
