<?php

namespace App\Http\Controllers;

use Auth;
use App\Hashtag;
use App\Profile;
use App\Status;
use Illuminate\Http\Request;
use App\Util\ActivityPub\Helpers;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function searchAPI(Request $request, $tag)
    {
        if(mb_strlen($tag) < 3) {
            return;
        }
        $hash = hash('sha256', $tag);
        $tokens = Cache::remember('api:search:tag:'.$hash, now()->addMinutes(5), function () use ($tag) {
            $tokens = collect([]);
            if(Helpers::validateUrl($tag)) {
                $remote = Helpers::fetchFromUrl($tag);
                if(isset($remote['type']) && in_array($remote['type'], ['Create', 'Person']) == true) {
                    $type = $remote['type'];
                    if($type == 'Person') {
                        $item = Helpers::profileFirstOrNew($tag);
                        $tokens->push([[
                            'count'  => 1,
                            'url'    => $item->url(),
                            'type'   => 'profile',
                            'value'  => $item->username,
                            'tokens' => [$item->username],
                            'name'   => $item->name,
                        ]]);
                    } else if ($type == 'Create') {
                        $item = Helpers::statusFirstOrFetch($tag, false);
                        $tokens->push([[
                            'count'  => 0,
                            'url'    => $item->url(),
                            'type'   => 'status',
                            'value'  => "by {$item->profile->username} <span class='float-right'>{$item->created_at->diffForHumans(null, true, true)}</span>",
                            'tokens' => [$item->caption],
                            'name'   => $item->caption,
                            'thumb'  => $item->thumb(),
                        ]]);
                    }
                }

            }
            $hashtags = Hashtag::select('id', 'name', 'slug')->where('slug', 'like', '%'.$tag.'%')->whereHas('posts')->limit(20)->get();
            if($hashtags->count() > 0) {
                $tags = $hashtags->map(function ($item, $key) {
                    return [
                        'count'  => $item->posts()->count(),
                        'url'    => $item->url(),
                        'type'   => 'hashtag',
                        'value'  => $item->name,
                        'tokens' => explode('-', $item->name),
                        'name'   => null,
                    ];
                });
                $tokens->push($tags);
            }
            $users = Profile::select('username', 'name', 'id')
                ->whereNull('status')
                ->whereNull('domain')
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
                        'id'     =>  $item->id
                    ];
                });
                $tokens->push($profiles);
            }

            return $tokens;
        });
        $posts = Status::select('id', 'profile_id', 'caption', 'created_at')
                    ->whereHas('media')
                    ->whereNull('in_reply_to_id')
                    ->whereNull('reblog_of_id')
                    ->whereProfileId(Auth::user()->profile->id)
                    ->where('caption', 'like', '%'.$tag.'%')
                    ->orWhere('uri', $tag)
                    ->orderBy('created_at', 'desc')
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
                ];
            });
            $tokens = $tokens->push($posts);
        }
        if($tokens->count() > 0) {
            $tokens = $tokens[0];
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
