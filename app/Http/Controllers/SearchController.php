<?php

namespace App\Http\Controllers;

use App\Hashtag;
use App\Place;
use App\Profile;
use App\Services\WebfingerService;
use App\Status;
use App\Util\ActivityPub\Helpers;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
            'v' => 'required|integer|in:2',
            'scope' => 'required|in:all,hashtag,profile,remote,webfinger',
        ]);

        $scope = $request->input('scope') ?? 'all';
        $this->term = e(urldecode($request->input('q')));
        $this->hash = hash('sha256', $this->term);

        switch ($scope) {
            case 'all':
                $this->getHashtags();
                $this->getPosts();
                $this->getProfiles();
                // $this->getPlaces();
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

            case 'remote':
                $this->remoteLookupSearch();
                break;

            case 'place':
                $this->getPlaces();
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
        if (Helpers::validateUrl($tag) != false &&
            Helpers::validateLocalUrl($tag) != true &&
            config_cache('federation.activitypub.enabled') == true &&
            config('federation.activitypub.remoteFollow') == true
        ) {
            $remote = Helpers::fetchFromUrl($tag);
            if (isset($remote['type']) &&
                $remote['type'] == 'Note') {
                $item = Helpers::statusFetch($tag);
                $this->tokens['posts'] = [[
                    'count' => 0,
                    'url' => $item->url(),
                    'type' => 'status',
                    'value' => "by {$item->profile->username} <span class='float-right'>{$item->created_at->diffForHumans(null, true, true)}</span>",
                    'tokens' => [$item->caption],
                    'name' => $item->caption,
                    'thumb' => $item->thumb(),
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

            if ($posts->count() > 0) {
                $posts = $posts->map(function ($item, $key) {
                    return [
                        'count' => 0,
                        'url' => $item->url(),
                        'type' => 'status',
                        'value' => "by {$item->profile->username} <span class='float-right'>{$item->created_at->diffForHumans(null, true, true)}</span>",
                        'tokens' => [$item->caption],
                        'name' => $item->caption,
                        'thumb' => $item->thumb(),
                        'filter' => $item->firstMedia()->filter_class,
                    ];
                });
                $this->tokens['posts'] = $posts;
            }
        }
    }

    protected function getHashtags()
    {
        $tag = $this->term;
        $key = $this->cacheKey.'hashtags:'.$this->hash;
        $ttl = now()->addMinutes(1);
        $tokens = Cache::remember($key, $ttl, function () use ($tag) {
            $htag = Str::startsWith($tag, '#') == true ? mb_substr($tag, 1) : $tag;
            $hashtags = Hashtag::select('id', 'name', 'slug')
                ->where('slug', 'like', '%'.$htag.'%')
                ->whereHas('posts')
                ->limit(20)
                ->get();
            if ($hashtags->count() > 0) {
                $tags = $hashtags->map(function ($item, $key) {
                    return [
                        'count' => $item->posts()->count(),
                        'url' => $item->url(),
                        'type' => 'hashtag',
                        'value' => $item->name,
                        'tokens' => '',
                        'name' => null,
                    ];
                });

                return $tags;
            }
        });
        $this->tokens['hashtags'] = $tokens;
    }

    protected function getPlaces()
    {
        $tag = $this->term;
        // $key = $this->cacheKey . 'places:' . $this->hash;
        // $ttl = now()->addHours(12);
        // $tokens = Cache::remember($key, $ttl, function() use($tag) {
        $htag = Str::contains($tag, ',') == true ? explode(',', $tag) : [$tag];
        $hashtags = Place::select('id', 'name', 'slug', 'country')
            ->where('name', 'like', '%'.$htag[0].'%')
            ->paginate(20);
        $tags = [];
        if ($hashtags->count() > 0) {
            $tags = $hashtags->map(function ($item, $key) {
                return [
                    'count' => null,
                    'url' => $item->url(),
                    'type' => 'place',
                    'value' => $item->name.', '.$item->country,
                    'tokens' => '',
                    'name' => null,
                    'city' => $item->name,
                    'country' => $item->country,
                ];
            });
            // return $tags;
        }
        // });
        $this->tokens['places'] = $tags;
        $this->tokens['placesPagination'] = [
            'total' => $hashtags->total(),
            'current_page' => $hashtags->currentPage(),
            'last_page' => $hashtags->lastPage(),
        ];
    }

    protected function getProfiles()
    {
        $tag = $this->term;
        $remoteKey = $this->cacheKey.'profiles:remote:'.$this->hash;
        $key = $this->cacheKey.'profiles:'.$this->hash;
        $remoteTtl = now()->addMinutes(15);
        $ttl = now()->addHours(2);
        if (Helpers::validateUrl($tag) != false &&
            Helpers::validateLocalUrl($tag) != true &&
            config_cache('federation.activitypub.enabled') == true &&
            config('federation.activitypub.remoteFollow') == true
        ) {
            $remote = Helpers::fetchFromUrl($tag);
            if (isset($remote['type']) &&
                $remote['type'] == 'Person'
            ) {
                $this->tokens['profiles'] = Cache::remember($remoteKey, $remoteTtl, function () use ($tag) {
                    $item = Helpers::profileFirstOrNew($tag);
                    $tokens = [[
                        'count' => 1,
                        'url' => $item->url(),
                        'type' => 'profile',
                        'value' => $item->username,
                        'tokens' => [$item->username],
                        'name' => $item->name,
                        'entity' => [
                            'id' => (string) $item->id,
                            'following' => $item->followedBy(Auth::user()->profile),
                            'follow_request' => $item->hasFollowRequestById(Auth::user()->profile_id),
                            'thumb' => $item->avatarUrl(),
                            'local' => (bool) ! $item->domain,
                            'post_count' => $item->statuses()->count(),
                        ],
                    ]];

                    return $tokens;
                });
            }
        } else {
            $this->tokens['profiles'] = Cache::remember($key, $ttl, function () use ($tag) {
                if (Str::startsWith($tag, '@')) {
                    $tag = substr($tag, 1);
                }
                $users = Profile::select('status', 'domain', 'username', 'name', 'id')
                    ->whereNull('status')
                    ->where('username', 'like', '%'.$tag.'%')
                    ->limit(20)
                    ->orderBy('domain')
                    ->get();

                if ($users->count() > 0) {
                    return $users->map(function ($item, $key) {
                        return [
                            'count' => 0,
                            'url' => $item->url(),
                            'type' => 'profile',
                            'value' => $item->username,
                            'tokens' => [$item->username],
                            'name' => $item->name,
                            'avatar' => $item->avatarUrl(),
                            'id' => (string) $item->id,
                            'entity' => [
                                'id' => (string) $item->id,
                                'following' => $item->followedBy(Auth::user()->profile),
                                'follow_request' => $item->hasFollowRequestById(Auth::user()->profile_id),
                                'thumb' => $item->avatarUrl(),
                                'local' => (bool) ! $item->domain,
                                'post_count' => $item->statuses()->count(),
                            ],
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

        if (empty($wfs)) {
            return;
        }

        $this->tokens['profiles'] = [
            [
                'count' => 1,
                'url' => $wfs['url'],
                'type' => 'profile',
                'value' => $wfs['username'],
                'tokens' => [$wfs['username']],
                'name' => $wfs['display_name'],
                'entity' => [
                    'id' => (string) $wfs['id'],
                    'following' => null,
                    'follow_request' => null,
                    'thumb' => $wfs['avatar'],
                    'local' => (bool) $wfs['local'],
                ],
            ],
        ];

    }

    protected function remotePostLookup()
    {
        $tag = $this->term;
        $hash = hash('sha256', $tag);
        $local = Helpers::validateLocalUrl($tag);
        $valid = Helpers::validateUrl($tag);

        if ($valid == false || $local == true) {
            return;
        }

        if (Status::whereUri($tag)->whereLocal(false)->exists()) {
            $item = Status::whereUri($tag)->first();
            $media = $item->firstMedia();
            $url = null;
            if ($media) {
                $url = $media->remote_url;
            }
            $this->tokens['posts'] = [[
                'count' => 0,
                'url' => "/i/web/post/_/$item->profile_id/$item->id",
                'type' => 'status',
                'username' => $item->profile->username,
                'caption' => $item->rendered ?? $item->caption,
                'thumb' => $url,
                'timestamp' => $item->created_at->diffForHumans(),
            ]];
        }

        $remote = Helpers::fetchFromUrl($tag);

        if (isset($remote['type']) && $remote['type'] == 'Note') {
            $item = Helpers::statusFetch($tag);
            $media = $item->firstMedia();
            $url = null;
            if ($media) {
                $url = $media->remote_url;
            }
            $this->tokens['posts'] = [[
                'count' => 0,
                'url' => "/i/web/post/_/$item->profile_id/$item->id",
                'type' => 'status',
                'username' => $item->profile->username,
                'caption' => $item->rendered ?? $item->caption,
                'thumb' => $url,
                'timestamp' => $item->created_at->diffForHumans(),
            ]];
        }
    }

    protected function remoteLookupSearch()
    {
        if (! Helpers::validateUrl($this->term)) {
            return;
        }
        $this->getProfiles();
        $this->remotePostLookup();
    }
}
