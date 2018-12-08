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
};
use Auth,Cache;
use Carbon\Carbon;
use League\Fractal;
use App\Transformer\Api\{
    AccountTransformer,
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
        $this->middleware('throttle:3000, 30');
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    protected function getUserData()
    {
    	if(false == Auth::check()) {
    		return [];
    	} else {
	        $profile = Auth::user()->profile;
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
            $shares = $status->sharedBy()->orderBy('created_at','desc')->paginate(10);
            $collection = new Fractal\Resource\Collection($shares, new AccountTransformer());
            return $this->fractal->createData($collection)->toArray();
        }
    }

    public function status(Request $request, $username, int $postid)
    {
        $profile = Profile::whereUsername($username)->first();
        $status = Status::whereProfileId($profile->id)->find($postid);
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
        $profile = Profile::whereUsername($username)->first();
        $status = Status::whereProfileId($profile->id)->find($postId);
        $this->scopeCheck($profile, $status);
        if($request->filled('min_id') || $request->filled('max_id')) {
            if($request->filled('min_id')) {
                $replies = $status->comments()
                ->select('id', 'caption', 'rendered', 'profile_id', 'in_reply_to_id', 'created_at')
                ->where('id', '>=', $request->min_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
            if($request->filled('max_id')) {
                $replies = $status->comments()
                ->select('id', 'caption', 'rendered', 'profile_id', 'in_reply_to_id', 'created_at')
                ->where('id', '<=', $request->max_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
        } else {
            $replies = $status->comments()
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
        $profile = Profile::whereUsername($username)->first();
        $status = Status::whereProfileId($profile->id)->find($id);
        $this->scopeCheck($profile, $status);
        $likes = $this->getLikes($status);
        return response()->json([
            'data' => $likes
        ]);
    }

    public function statusShares(Request $request, $username, $id)
    {
        $profile = Profile::whereUsername($username)->first();
        $status = Status::whereProfileId($profile->id)->find($id);
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
            case 'private':
                $user = Auth::check() ? Auth::user() : false;
                if($user && $profile->is_private) {
                    $follows = Follower::whereProfileId($user->profile->id)
                        ->whereFollowingId($profile->id)
                        ->exists();
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
}
