<?php

namespace App\Http\Controllers\Api\V1;

use App\Bookmark;
use App\Http\Controllers\Controller;
use App\Services\BookmarkService;
use App\Services\LikeService;
use App\Services\ReblogService;
use App\Services\StatusService;
use App\Status;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class BookmarkController extends Controller
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
     * GET /api/v1/bookmarks
     *
     * @return \App\Transformer\Api\StatusTransformer
     */
    public function bookmarks(Request $request)
    {
        abort_if(! $request->user() || ! $request->user()->token(), 403);
        abort_unless($request->user()->tokenCan('read'), 403);

        $this->validate($request, [
            'limit' => 'nullable|integer|min:1|max:40',
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'since_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
        ]);

        $user = $request->user();
        $limit = $request->input('limit') ?? 20;
        $max_id = $request->input('max_id');
        $since_id = $request->input('since_id');
        $min_id = $request->input('min_id');

        $bookmarks = Bookmark::whereProfileId($user->profile_id)
            ->when($max_id, function ($query, $max_id) {
                return $query->where('status_id', '<', $max_id);
            })
            ->when($since_id, function ($query, $since_id) {
                return $query->where('status_id', '>', $since_id);
            })
            ->when($min_id, function ($query, $min_id) {
                return $query->where('status_id', '>', $min_id);
            })
            ->orderByDesc('status_id')
            ->limit($limit)
            ->pluck('status_id');

        $res = $bookmarks->map(function ($id) use ($user) {
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