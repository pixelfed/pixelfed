<?php

namespace App\Http\Controllers;

use Auth;
use App\Hashtag;
use App\Profile;
use App\Status;
use Illuminate\Http\Request;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Transformer\Api\{
    AccountTransformer,
    HashtagTransformer,
    StatusTransformer,
};

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function searchAPI(Request $request)
    {
        $this->validate($request, [
            'q' => 'required|string|min:3|max:120',
            'src' => 'required|string|in:metro',
            'v' => 'required|integer|in:1'
        ]);
        $tag = $request->input('q');
        $tag = e(urldecode($tag));

        $hash = hash('sha256', $tag);
        $tokens = Cache::remember('api:search:tag:'.$hash, now()->addMinutes(5), function () use ($tag) {
            $tokens = [];
            if(Helpers::validateUrl($tag) != false && config('federation.activitypub.enabled') == true && config('federation.activitypub.remoteFollow') == true) {
                abort_if(Helpers::validateLocalUrl($tag), 404);
                $remote = Helpers::fetchFromUrl($tag);
                if(isset($remote['type']) && in_array($remote['type'], ['Note', 'Person']) == true) {
                    $type = $remote['type'];
                    if($type == 'Person') {
                        $item = Helpers::profileFirstOrNew($tag);
                        $tokens['profiles'] = [[
                            'count'  => 1,
                            'url'    => $item->url(),
                            'type'   => 'profile',
                            'value'  => $item->username,
                            'tokens' => [$item->username],
                            'name'   => $item->name,
                            'entity' => [
                                'id' => (string) $item->id,
                                'following' => $item->followedBy(Auth::user()->profile),
                                'follow_request' => $item->hasFollowRequestById(Auth::user()->profile_id),
                                'thumb' => $item->avatarUrl(),
                                'local' => (bool) !$item->domain
                            ]
                        ]];
                    } elseif ($type == 'Note') {
                        $item = Helpers::statusFetch($tag);
                        $tokens['posts'] = [[
                            'count'  => 0,
                            'url'    => $item->url(),
                            'type'   => 'status',
                            'value'  => "by {$item->profile->username} <span class='float-right'>{$item->created_at->diffForHumans(null, true, true)}</span>",
                            'tokens' => [$item->caption],
                            'name'   => $item->caption,
                            'thumb'  => $item->thumb(),
                        ]];
                    }
                }
            }
            $htag = Str::startsWith($tag, '#') == true ? mb_substr($tag, 1) : $tag;
            $hashtags = Hashtag::select('id', 'name', 'slug')
                ->where('slug', 'like', '%'.$htag.'%')
                ->whereHas('posts')
                ->limit(20)
                ->get();
            if($hashtags->count() > 0) {
                $tags = $hashtags->map(function ($item, $key) {
                    return [
                        'count'  => $item->posts()->count(),
                        'url'    => $item->url(),
                        'type'   => 'hashtag',
                        'value'  => $item->name,
                        'tokens' => '',
                        'name'   => null,
                    ];
                });
                $tokens['hashtags'] = $tags;
            }
            return $tokens;
        });
        $users = Profile::select('domain', 'username', 'name', 'id')
            ->whereNull('status')
            ->whereNull('domain')
            ->where('id', '!=', Auth::user()->profile->id)
            ->where('username', 'like', '%'.$tag.'%')
            //->orWhere('remote_url', $tag)
            ->limit(20)
            ->get();

        if($users->count() > 0) {
            $profiles = $users->map(function ($item, $key) {
                return [
                    'count'  => 0,
                    'url'    => $item->url(),
                    'type'   => 'profile',
                    'value'  => $item->username,
                    'tokens' => [$item->username],
                    'name'   => $item->name,
                    'avatar' => $item->avatarUrl(),
                    'id'     =>  $item->id,
                    'entity' => [
                        'id' => (string) $item->id,
                        'following' => $item->followedBy(Auth::user()->profile),
                        'follow_request' => $item->hasFollowRequestById(Auth::user()->profile_id),
                        'thumb' => $item->avatarUrl(),
                        'local' => (bool) !$item->domain
                    ]
                ];
            });
            if(isset($tokens['profiles'])) {
                array_push($tokens['profiles'], $profiles);
            } else {
                $tokens['profiles'] = $profiles;
            }
        }
        $posts = Status::select('id', 'profile_id', 'caption', 'created_at')
                    ->whereHas('media')
                    ->whereNull('in_reply_to_id')
                    ->whereNull('reblog_of_id')
                    ->whereProfileId(Auth::user()->profile->id)
                    ->where('caption', 'like', '%'.$tag.'%')
                    ->latest()
                    ->limit(10)
                    ->get();

        if($posts->count() > 0) {
            $posts = $posts->map(function($item, $key) {
                return [
                    'count'  => 0,
                    'url'    => $item->url(),
                    'type'   => 'status',
                    'value'  => "by {$item->profile->username} <span class='float-right'>{$item->created_at->diffForHumans(null, true, true)}</span>",
                    'tokens' => [$item->caption],
                    'name'   => $item->caption,
                    'thumb'  => $item->thumb(),
                    'filter' => $item->firstMedia()->filter_class
                ];
            });
            $tokens['posts'] = $posts;
        }

        return response()->json($tokens);
    }

    public function results(Request $request)
    {
        $this->validate($request, [
            'q' => 'required|string|min:1',
        ]);
        
        return view('search.results');
    }

}
