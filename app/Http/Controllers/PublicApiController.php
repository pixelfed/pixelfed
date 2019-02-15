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

    protected function getUserData()
    {
    	if(false == Auth::check()) {
    		return [];
    	} else {
	        $profile = Auth::user()->profile;
            if($profile->status) {
                return [];
            }
	        $user = new Fractal\Resource\Item($profile, new AccountTransformer());
        	return $this->fractal->createData($user)->toArray();
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
        	'user' => $this->getUserData(),
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
        $status = Status::whereProfileId($profile->id)->findOrFail($postId);
        $this->scopeCheck($profile, $status);
        if($request->filled('min_id') || $request->filled('max_id')) {
            if($request->filled('min_id')) {
                $replies = $status->comments()
                ->whereNull('reblog_of_id')
                ->select('id', 'caption', 'rendered', 'profile_id', 'in_reply_to_id', 'created_at')
                ->where('id', '>=', $request->min_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
            if($request->filled('max_id')) {
                $replies = $status->comments()
                ->whereNull('reblog_of_id')
                ->select('id', 'caption', 'rendered', 'profile_id', 'in_reply_to_id', 'created_at')
                ->where('id', '<=', $request->max_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
        } else {
            $replies = $status->comments()
            ->whereNull('reblog_of_id')
            ->select('id', 'caption', 'rendered', 'profile_id', 'in_reply_to_id', 'created_at')
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
                    $follows = $profile->followedBy(Auth::user()->profile);
                    if($follows == false && $profile->id !== $user->profile->id) {
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
        if(!Auth::check()) {
            return abort(403);
        }

        $this->validate($request,[
          'page'        => 'nullable|integer|max:40',
          'min_id'      => 'nullable|integer',
          'max_id'      => 'nullable|integer',
          'limit'       => 'nullable|integer|max:20'
        ]);

        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 5;

        // TODO: Use redis for timelines
        // $timeline = Timeline::build()->local();
        $pid = Auth::user()->profile->id;

        $private = Profile::whereIsPrivate(true)
            ->orWhere('unlisted', true)
            ->orWhere('status', '!=', null)
            ->where('id', '!=', $pid)
            ->pluck('id');
        $filters = UserFilter::whereUserId($pid)
                  ->whereFilterableType('App\Profile')
                  ->whereIn('filter_type', ['mute', 'block'])
                  ->pluck('filterable_id')->toArray();
        $filtered = array_merge($private->toArray(), $filters);

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
                        'created_at',
                        'updated_at'
                      )
                      ->whereHas('media')
                      ->whereLocal(true)
                      ->whereNull('uri')
                      ->where('id', $dir, $id)
                      ->whereNotIn('profile_id', $filtered)
                      ->whereNull('in_reply_to_id')
                      ->whereNull('reblog_of_id')
                      ->whereVisibility('public')
                      ->withCount(['comments', 'likes'])
                      ->orderBy('created_at', 'desc')
                      ->limit($limit)
                      ->get();
        } else {
            $timeline = Status::whereHas('media')
                      ->whereLocal(true)
                      ->whereNull('uri')
                      ->whereNotIn('profile_id', $filtered)
                      ->whereNull('in_reply_to_id')
                      ->whereNull('reblog_of_id')
                      ->whereVisibility('public')
                      ->withCount(['comments', 'likes'])
                      ->orderBy('created_at', 'desc')
                      ->simplePaginate($limit);
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
          'min_id'      => 'nullable|integer',
          'max_id'      => 'nullable|integer',
          'limit'       => 'nullable|integer|max:20'
        ]);

        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 5;

        // TODO: Use redis for timelines
        // $timeline = Timeline::build()->local();
        $pid = Auth::user()->profile->id;

        $following = Follower::whereProfileId($pid)->pluck('following_id');
        $following->push($pid)->toArray();

        $private = Profile::whereIsPrivate(true)->orWhereNotNull('status')->where('id', '!=', $pid)->pluck('id');
        $filters = UserFilter::whereUserId($pid)
                  ->whereFilterableType('App\Profile')
                  ->whereIn('filter_type', ['mute', 'block'])
                  ->pluck('filterable_id')->toArray();
        $filtered = array_merge($private->toArray(), $filters);

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
                        'created_at',
                        'updated_at'
                      )
                      ->whereHas('media')
                      ->whereLocal(true)
                      ->whereNull('uri')
                      ->where('id', $dir, $id)
                      ->whereIn('profile_id', $following)
                      ->whereNotIn('profile_id', $filtered)
                      ->whereNull('in_reply_to_id')
                      ->whereNull('reblog_of_id')
                      ->whereIn('visibility',['public', 'unlisted', 'private'])
                      ->withCount(['comments', 'likes'])
                      ->orderBy('created_at', 'desc')
                      ->limit($limit)
                      ->get();
        } else {
            $timeline = Status::whereHas('media')
                      ->whereLocal(true)
                      ->whereNull('uri')
                      ->whereIn('profile_id', $following)
                      ->whereNotIn('profile_id', $filtered)
                      ->whereNull('in_reply_to_id')
                      ->whereNull('reblog_of_id')
                      ->whereIn('visibility',['public', 'unlisted', 'private'])
                      ->withCount(['comments', 'likes'])
                      ->orderBy('created_at', 'desc')
                      ->simplePaginate($limit);
        }

        $fractal = new Fractal\Resource\Collection($timeline, new StatusTransformer());
        $res = $this->fractal->createData($fractal)->toArray();
        return response()->json($res);

    }

    public function relationships(Request $request)
    {
        abort_if(!Auth::check(), 403);

        $this->validate($request, [
            'id'    => 'required|array|min:1|max:20',
            'id.*'  => 'required|integer'
        ]);
        $ids = collect($request->input('id'));
        $filtered = $ids->filter(function($v) { 
            return $v != Auth::user()->profile->id;
        });
        $relations = Profile::findOrFail($filtered->all());
        $fractal = new Fractal\Resource\Collection($relations, new RelationshipTransformer());
        $res = $this->fractal->createData($fractal)->toArray();
        return response()->json($res);
    }

    public function account(Request $request, $id)
    {
        $profile = Profile::whereNull('status')->findOrFail($id);
        $resource = new Fractal\Resource\Item($profile, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function accountFollowers(Request $request, $id)
    {
        $profile = Profile::findOrFail($id);
        $followers = $profile->followers;
        $resource = new Fractal\Resource\Collection($followers, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function accountFollowing(Request $request, $id)
    {
        $profile = Profile::findOrFail($id);
        $following = $profile->following;
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
            'max_id' => 'nullable|integer|min:1',
            'since_id' => 'nullable|integer|min:1',
            'min_id' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:24'
        ]);
        $limit = $request->limit ?? 20;
        $max_id = $request->max_id ?? false;
        $min_id = $request->min_id ?? false;
        $since_id = $request->since_id ?? false;
        $only_media = $request->only_media ?? false;
        $user = Auth::user();
        $account = Profile::findOrFail($id);
        $statuses = $account->statuses()
            ->getQuery()
            ->whereNull('uri');
        if(!$user || $user->profile->id != $account->id && !$user->profile->follows($account)) {
            $statuses = $statuses->whereVisibility('public');
        } else {
            $statuses = $statuses->whereIn('visibility', ['public', 'unlisted', 'private']);
        }
        if($only_media == true) {
            $statuses = $statuses
                ->whereHas('media')
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id');
        }
        if($id == $account->id && !$max_id && !$min_id && !$since_id) {
            $statuses = $statuses->orderBy('id', 'desc')
                ->paginate($limit);
        } else if($since_id) {
            $statuses = $statuses->where('id', '>', $since_id)
                ->orderBy('id', 'DESC')
                ->paginate($limit);
        } else if($min_id) {
            $statuses = $statuses->where('id', '>', $min_id)
                ->orderBy('id', 'ASC')
                ->paginate($limit);
        } else if($max_id) {
            $statuses = $statuses->where('id', '<', $max_id)
                ->orderBy('id', 'DESC')
                ->paginate($limit);
        } else {
            $statuses = $statuses->orderBy('id', 'desc')->paginate($limit);
        }
        $resource = new Fractal\Resource\Collection($statuses, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }
}
