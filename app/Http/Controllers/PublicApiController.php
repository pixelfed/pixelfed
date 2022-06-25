<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{
    Hashtag,
    Follower,
    Like,
    Media,
    Notification,
    Profile,
    StatusHashtag,
    Status,
    StatusView,
    UserFilter
};
use Auth, Cache, DB;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use League\Fractal;
use App\Transformer\Api\{
    AccountTransformer,
    RelationshipTransformer,
    StatusTransformer,
    StatusStatelessTransformer
};
use App\Services\{
    AccountService,
    BookmarkService,
    FollowerService,
    LikeService,
    PublicTimelineService,
    ProfileService,
    NetworkTimelineService,
    ReblogService,
    RelationshipService,
    StatusService,
    SnowflakeService,
    UserFilterService
};
use App\Jobs\StatusPipeline\NewStatusPipeline;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

class PublicApiController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    protected function getUserData($user)
    {
        if(!$user) {
            return [];
        } else {
            return AccountService::get($user->profile_id);
        }
    }

    protected function getLikes($status)
    {
        if(false == Auth::check()) {
            return [];
        } else {
            $profile = Auth::user()->profile;
            if($profile->status) {
                return [];
            }
            $likes = $status->likedBy()->orderBy('created_at','desc')->paginate(10);
            $collection = new Fractal\Resource\Collection($likes, new AccountTransformer());
            return $this->fractal->createData($collection)->toArray();
        }
    }

    protected function getShares($status)
    {
        if(false == Auth::check()) {
            return [];
        } else {
            $profile = Auth::user()->profile;
            if($profile->status) {
                return [];
            }
            $shares = $status->sharedBy()->orderBy('created_at','desc')->paginate(10);
            $collection = new Fractal\Resource\Collection($shares, new AccountTransformer());
            return $this->fractal->createData($collection)->toArray();
        }
    }

    public function getStatus(Request $request, $id)
    {
		abort_if(!$request->user(), 403);
		$status = StatusService::get($id, false);
		abort_if(!$status, 404);
		if(in_array($status['visibility'], ['public', 'unlisted'])) {
			return $status;
		}
		$pid = $request->user()->profile_id;
		if($status['account']['id'] == $pid) {
			return $status;
		}
		if($status['visibility'] == 'private') {
			if(FollowerService::follows($pid, $status['account']['id'])) {
				return $status;
			}
		}
		abort(404);
    }

    public function status(Request $request, $username, int $postid)
    {
        $profile = Profile::whereUsername($username)->whereNull('status')->firstOrFail();
        $status = Status::whereProfileId($profile->id)->findOrFail($postid);
        $this->scopeCheck($profile, $status);
        if(!$request->user()) {
            $res = ['status' => StatusService::get($status->id)];
        } else {
            $item = new Fractal\Resource\Item($status, new StatusStatelessTransformer());
            $res = [
                'status' => $this->fractal->createData($item)->toArray(),
            ];
        }

        return response()->json($res);
    }

    public function statusState(Request $request, $username, int $postid)
    {
        $profile = Profile::whereUsername($username)->whereNull('status')->firstOrFail();
        $status = Status::whereProfileId($profile->id)->findOrFail($postid);
        $this->scopeCheck($profile, $status);
        if(!Auth::check()) {
            $res = [
                'user' => [],
                'likes' => [],
                'shares' => [],
                'reactions' => [
                    'liked' => false,
                    'shared' => false,
                    'bookmarked' => false,
                ],
            ];
            return response()->json($res);
        }
        $res = [
            'user' => $this->getUserData($request->user()),
            'likes' => [],
            'shares' => [],
            'reactions' => [
                'liked' => (bool) $status->liked(),
                'shared' => (bool) $status->shared(),
                'bookmarked' => (bool) $status->bookmarked(),
            ],
        ];
        return response()->json($res);
    }

    public function statusComments(Request $request, $username, int $postId)
    {
        $this->validate($request, [
            'min_id'    => 'nullable|integer|min:1',
            'max_id'    => 'nullable|integer|min:1|max:'.PHP_INT_MAX,
            'limit'     => 'nullable|integer|min:5|max:50'
        ]);

        $limit = $request->limit ?? 10;
        $profile = Profile::whereNull('status')->findOrFail($username);
        $status = Status::whereProfileId($profile->id)->whereCommentsDisabled(false)->findOrFail($postId);
        $this->scopeCheck($profile, $status);

        if(Auth::check()) {
            $p = Auth::user()->profile;
            $scope = $p->id == $status->profile_id || FollowerService::follows($p->id, $profile->id) ? ['public', 'private', 'unlisted'] : ['public','unlisted'];
        } else {
            $scope = ['public', 'unlisted'];
        }

        if($request->filled('min_id') || $request->filled('max_id')) {
            if($request->filled('min_id')) {
                $replies = $status->comments()
                ->whereNull('reblog_of_id')
                ->whereIn('scope', $scope)
                ->select('id', 'caption', 'local', 'visibility', 'scope', 'is_nsfw', 'rendered', 'profile_id', 'in_reply_to_id', 'type', 'reply_count', 'created_at')
                ->where('id', '>=', $request->min_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
            if($request->filled('max_id')) {
                $replies = $status->comments()
                ->whereNull('reblog_of_id')
                ->whereIn('scope', $scope)
                ->select('id', 'caption', 'local', 'visibility', 'scope', 'is_nsfw', 'rendered', 'profile_id', 'in_reply_to_id', 'type', 'reply_count', 'created_at')
                ->where('id', '<=', $request->max_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
        } else {
            $replies = Status::whereInReplyToId($status->id)
            ->whereNull('reblog_of_id')
            ->whereIn('scope', $scope)
            ->select('id', 'caption', 'local', 'visibility', 'scope', 'is_nsfw', 'rendered', 'profile_id', 'in_reply_to_id', 'type', 'reply_count', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate($limit);
        }

        $resource = new Fractal\Resource\Collection($replies, new StatusStatelessTransformer(), 'data');
        $resource->setPaginator(new IlluminatePaginatorAdapter($replies));
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function statusLikes(Request $request, $username, $id)
    {
        abort_if(!$request->user(), 404);
        $status = Status::findOrFail($id);
        $this->scopeCheck($status->profile, $status);
        $page = $request->input('page');
        if($page && $page >= 3 && $request->user()->profile_id != $status->profile_id) {
            return response()->json([
                'data' => []
            ]);
        }
        $likes = $this->getLikes($status);
        return response()->json([
            'data' => $likes
        ]);
    }

    public function statusShares(Request $request, $username, $id)
    {
        abort_if(!$request->user(), 404);
        $profile = Profile::whereUsername($username)->whereNull('status')->firstOrFail();
        $status = Status::whereProfileId($profile->id)->findOrFail($id);
        $this->scopeCheck($profile, $status);
        $page = $request->input('page');
        if($page && $page >= 3 && $request->user()->profile_id != $status->profile_id) {
            return response()->json([
                'data' => []
            ]);
        }
        $shares = $this->getShares($status);
        return response()->json([
            'data' => $shares
        ]);
    }

    protected function scopeCheck(Profile $profile, Status $status)
    {
        if($profile->is_private == true && Auth::check() == false) {
            abort(404);
        }

        switch ($status->scope) {
            case 'public':
            case 'unlisted':
                break;
            case 'private':
                $user = Auth::check() ? Auth::user() : false;
                if(!$user) {
                    abort(403);
                } else {
                    $follows = $profile->followedBy($user->profile);
                    if($follows == false && $profile->id !== $user->profile->id && $user->is_admin == false) {
                        abort(404);
                    }
                }
                break;

            case 'direct':
                abort(404);
                break;

            case 'draft':
                abort(404);
                break;

            default:
                abort(404);
                break;
        }
    }

    public function publicTimelineApi(Request $request)
    {
        $this->validate($request,[
          'page'        => 'nullable|integer|max:40',
          'min_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'max_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'limit'       => 'nullable|integer|max:30'
        ]);

        if(!$request->user()) {
            return response('', 403);
        }

        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 3;
        $user = $request->user();
        $filtered = $user ? UserFilterService::filters($user->profile_id) : [];

        if(config('exp.cached_public_timeline') == false) {
            if($min || $max) {
                $dir = $min ? '>' : '<';
                $id = $min ?? $max;
                $timeline = Status::select(
                            'id',
                            'profile_id',
                            'type',
                            'scope',
                            'local'
                          )
                          ->where('id', $dir, $id)
                          ->whereNull(['in_reply_to_id', 'reblog_of_id'])
                          ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                          ->whereLocal(true)
                          ->whereScope('public')
                          ->orderBy('id', 'desc')
                          ->limit($limit)
                          ->get()
                          ->map(function($s) use ($user) {
                               $status = StatusService::getFull($s->id, $user->profile_id);
                               if(!$status) {
                               		return false;
                               }
                               $status['favourited'] = (bool) LikeService::liked($user->profile_id, $s->id);
                               $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $s->id);
                               $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $s->id);
                               return $status;
                          })
                          ->filter(function($s) use($filtered) {
                                return $s && isset($s['account']) && in_array($s['account']['id'], $filtered) == false;
                          })
                          ->values();
                $res = $timeline->toArray();
            } else {
                $timeline = Status::select(
                            'id',
                            'uri',
                            'caption',
                            'rendered',
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
                          )
                          ->whereNull(['in_reply_to_id', 'reblog_of_id'])
                          ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                          ->with('profile', 'hashtags', 'mentions')
                          ->whereLocal(true)
                          ->whereScope('public')
                          ->orderBy('id', 'desc')
                          ->limit($limit)
                          ->get()
                          ->map(function($s) use ($user) {
                               $status = StatusService::getFull($s->id, $user->profile_id);
                               if(!$status) {
                               		return false;
                               }
                               $status['favourited'] = (bool) LikeService::liked($user->profile_id, $s->id);
                               $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $s->id);
                               $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $s->id);
                               return $status;
                          })
                          ->filter(function($s) use($filtered) {
                                return $s && isset($s['account']) && in_array($s['account']['id'], $filtered) == false;
                          })
                          ->values();

                $res = $timeline->toArray();
            }
        } else {
            Cache::remember('api:v1:timelines:public:cache_check', 10368000, function() {
                if(PublicTimelineService::count() == 0) {
                    PublicTimelineService::warmCache(true, 400);
                }
            });

            if ($max) {
                $feed = PublicTimelineService::getRankedMaxId($max, $limit);
            } else if ($min) {
                $feed = PublicTimelineService::getRankedMinId($min, $limit);
            } else {
                $feed = PublicTimelineService::get(0, $limit);
            }

            $res = collect($feed)
            ->take($limit)
            ->map(function($k) use($user) {
                $status = StatusService::get($k);
                if($status && isset($status['account']) && $user) {
                    $status['favourited'] = (bool) LikeService::liked($user->profile_id, $k);
                    $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $k);
                    $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $k);
                    $status['relationship'] = RelationshipService::get($user->profile_id, $status['account']['id']);
                }
                return $status;
            })
            ->filter(function($s) use($filtered) {
                return $s && isset($s['account']) && in_array($s['account']['id'], $filtered) == false;
            })
            ->values()
            ->toArray();
        }

        return response()->json($res);
    }

    public function homeTimelineApi(Request $request)
    {
        if(!$request->user()) {
            return response('', 403);
        }

        $this->validate($request,[
          'page'        => 'nullable|integer|max:40',
          'min_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'max_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'limit'       => 'nullable|integer|max:40',
          'recent_feed' => 'nullable',
          'recent_min'  => 'nullable|integer'
        ]);

        $recentFeed = $request->input('recent_feed') == 'true';
        $recentFeedMin = $request->input('recent_min');
        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 3;
        $user = $request->user();

        $key = 'user:last_active_at:id:'.$user->id;
        $ttl = now()->addMinutes(20);
        Cache::remember($key, $ttl, function() use($user) {
            $user->last_active_at = now();
            $user->save();
            return;
        });

        $pid = $user->profile_id;

        $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
            $following = Follower::whereProfileId($pid)->pluck('following_id');
            return $following->push($pid)->toArray();
        });

        if($recentFeed == true) {
            $key = 'profile:home-timeline-cursor:'.$user->id;
            $ttl = now()->addMinutes(30);
            $min = Cache::remember($key, $ttl, function() use($pid) {
                $res = StatusView::whereProfileId($pid)->orderByDesc('status_id')->first();
                return $res ? $res->status_id : null;
            });
        }

        $filtered = $user ? UserFilterService::filters($user->profile_id) : [];
        $types = ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'];
        // $types = ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album', 'text'];

        $textOnlyReplies = false;

        if(config('exp.top')) {
            $textOnlyPosts = (bool) Redis::zscore('pf:tl:top', $pid);
            $textOnlyReplies = (bool) Redis::zscore('pf:tl:replies', $pid);

            if($textOnlyPosts) {
                array_push($types, 'text');
            }
        }

        if(config('exp.polls') == true) {
            array_push($types, 'poll');
        }

        if($min || $max) {
            $dir = $min ? '>' : '<';
            $id = $min ?? $max;
           	return Status::select(
                        'id',
                        'uri',
                        'caption',
                        'rendered',
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
                      )
                      ->whereIn('type', $types)
                      ->when($textOnlyReplies != true, function($q, $textOnlyReplies) {
                        return $q->whereNull('in_reply_to_id');
                      })
                      ->with('profile', 'hashtags', 'mentions')
                      ->where('id', $dir, $id)
                      ->whereIn('profile_id', $following)
                      ->whereIn('visibility',['public', 'unlisted', 'private'])
                      ->orderBy('created_at', 'desc')
                      ->limit($limit)
                      ->get()
                      ->map(function($s) use ($user) {
                           $status = StatusService::get($s->id, false);
                           if(!$status) {
                           		return false;
                           }
                           $status['favourited'] = (bool) LikeService::liked($user->profile_id, $s->id);
                           $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $s->id);
                           $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $s->id);
                           return $status;
                      })
                      ->filter(function($s) use($filtered) {
                            return $s && in_array($s['account']['id'], $filtered) == false;
                      })
                      ->values()
                      ->toArray();
        } else {
            return Status::select(
                        'id',
                        'uri',
                        'caption',
                        'rendered',
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
                      )
                      ->whereIn('type', $types)
                      ->when(!$textOnlyReplies, function($q, $textOnlyReplies) {
                        return $q->whereNull('in_reply_to_id');
                      })
                      ->with('profile', 'hashtags', 'mentions')
                      ->whereIn('profile_id', $following)
                      ->whereIn('visibility',['public', 'unlisted', 'private'])
                      ->orderBy('created_at', 'desc')
                      ->limit($limit)
                      ->get()
                      ->map(function($s) use ($user) {
                           $status = StatusService::get($s->id, false);
                           if(!$status) {
                           		return false;
                           }
                           $status['favourited'] = (bool) LikeService::liked($user->profile_id, $s->id);
                           $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $s->id);
                           $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $s->id);
                           return $status;
                      })
                      ->filter(function($s) use($filtered) {
                            return $s && in_array($s['account']['id'], $filtered) == false;
                      })
                      ->values()
                      ->toArray();
        }
    }

    public function networkTimelineApi(Request $request)
    {
        if(!$request->user()) {
            return response('', 403);
        }

        abort_if(config('federation.network_timeline') == false, 404);

        $this->validate($request,[
          'page'        => 'nullable|integer|max:40',
          'min_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'max_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'limit'       => 'nullable|integer|max:30'
        ]);

        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 3;
        $user = $request->user();
        $amin = SnowflakeService::byDate(now()->subDays(config('federation.network_timeline_days_falloff')));

        $filtered = $user ? UserFilterService::filters($user->profile_id) : [];

        if(config('instance.timeline.network.cached') == false) {
	        if($min || $max) {
	            $dir = $min ? '>' : '<';
	            $id = $min ?? $max;
	            $timeline = Status::select(
	                        'id',
	                        'uri',
	                        'type',
	                        'scope',
	                        'created_at',
	                      )
	                      ->where('id', $dir, $id)
	                      ->whereNull(['in_reply_to_id', 'reblog_of_id'])
	                      ->whereNotIn('profile_id', $filtered)
	                      ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
	                      ->whereNotNull('uri')
	                      ->whereScope('public')
	                      ->where('id', '>', $amin)
	                      ->orderBy('created_at', 'desc')
	                      ->limit($limit)
	                      ->get()
	                     ->map(function($s) use ($user) {
	                            $status = StatusService::get($s->id);
	                            $status['favourited'] = (bool) LikeService::liked($user->profile_id, $s->id);
	                            $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $s->id);
	                            $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $s->id);
	                            return $status;
	                      });
	            $res = $timeline->toArray();
	        } else {
	                $timeline = Status::select(
	                            'id',
	                            'uri',
	                            'type',
	                            'scope',
	                            'created_at',
	                          )
	                      	  ->whereNull(['in_reply_to_id', 'reblog_of_id'])
	                          ->whereNotIn('profile_id', $filtered)
	                          ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
	                          ->whereNotNull('uri')
	                          ->whereScope('public')
	                          ->where('id', '>', $amin)
	                          ->orderBy('created_at', 'desc')
	                          ->limit($limit)
	                          ->get()
	                          ->map(function($s) use ($user) {
	                                $status = StatusService::get($s->id);
	                                $status['favourited'] = (bool) LikeService::liked($user->profile_id, $s->id);
	                                $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $s->id);
	                                $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $s->id);
	                                return $status;
	                          });
	                $res = $timeline->toArray();
	        }
	    } else {
            Cache::remember('api:v1:timelines:network:cache_check', 10368000, function() {
                if(NetworkTimelineService::count() == 0) {
                    NetworkTimelineService::warmCache(true, 400);
                }
            });

            if ($max) {
                $feed = NetworkTimelineService::getRankedMaxId($max, $limit);
            } else if ($min) {
                $feed = NetworkTimelineService::getRankedMinId($min, $limit);
            } else {
                $feed = NetworkTimelineService::get(0, $limit);
            }

            $res = collect($feed)
            ->take($limit)
            ->map(function($k) use($user) {
                $status = StatusService::get($k);
                if($status && isset($status['account']) && $user) {
                    $status['favourited'] = (bool) LikeService::liked($user->profile_id, $k);
                    $status['bookmarked'] = (bool) BookmarkService::get($user->profile_id, $k);
                    $status['reblogged'] = (bool) ReblogService::get($user->profile_id, $k);
                    $status['relationship'] = RelationshipService::get($user->profile_id, $status['account']['id']);
                }
                return $status;
            })
            ->filter(function($s) use($filtered) {
                return $s && isset($s['account']) && in_array($s['account']['id'], $filtered) == false;
            })
            ->values()
            ->toArray();
        }

        return response()->json($res);
    }

    public function relationships(Request $request)
    {
        if(!Auth::check()) {
            return response()->json([]);
        }

        $pid = $request->user()->profile_id;

        $this->validate($request, [
            'id'    => 'required|array|min:1|max:20',
            'id.*'  => 'required|integer'
        ]);
        $ids = collect($request->input('id'));
        $res = $ids->filter(function($v) use($pid) {
            return $v != $pid;
        })
        ->map(function($id) use($pid) {
            return RelationshipService::get($pid, $id);
        });

        return response()->json($res);
    }

    public function account(Request $request, $id)
    {
        $res = AccountService::get($id);
        return response()->json($res);
    }

    public function accountFollowers(Request $request, $id)
    {
		abort_if(!$request->user(), 403);
		$account = AccountService::get($id);
		abort_if(!$account, 404);
		$pid = $request->user()->profile_id;

		if($pid != $account['id']) {
			if($account['locked']) {
				if(!FollowerService::follows($pid, $account['id'])) {
					return [];
				}
			}

			if(AccountService::hiddenFollowers($id)) {
				return [];
			}

			if($request->has('page') && $request->page >= 5) {
				return [];
			}
		}

		$res = DB::table('followers')
			->select('id', 'profile_id', 'following_id')
			->whereFollowingId($account['id'])
			->orderByDesc('id')
			->simplePaginate(10)
			->map(function($follower) {
				return AccountService::get($follower->profile_id);
			})
			->filter(function($account) {
				return $account && isset($account['id']);
			})
			->values()
			->toArray();

		return response()->json($res);
    }

    public function accountFollowing(Request $request, $id)
    {
		abort_if(!$request->user(), 403);
		$account = AccountService::get($id);
		abort_if(!$account, 404);
		$pid = $request->user()->profile_id;

		if($pid != $account['id']) {
			if($account['locked']) {
				if(!FollowerService::follows($pid, $account['id'])) {
					return [];
				}
			}

			if(AccountService::hiddenFollowing($id)) {
				return [];
			}

			if($request->has('page') && $request->page >= 5) {
				return [];
			}
		}

		$res = DB::table('followers')
			->select('id', 'profile_id', 'following_id')
			->whereProfileId($account['id'])
			->orderByDesc('id')
			->simplePaginate(10)
			->map(function($follower) {
				return AccountService::get($follower->following_id);
			})
			->filter(function($account) {
				return $account && isset($account['id']);
			})
			->values()
			->toArray();

		return response()->json($res);
    }

    public function accountStatuses(Request $request, $id)
    {
        $this->validate($request, [
            'only_media' => 'nullable',
            'pinned' => 'nullable',
            'exclude_replies' => 'nullable',
            'max_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
            'since_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
            'min_id' => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
            'limit' => 'nullable|integer|min:1|max:24'
        ]);

        $user = $request->user();
        $profile = AccountService::get($id);
        abort_if(!$profile, 404);

        $limit = $request->limit ?? 9;
        $max_id = $request->max_id;
        $min_id = $request->min_id;
        $scope = ['photo', 'photo:album', 'video', 'video:album'];
        $onlyMedia = $request->input('only_media', true);

        if(!$min_id && !$max_id) {
        	$min_id = 1;
        }

        if($profile['locked']) {
            if(!$user) {
                return response()->json([]);
            }
            $pid = $user->profile_id;
            $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
                $following = Follower::whereProfileId($pid)->pluck('following_id');
                return $following->push($pid)->toArray();
            });
            $visibility = true == in_array($profile['id'], $following) ? ['public', 'unlisted', 'private'] : [];
        } else {
            if($user) {
                $pid = $user->profile_id;
                $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
                    $following = Follower::whereProfileId($pid)->pluck('following_id');
                    return $following->push($pid)->toArray();
                });
                $visibility = true == in_array($profile['id'], $following) ? ['public', 'unlisted', 'private'] : ['public', 'unlisted'];
            } else {
                $visibility = ['public', 'unlisted'];
            }
        }
        $dir = $min_id ? '>' : '<';
        $id = $min_id ?? $max_id;
        $res = Status::whereProfileId($profile['id'])
        ->whereNull('in_reply_to_id')
        ->whereNull('reblog_of_id')
        ->whereIn('type', $scope)
        ->where('id', $dir, $id)
        ->whereIn('scope', $visibility)
        ->limit($limit)
        ->orderByDesc('id')
        ->get()
        ->map(function($s) use($user) {
            try {
                $status = StatusService::get($s->id, false);
            } catch (\Exception $e) {
                $status = false;
            }
            if($user && $status) {
                $status['favourited'] = (bool) LikeService::liked($user->profile_id, $s->id);
            }
            return $status;
        })
        ->filter(function($s) use($onlyMedia) {
        	if($onlyMedia) {
        		if(
        			!isset($s['media_attachments']) ||
        			!is_array($s['media_attachments']) ||
        			empty($s['media_attachments'])
        		) {
        			return false;
        		}
        	}
            return $s;
        })
        ->values();

        return response()->json($res);
    }
}
