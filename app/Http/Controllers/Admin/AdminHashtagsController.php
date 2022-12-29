<?php

namespace App\Http\Controllers\Admin;

use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Hashtag;
use App\StatusHashtag;
use App\Http\Resources\AdminHashtag;
use App\Services\TrendingHashtagService;

trait AdminHashtagsController
{
    public function hashtagsHome(Request $request)
    {
        return view('admin.hashtags.home');
    }

    public function hashtagsApi(Request $request)
    {
        $this->validate($request, [
            'action' => 'sometimes|in:banned,nsfw',
            'sort' => 'sometimes|in:id,name,cached_count,can_search,can_trend,is_banned,is_nsfw',
            'dir' => 'sometimes|in:asc,desc'
        ]);
        $action = $request->input('action');
        $query = $request->input('q');
        $sort = $request->input('sort');
        $order = $request->input('dir');

        $hashtags = Hashtag::when($query, function($q, $query) {
                return $q->where('name', 'like', $query . '%');
            })
            ->when($sort, function($q, $sort) use($order) {
                return $q->orderBy($sort, $order);
            }, function($q) {
                return $q->orderByDesc('id');
            })
            ->when($action, function($q, $action) {
                if($action === 'banned') {
                    return $q->whereIsBanned(true);
                } else if ($action === 'nsfw') {
                    return $q->whereIsNsfw(true);
                }
            })
            ->cursorPaginate(10)
            ->withQueryString();

        return AdminHashtag::collection($hashtags);
    }

    public function hashtagsStats(Request $request)
    {
        $stats = [
            'total_unique' => Hashtag::count(),
            'total_posts' => StatusHashtag::count(),
            'added_14_days' => Hashtag::where('created_at', '>', now()->subDays(14))->count(),
            'total_banned' => Hashtag::whereIsBanned(true)->count(),
            'total_nsfw' => Hashtag::whereIsNsfw(true)->count()
        ];

        return response()->json($stats);
    }

    public function hashtagsGet(Request $request)
    {
        return new AdminHashtag(Hashtag::findOrFail($request->input('id')));
    }

    public function hashtagsUpdate(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'name' => 'required',
            'slug' => 'required',
            'can_search' => 'required:boolean',
            'can_trend' => 'required:boolean',
            'is_nsfw' => 'required:boolean',
            'is_banned' => 'required:boolean'
        ]);

        $hashtag = Hashtag::whereSlug($request->input('slug'))->findOrFail($request->input('id'));
        $canTrendPrev = $hashtag->can_trend == null ? true : $hashtag->can_trend;
        $hashtag->is_banned = $request->input('is_banned');
        $hashtag->is_nsfw = $request->input('is_nsfw');
        $hashtag->can_search = $hashtag->is_banned ? false : $request->input('can_search');
        $hashtag->can_trend = $hashtag->is_banned ? false : $request->input('can_trend');
        $hashtag->save();

        TrendingHashtagService::refresh();

        return new AdminHashtag($hashtag);
    }

    public function hashtagsClearTrendingCache(Request $request)
    {
        TrendingHashtagService::refresh();
        return [];
    }

}
