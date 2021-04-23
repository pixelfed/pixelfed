<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

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
    StatusStatelessTransformer
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
        if(!Auth::check()) {
            $res = Cache::remember('wapi:v1:status:stateless_byid:' . $status->id, now()->addMinutes(30), function() use($status) {
                $item = new Fractal\Resource\Item($status, new StatusStatelessTransformer());
                $res = [
                    'status' => $this->fractal->createData($item)->toArray(),
                ];
                return $res;
            });
            return response()->json($res);
        }
        $item = new Fractal\Resource\Item($status, new StatusStatelessTransformer());
        $res = [
        	'status' => $this->fractal->createData($item)->toArray(),
        ];
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
            $filtered = UserFilter::whereUserId($p->id)
            ->whereFilterableType('App\Profile')
            ->whereIn('filter_type', ['mute', 'block'])
            ->pluck('filterable_id')->toArray();
            $scope = $p->id == $status->profile_id ? ['public', 'private', 'unlisted'] : ['public','unlisted'];
        } else {
            $filtered = [];
            $scope = ['public', 'unlisted'];
        }

        if($request->filled('min_id') || $request->filled('max_id')) {
            if($request->filled('min_id')) {
                $replies = $status->comments()
                ->whereNull('reblog_of_id')
                ->whereIn('scope', $scope)
                ->whereNotIn('profile_id', $filtered)
                ->select('id', 'caption', 'local', 'visibility', 'scope', 'is_nsfw', 'rendered', 'profile_id', 'in_reply_to_id', 'type', 'reply_count', 'created_at')
                ->where('id', '>=', $request->min_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
            if($request->filled('max_id')) {
                $replies = $status->comments()
                ->whereNull('reblog_of_id')
                ->whereIn('scope', $scope)
                ->whereNotIn('profile_id', $filtered)
                ->select('id', 'caption', 'local', 'visibility', 'scope', 'is_nsfw', 'rendered', 'profile_id', 'in_reply_to_id', 'type', 'reply_count', 'created_at')
                ->where('id', '<=', $request->max_id)
                ->orderBy('id', 'desc')
                ->paginate($limit);
            }
        } else {
            $replies = $status->comments()
            ->whereNull('reblog_of_id')
            ->whereIn('scope', $scope)
            ->whereNotIn('profile_id', $filtered)
            ->select('id', 'caption', 'local', 'visibility', 'scope', 'is_nsfw', 'rendered', 'profile_id', 'in_reply_to_id', 'type', 'reply_count', 'created_at')
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
        $user = $request->user();

        $key = 'user:last_active_at:id:'.$user->id;
        $ttl = now()->addMinutes(5);
        Cache::remember($key, $ttl, function() use($user) {
            $user->last_active_at = now();
            $user->save();
            return;
        });

        $filtered = UserFilter::whereUserId($user->profile_id)
                  ->whereFilterableType('App\Profile')
                  ->whereIn('filter_type', ['mute', 'block'])
                  ->pluck('filterable_id')->toArray();

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
                      )->whereIn('type', ['text', 'photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                      ->whereNotIn('profile_id', $filtered)
                      ->with('profile', 'hashtags', 'mentions')
                      ->whereLocal(true)
                      ->whereScope('public')
                      ->where('created_at', '>', now()->subMonths(3))
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
          'min_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'max_id'      => 'nullable|integer|min:0|max:' . PHP_INT_MAX,
          'limit'       => 'nullable|integer|max:40'
        ]);

        $page = $request->input('page');
        $min = $request->input('min_id');
        $max = $request->input('max_id');
        $limit = $request->input('limit') ?? 3;
        $user = $request->user();

        $key = 'user:last_active_at:id:'.$user->id;
        $ttl = now()->addMinutes(5);
        Cache::remember($key, $ttl, function() use($user) {
            $user->last_active_at = now();
            $user->save();
            return;
        });

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
                      )->whereIn('type', ['text','photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
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
                      )->whereIn('type', ['text','photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
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
        abort_if(!Auth::check(), 403);
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

        $key = 'user:last_active_at:id:'.$user->id;
        $ttl = now()->addMinutes(5);
        Cache::remember($key, $ttl, function() use($user) {
            $user->last_active_at = now();
            $user->save();
            return;
        });

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
                      ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                      ->whereNotNull('uri')
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
                      ->whereNotNull('uri')
                      ->whereScope('public')
                      ->where('created_at', '>', now()->subMonths(3))
                      ->orderBy('created_at', 'desc')
                      ->simplePaginate($limit);
        }

        $fractal = new Fractal\Resource\Collection($timeline, new StatusTransformer());
        $res = $this->fractal->createData($fractal)->toArray();
        return response()->json($res);
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

        $profile = Profile::with('user')
            ->whereNull('status')
            ->whereNull('domain')
            ->findOrFail($id);

        // filter by username
        $search = $request->input('fbu');
        $owner = Auth::id() == $profile->user_id;
        $filter = ($owner == true) && ($search != null);

        abort_if($owner == false && $profile->is_private == true && !$profile->followedBy(Auth::user()->profile), 404);
        abort_if($profile->user->settings->show_profile_following == false && $owner == false, 404);

        if($search) {
            abort_if(!$owner, 404);
            $following = $profile->following()
                    ->where('profiles.username', 'like', '%'.$search.'%')
                    ->orderByDesc('followers.created_at')
                    ->paginate(10);
        } else {
            $following = $profile->following()
                ->orderByDesc('followers.created_at')
                ->paginate(10);
        }
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

        $tag = in_array('private', $visibility) ? 'private' : 'public';
        if($min_id == 1 && $limit == 9 && $tag == 'public') {
            $limit = 9;
            $scope = ['photo', 'photo:album', 'video', 'video:album'];
            $key = '_api:statuses:recent_9:'.$profile->id;
            $res = Cache::remember($key, now()->addHours(24), function() use($profile, $scope, $visibility, $limit) {
                $dir = '>';
                $id = 1;
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
                    'visibility',
                    'local',
                    'place_id',
                    'comments_disabled',
                    'cw_summary',
                    'created_at',
                    'updated_at'
                  )->whereProfileId($profile->id)
                  ->whereIn('type', $scope)
                  ->where('id', $dir, $id)
                  ->whereIn('visibility', $visibility)
                  ->limit($limit)
                  ->orderByDesc('id')
                  ->get();

                $resource = new Fractal\Resource\Collection($timeline, new StatusStatelessTransformer());
                $res = $this->fractal->createData($resource)->toArray();

                return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            });
            return $res;
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
            'visibility',
            'local',
            'place_id',
            'comments_disabled',
            'cw_summary',
            'created_at',
            'updated_at'
          )->whereProfileId($profile->id)
          ->whereIn('type', $scope)
          ->where('id', $dir, $id)
          ->whereIn('visibility', $visibility)
          ->limit($limit)
          ->orderByDesc('id')
          ->get();

        $resource = new Fractal\Resource\Collection($timeline, new StatusStatelessTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res, 200, [], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    }

}
