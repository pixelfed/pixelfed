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
use App\Services\WebfingerService;

class SearchController extends Controller
{
    public $tokens = [];
    public $term = '';
    public $hash = '';
    public $cacheKey = 'api:search:tag:';

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function searchAPI(Request $request)
    {
        $this->validate($request, [
            'q' => 'required|string|min:3|max:120',
            'src' => 'required|string|in:metro',
            'v' => 'required|integer|in:1',
            'scope' => 'required|in:all,hashtag,profile,remote,webfinger'
        ]);

        $scope = $request->input('scope') ?? 'all';
        $this->term = e(urldecode($request->input('q')));
        $this->hash = hash('sha256', $this->term);

        switch ($scope) {
            case 'all':
                $this->getHashtags();
                $this->getPosts();
                $this->getProfiles();
                break;

            case 'hashtag':
                $this->getHashtags();
                break;

            case 'profile':
                $this->getProfiles();
                break;

            case 'webfinger':
                $this->webfingerSearch();
                break;

            default:
                break;
        }

        return response()->json($this->tokens, 200, [], JSON_PRETTY_PRINT);
    }

    protected function getPosts()
    {
        $tag = $this->term;
        $hash = hash('sha256', $tag);
        if( Helpers::validateUrl($tag) != false && 
            Helpers::validateLocalUrl($tag) != true && 
            config('federation.activitypub.enabled') == true && 
            config('federation.activitypub.remoteFollow') == true
        ) {
            $remote = Helpers::fetchFromUrl($tag);
            if( isset($remote['type']) && 
                $remote['type'] == 'Note') {
                $item = Helpers::statusFetch($tag);
                $this->tokens['posts'] = [[
                    'count'  => 0,
                    'url'    => $item->url(),
                    'type'   => 'status',
                    'value'  => "by {$item->profile->username} <span class='float-right'>{$item->created_at->diffForHumans(null, true, true)}</span>",
                    'tokens' => [$item->caption],
                    'name'   => $item->caption,
                    'thumb'  => $item->thumb(),
                ]];
            }
        } else {
            $posts = Status::select('id', 'profile_id', 'caption', 'created_at')
                        ->whereHas('media')
                        ->whereNull('in_reply_to_id')
                        ->whereNull('reblog_of_id')
                        ->whereProfileId(Auth::user()->profile_id)
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
                $this->tokens['posts'] = $posts;
            }
        }
    }

    protected function getHashtags()
    {
        $tag = $this->term;
        $key = $this->cacheKey . 'hashtags:' . $this->hash;
        $ttl = now()->addMinutes(1);
        $tokens = Cache::remember($key, $ttl, function() use($tag) {
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
                return $tags;
            }
        });
        $this->tokens['hashtags'] = $tokens;
    }

    protected function getProfiles()
    {
        $tag = $this->term;
        $remoteKey = $this->cacheKey . 'profiles:remote:' . $this->hash;
        $key = $this->cacheKey . 'profiles:' . $this->hash;
        $remoteTtl = now()->addMinutes(15);
        $ttl = now()->addHours(2);
        if( Helpers::validateUrl($tag) != false && 
            Helpers::validateLocalUrl($tag) != true && 
            config('federation.activitypub.enabled') == true && 
            config('federation.activitypub.remoteFollow') == true
        ) {
            $remote = Helpers::fetchFromUrl($tag);
            if( isset($remote['type']) && 
                $remote['type'] == 'Person'
            ) {
                $this->tokens['profiles'] = Cache::remember($remoteKey, $remoteTtl, function() use($tag) {
                    $item = Helpers::profileFirstOrNew($tag);
                    $tokens = [[
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
                    return $tokens;
                });
            }
        } 
        // elseif( Str::containsAll($tag, ['@', '.'])
        //     config('federation.activitypub.enabled') == true && 
        //     config('federation.activitypub.remoteFollow') == true
        // ) {
        //     if(substr_count($tag, '@') == 2) {
        //         $domain = last(explode('@', sub_str($u, 1)));
        //     } else {
        //         $domain = last(explode('@', $u));
        //     }

        // } 
        else {
            $this->tokens['profiles'] = Cache::remember($key, $ttl, function() use($tag) {
                $users = Profile::select('domain', 'username', 'name', 'id')
                    ->whereNull('status')
                    ->where('id', '!=', Auth::user()->profile->id)
                    ->where('username', 'like', '%'.$tag.'%')
                    ->limit(20)
                    ->get();

                if($users->count() > 0) {
                    return $users->map(function ($item, $key) {
                        return [
                            'count'  => 0,
                            'url'    => $item->url(),
                            'type'   => 'profile',
                            'value'  => $item->username,
                            'tokens' => [$item->username],
                            'name'   => $item->name,
                            'avatar' => $item->avatarUrl(),
                            'id'     =>  (string) $item->id,
                            'entity' => [
                                'id' => (string) $item->id,
                                'following' => $item->followedBy(Auth::user()->profile),
                                'follow_request' => $item->hasFollowRequestById(Auth::user()->profile_id),
                                'thumb' => $item->avatarUrl(),
                                'local' => (bool) !$item->domain,
                                'post_count' => $item->statuses()->count()
                            ]
                        ];
                    });
                }
            });
        }
    }

    public function results(Request $request)
    {
        $this->validate($request, [
            'q' => 'required|string|min:1',
        ]);
        
        return view('search.results');
    }

    protected function webfingerSearch()
    {
        $wfs = WebfingerService::lookup($this->term);

        if(empty($wfs)) {
            return;
        }

        $this->tokens['profiles'] = [
            [
                'count'  => 1,
                'url'    => $wfs['url'],
                'type'   => 'profile',
                'value'  => $wfs['username'],
                'tokens' => [$wfs['username']],
                'name'   => $wfs['display_name'],
                'entity' => [
                    'id' => (string) $wfs['id'],
                    'following' => null,
                    'follow_request' => null,
                    'thumb' => $wfs['avatar'],
                    'local' => (bool) $wfs['local']
                ]
            ]
        ];
        return;
    }
}