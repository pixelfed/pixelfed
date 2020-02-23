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
    UserFilter
};
use Auth,Cache;
use Carbon\Carbon;
use League\Fractal;
use App\Transformer\Api\{
    AccountTransformer,
    RelationshipTransformer,
    StatusTransformer,
};
use App\Services\{
    AccountService,
    PublicTimelineService,
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

    public function status(Request $request, $username, int $postid)
    {
        $profile = Profile::whereUsername($username)->whereNull('status')->firstOrFail();
        $status = Status::whereProfileId($profile->id)->findOrFail($postid);
        $this->scopeCheck($profile, $status);
        $item = new Fractal\Resource\Item($status, new StatusTransformer());
        $res = [
        	'status' => $this->fractal->createData($item)->toArray(),
        	'user' => $this->getUserData($request->user()),
            'likes' => $this->getLikes($status),
            'shares' => $this->getShares($status),
            'reactions' => [
                'liked' => $status->liked(),
                'shared' => $status->shared(),
                'bookmarked' => $status->bookmarked(),
            ],
        ];
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function statusComments(Request $request, $username, int $postId)
    {
        $this->validate($request, [
            'min_id'    => 'nullable|integer|min:1',
            'max_id'    => 'nullable|integer|min:1|max:'.PHP_INT_MAX,
            'limit'     => 'nullable|integer|min:5|max:50'
        ]);

        $limit = $request->limit ?? 10;
        $profile = Profile::whereUsername($username)->whereNull('status')->firstOrFail();
        $status = Status::whereProfileId($profile->id)->whereCommentsDisabled(false)->findOrFail($postId);
        $this->scopeCheck($profile, $status);

        if(Auth::check()) {
            $p = Auth::user()->profile;
            $filtered = UserFilter::whereUserId($p->id)
            ->whereFilterableType('App\Profile')
            ->whereIn('filter_type', ['mute', 'block'])
            ->pluck('filterable_id')->toArray();
            $scope = $p->id == $status->profile_id ? ['public', 'private'] : ['public'];
        } else {
            $filtered = [];
            $scope = ['public'];
        }

        if($request->filled('min_id') || $request->filled('max_id')) {
            if($request->filled('min_id')) {
                $replies = $status->comments()
                ->whereNull('reblog_of_id')
                ->whereIn('scope', $scope)
                ->whereNotIn('profile_id', $filtered)
                ->select('id', 'caption', 'is_nsfw', 'rendered', 'profile_id', 'in_reply_to_id', 'type', 'reply_count', 'created_at')
                ->where('id', '>=', $request->min_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
            if($request->filled('max_id')) {
                $replies = $status->comments()
                ->whereNull('reblog_of_id')
                ->whereIn('scope', $scope)
                ->whereNotIn('profile_id', $filtered)
                ->select('id', 'caption', 'is_nsfw', 'rendered', 'profile_id', 'in_reply_to_id', 'type', 'reply_count', 'created_at')
                ->where('id', '<=', $request->max_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
        } else {
            $replies = $status->comments()
            ->whereNull('reblog_of_id')
            ->whereIn('scope', $scope)
            ->whereNotIn('profile_id', $filtered)
            ->select('id', 'caption', 'is_nsfw', 'rendered', 'profile_id', 'in_reply_to_id', 'type', 'reply_count', 'created_at')
            ->orderBy('id', 'desc')
            ->paginate($limit);
        }

        $resource = new Fractal\Resource\Collection($replies, new StatusTransformer(), 'data');
        $resource->setPaginator(new IlluminatePaginatorAdapter($replies));
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function statusLikes(Request $request, $username, $id)
    {
        $profile = Profile::whereUsername($username)->whereNull('status')->firstOrFail();
        $status = Status::whereProfileId($profile->id)->findOrFail($id);
        $this->scopeCheck($profile, $status);
        $likes = $this->getLikes($status);
        return response()->json([
            'data' => $likes
        ]);
    }

    public function statusShares(Request $request, $username, $id)
    {
        $profile = Profile::whereUsername($username)->whereNull('status')->firstOrFail();
        $status = Status::whereProfileId($profile->id)->findOrFail($id);
        $this->scopeCheck($profile, $status);
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

        if(config('instance.timeline.local.is_public') == false && !Auth::check()) {
            abort(403, 'Authentication required.');
        }

        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 3;

        $private = Cache::remember('profiles:private', now()->addMinutes(1440), function() {
            return Profile::whereIsPrivate(true)
                ->orWhere('unlisted', true)
                ->orWhere('status', '!=', null)
                ->pluck('id')
                ->toArray();
        });

        // if(Auth::check()) {
        //     // $pid = Auth::user()->profile->id;
        //     // $filters = UserFilter::whereUserId($pid)
        //     //           ->whereFilterableType('App\Profile')
        //     //           ->whereIn('filter_type', ['mute', 'block'])
        //     //           ->pluck('filterable_id')->toArray();
        //     // $filtered = array_merge($private->toArray(), $filters);
        //     $filtered = UserFilterService::filters(Auth::user()->profile_id);
        // } else {
        //     // $filtered = $private->toArray();
        //     $filtered = [];
        // }

        $filtered = Auth::check() ? array_merge($private, UserFilterService::filters(Auth::user()->profile_id)) : [];
        // if($max == 0) {
        //     $res = PublicTimelineService::count();
        //     if($res == 0) {
        //         PublicTimelineService::warmCache();
        //         $res = PublicTimelineService::get(0,4);
        //     } else {
        //         $res = PublicTimelineService::get(0,4);
        //     }
        //     return response()->json($res);
        // } 

        if($min || $max) {
            $dir = $min ? '>' : '<';
            $id = $min ?? $max;
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
                        'place_id',
                        'likes_count',
                        'reblogs_count',
                        'created_at',
                        'updated_at'
                      )->where('id', $dir, $id)
                      ->with('profile', 'hashtags', 'mentions')
                      ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                      ->whereLocal(true)
                      ->whereNotIn('profile_id', $filtered)
                      ->whereVisibility('public')
                      ->orderBy('created_at', 'desc')
                      ->limit($limit)
                      ->get();
                      //->toSql();
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
                      )->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                      ->with('profile', 'hashtags', 'mentions')
                      ->whereLocal(true)
                      ->whereNotIn('profile_id', $filtered)
                      ->whereVisibility('public')
                      ->orderBy('created_at', 'desc')
                      ->simplePaginate($limit);
                      //->toSql();
        }

        $fractal = new Fractal\Resource\Collection($timeline, new StatusTransformer());
        $res = $this->fractal->createData($fractal)->toArray();
        return response()->json($res);

    }

    public function homeTimelineApi(Request $request)
    {
        if(!Auth::check()) {
            return abort(403);
        }

        $this->validate($request,[
          'page'        => 'nullable|integer|max:40',
          'min_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'max_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'limit'       => 'nullable|integer|max:40'
        ]);

        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 3;

        // TODO: Use redis for timelines
        // $timeline = Timeline::build()->local();
        $pid = Auth::user()->profile->id;

        $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
            $following = Follower::whereProfileId($pid)->pluck('following_id');
            return $following->push($pid)->toArray();
        });

        // $private = Cache::remember('profiles:private', now()->addMinutes(1440), function() {
        //     return Profile::whereIsPrivate(true)
        //         ->orWhere('unlisted', true)
        //         ->orWhere('status', '!=', null)
        //         ->pluck('id');
        // });
        
        // $private = $private->diff($following)->flatten();

        // $filters = UserFilter::whereUserId($pid)
        //           ->whereFilterableType('App\Profile')
        //           ->whereIn('filter_type', ['mute', 'block'])
        //           ->pluck('filterable_id')->toArray();
        // $filtered = array_merge($private->toArray(), $filters);

        $filtered = Auth::check() ? UserFilterService::filters(Auth::user()->profile_id) : [];

        if($min || $max) {
            $dir = $min ? '>' : '<';
            $id = $min ?? $max;
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
                        'place_id',
                        'likes_count',
                        'reblogs_count',
                        'created_at',
                        'updated_at'
                      )->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                      ->with('profile', 'hashtags', 'mentions')
                      ->where('id', $dir, $id)
                      ->whereIn('profile_id', $following)
                      ->whereNotIn('profile_id', $filtered)
                      ->whereIn('visibility',['public', 'unlisted', 'private'])
                      ->orderBy('created_at', 'desc')
                      ->limit($limit)
                      ->get();
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
                        'place_id',
                        'likes_count',
                        'reblogs_count',
                        'created_at',
                        'updated_at'
                      )->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                      ->with('profile', 'hashtags', 'mentions')
                      ->whereIn('profile_id', $following)
                      ->whereNotIn('profile_id', $filtered)
                      ->whereIn('visibility',['public', 'unlisted', 'private'])
                      ->orderBy('created_at', 'desc')
                      ->simplePaginate($limit);
        }

        $fractal = new Fractal\Resource\Collection($timeline, new StatusTransformer());
        $res = $this->fractal->createData($fractal)->toArray();
        return response()->json($res);

    }


    public function networkTimelineApi(Request $request)
    {
        return response()->json([]);
    }

    public function relationships(Request $request)
    {
        if(!Auth::check()) {
            return response()->json([]);
        }

        $this->validate($request, [
            'id'    => 'required|array|min:1|max:20',
            'id.*'  => 'required|integer'
        ]);
        $ids = collect($request->input('id'));
        $filtered = $ids->filter(function($v) { 
            return $v != Auth::user()->profile->id;
        });
        $relations = Profile::whereNull('status')->findOrFail($filtered->all());
        $fractal = new Fractal\Resource\Collection($relations, new RelationshipTransformer());
        $res = $this->fractal->createData($fractal)->toArray();
        return response()->json($res);
    }

    public function account(Request $request, $id)
    {
        $res = AccountService::get($id);
        return response()->json($res);
    }

    public function accountFollowers(Request $request, $id)
    {
        abort_unless(Auth::check(), 403);
        $profile = Profile::with('user')->whereNull('status')->whereNull('domain')->findOrFail($id);
        if(Auth::id() != $profile->user_id && $profile->is_private || !$profile->user->settings->show_profile_followers) {
            return response()->json([]);
        }
        $followers = $profile->followers()->orderByDesc('followers.created_at')->paginate(10);
        $resource = new Fractal\Resource\Collection($followers, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function accountFollowing(Request $request, $id)
    {
        abort_unless(Auth::check(), 403);
        $profile = Profile::with('user')->whereNull('status')->whereNull('domain')->findOrFail($id);
        if(Auth::id() != $profile->user_id && $profile->is_private || !$profile->user->settings->show_profile_following) {
            return response()->json([]);
        }
        $following = $profile->following()->orderByDesc('followers.created_at')->paginate(10);
        $resource = new Fractal\Resource\Collection($following, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

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

        $profile = Profile::whereNull('status')->findOrFail($id);

        $limit = $request->limit ?? 9;
        $max_id = $request->max_id;
        $min_id = $request->min_id;
        $scope = $request->only_media == true ? 
            ['photo', 'photo:album', 'video', 'video:album'] :
            ['photo', 'photo:album', 'video', 'video:album', 'share', 'reply'];
       
        if($profile->is_private) {
            if(!Auth::check()) {
                return response()->json([]);
            }
            $pid = Auth::user()->profile->id;
            $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
                $following = Follower::whereProfileId($pid)->pluck('following_id');
                return $following->push($pid)->toArray();
            });
            $visibility = true == in_array($profile->id, $following) ? ['public', 'unlisted', 'private'] : [];
        } else {
            if(Auth::check()) {
                $pid = Auth::user()->profile->id;
                $following = Cache::remember('profile:following:'.$pid, now()->addMinutes(1440), function() use($pid) {
                    $following = Follower::whereProfileId($pid)->pluck('following_id');
                    return $following->push($pid)->toArray();
                });
                $visibility = true == in_array($profile->id, $following) ? ['public', 'unlisted', 'private'] : ['public', 'unlisted'];
            } else {
                $visibility = ['public', 'unlisted'];
            }
        }

        $dir = $min_id ? '>' : '<';
        $id = $min_id ?? $max_id;
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
            'likes_count',
            'reblogs_count',
            'scope',
            'local',
            'created_at',
            'updated_at'
          )->whereProfileId($profile->id)
          ->whereIn('type', $scope)
          ->whereLocal(true)
          ->whereNull('uri')
          ->where('id', $dir, $id)
          ->whereIn('visibility', $visibility)
          ->latest()
          ->limit($limit)
          ->get();

        $resource = new Fractal\Resource\Collection($timeline, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

}
