<?php

namespace App\Http\Controllers;

use App\Status;
use App\Transformer\Api\StatusTimelineTransformer;
use App\UserFilter;
use Auth;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class TimelineController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('twofactor');
        $this->fractal = new Fractal\Manager;
        $this->fractal->setSerializer(new ArraySerializer);
    }

    public function local(Request $request)
    {
        $this->validate($request, [
            'layout' => 'nullable|string|in:grid,feed',
        ]);
        $layout = $request->input('layout', 'feed');

        return view('timeline.local', compact('layout'));
    }

    public function network(Request $request)
    {
        abort_if(config('federation.network_timeline') == false, 404);
        $this->validate($request, [
            'layout' => 'nullable|string|in:grid,feed',
        ]);
        $layout = $request->input('layout', 'feed');

        return view('timeline.network', compact('layout'));
    }

    public function publicApi(Request $request)
    {
        $this->validate($request, [
            'page' => 'nullable|integer|max:40',
            'min_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'max_id' => 'nullable|integer|min:0|max:'.PHP_INT_MAX,
            'limit' => 'nullable|integer|max:30',
        ]);

        if (config('instance.timeline.local.is_public') == false && ! Auth::check()) {
            abort(403, 'Authentication required.');
        }

        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 3;
        $user = $request->user();

        $key = 'user:last_active_at:id:'.$user->id;
        $ttl = now()->addMinutes(5);
        Cache::remember($key, $ttl, function () use ($user) {
            $user->last_active_at = now();
            $user->save();

        });

        $filtered = UserFilter::whereUserId($user->profile_id)
            ->whereFilterableType(\App\Profile::class)
            ->whereIn('filter_type', ['mute', 'block'])
            ->pluck('filterable_id')->toArray();

        if ($min || $max) {
            $dir = $min ? '>' : '<';
            $id = $min ?? $max;
            $timeline = Status::select(
                'id',
                'uri',
                'caption',
                'profile_id',
                'type',
                'in_reply_to_id',
                'reblog_of_id',
                'is_nsfw',
                'scope',
                'local',
                'reply_count',
                'comments_disabled',
                'place_id',
                'likes_count',
                'reblogs_count',
                'created_at',
                'updated_at'
            )->where('id', $dir, $id)
                ->whereIn('type', ['text', 'photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->whereNotIn('profile_id', $filtered)
                ->whereLocal(true)
                ->whereScope('public')
                ->where('created_at', '>', now()->subMonths(3))
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } else {
            $timeline = Status::select(
                'id',
                'uri',
                'caption',
                'profile_id',
                'type',
                'in_reply_to_id',
                'reblog_of_id',
                'is_nsfw',
                'scope',
                'local',
                'reply_count',
                'comments_disabled',
                'created_at',
                'place_id',
                'likes_count',
                'reblogs_count',
                'updated_at'
            )->whereIn('type', ['text', 'photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->whereNotIn('profile_id', $filtered)
                ->with('profile', 'hashtags', 'mentions')
                ->whereLocal(true)
                ->whereScope('public')
                ->where('created_at', '>', now()->subMonths(3))
                ->orderBy('created_at', 'desc')
                ->simplePaginate($limit);
        }

        $fractal = new Fractal\Resource\Collection($timeline, new StatusTimelineTransformer);
        $res = $this->fractal->createData($fractal)->toArray();

        return response()->json($res, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function homeApi(Request $request)
    {
        return [];
    }
}
