<?php

namespace App\Http\Controllers;

use Auth;
use App\Newsroom;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class NewsroomController extends Controller
{

    public function index(Request $request)
    {
        if (Auth::check()) {
            $posts = Newsroom::whereNotNull('published_at')->latest()->paginate(9);
        } else {
            $posts = Newsroom::whereNotNull('published_at')
                ->whereAuthOnly(false)
                ->latest()
                ->paginate(3);
        }
        return view('site.news.home', compact('posts'));
    }

    public function show(Request $request, $year, $month, $slug)
    {
        $post = Newsroom::whereNotNull('published_at')
            ->whereSlug($slug)
            ->whereYear('published_at', $year)
            ->whereMonth('published_at', $month)
            ->firstOrFail();
        abort_if($post->auth_only && !$request->user(), 404);
        return view('site.news.post.show', compact('post'));
    }

    public function search(Request $request)
    {
        abort(404);
        $this->validate($request, [
            'q'         => 'nullable'
        ]);
    }

    public function archive(Request $request)
    {
        abort(404);
        return view('site.news.archive.index');
    }

    public function timelineApi(Request $request)
    {
        abort_if(!Auth::check(), 404);

        $key = 'newsroom:read:profileid:' . $request->user()->profile_id;
        $read = Redis::smembers($key);

        $posts = Newsroom::whereNotNull('published_at')
            ->whereShowTimeline(true)
            ->whereNotIn('id', $read)
            ->orderBy('id', 'desc')
            ->take(9)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => Str::limit($post->title, 40),
                    'summary' => $post->summary,
                    'url' => $post->show_link ? $post->permalink() : null,
                    'published_at' => $post->published_at->format('F m, Y')
                ];
            });
        return response()->json($posts, 200, [], JSON_PRETTY_PRINT);
    }

    public function markAsRead(Request $request)
    {
        abort_if(!Auth::check(), 404);

        $this->validate($request, [
            'id' => 'required|integer|min:1'
        ]);

        $news = Newsroom::whereNotNull('published_at')
            ->findOrFail($request->input('id'));

        $key = 'newsroom:read:profileid:' . $request->user()->profile_id;

        Redis::sadd($key, $news->id);

        return response()->json(['code' => 200]);
    }
}
