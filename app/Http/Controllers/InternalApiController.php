<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{
    DirectMessage,
    Hashtag,
    Follower,
    Like,
    Media,
    Notification,
    Profile,
    StatusHashtag,
    Status,
    UserFilter,
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

class InternalApiController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->middleware('auth');
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    public function compose(Request $request)
    {
        $this->validate($request, [
            'caption' => 'nullable|string',
            'media.*'   => 'required',
            'media.*.id' => 'required|integer|min:1',
            'media.*.filter' => 'nullable|string|max:30',
            'media.*.license' => 'nullable|string|max:80',
            'visibility' => 'required|string|in:public,private|min:2|max:10'
        ]);

        $profile = Auth::user()->profile;
        $visibility = $request->input('visibility');
        $medias = $request->input('media');
        $attachments = [];
        $status = new Status;
        $mimes = [];

        foreach($medias as $k => $media) {
            $m = Media::findOrFail($media['id']);
            if($m->profile_id !== $profile->id || $m->status_id) {
                abort(403, 'Invalid media id');
            }
            $m->filter_class = $media['filter'];
            $m->license = $media['license'];
            $m->caption = strip_tags($media['alt']);
            $m->order = isset($media['cursor']) && is_int($media['cursor']) ? (int) $media['cursor'] : $k;
            if($media['cw'] == true) {
                $m->is_nsfw = true;
                $status->is_nsfw = true;
            }
            $m->save();
            $attachments[] = $m;
            array_push($mimes, $m->mime);
        }

        $status->caption = strip_tags($request->caption);
        $status->visibility = 'draft';
        $status->scope = 'draft';
        $status->profile_id = $profile->id;
        $status->save();

        foreach($attachments as $media) {
            $media->status_id = $status->id;
            $media->save();
        }

        $status->visibility = $visibility;
        $status->scope = $visibility;
        $status->type = StatusController::mimeTypeCheck($mimes);
        $status->save();

        NewStatusPipeline::dispatch($status);

        return $status->url();
    }

    // deprecated
    public function discover(Request $request)
    {
        $profile = Auth::user()->profile;
        $pid = $profile->id;
        $following = Cache::remember('feature:discover:following:'.$pid, 60, function() use ($pid) {
            return Follower::whereProfileId($pid)->pluck('following_id')->toArray();
        });
        $filters = Cache::remember("user:filter:list:$pid", 60, function() use($pid) {
            return UserFilter::whereUserId($pid)
            ->whereFilterableType('App\Profile')
            ->whereIn('filter_type', ['mute', 'block'])
            ->pluck('filterable_id')->toArray();
        });
        $following = array_merge($following, $filters);

        $people = Profile::select('id', 'name', 'username')
            ->with('avatar')
            ->whereNull('status')
            ->orderByRaw('rand()')
            ->whereHas('statuses')
            ->whereNull('domain')
            ->whereNotIn('id', $following)
            ->whereIsPrivate(false)
            ->take(3)
            ->get();

        $posts = Status::select('id', 'caption', 'profile_id')
              ->whereHas('media')
              ->whereIsNsfw(false)
              ->whereVisibility('public')
              ->whereNotIn('profile_id', $following)
              ->with('media')
              ->orderBy('created_at', 'desc')
              ->take(21)
              ->get();

        $res = [
            'people' => $people->map(function($profile) {
                return [
                    'id'    => $profile->id,
                    'avatar' => $profile->avatarUrl(),
                    'name' => $profile->name,
                    'username' => $profile->username,
                    'url'   => $profile->url(),
                ];
            }),
            'posts' => $posts->map(function($post) {
                return [
                    'url' => $post->url(),
                    'thumb' => $post->thumb(),
                ];
            })
        ];
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function discoverPeople(Request $request)
    {
        $profile = Auth::user()->profile;
        $pid = $profile->id;
        $following = Cache::remember('feature:discover:following:'.$pid, 60, function() use ($pid) {
            return Follower::whereProfileId($pid)->pluck('following_id')->toArray();
        });
        $filters = Cache::remember("user:filter:list:$pid", 60, function() use($pid) {
            return UserFilter::whereUserId($pid)
            ->whereFilterableType('App\Profile')
            ->whereIn('filter_type', ['mute', 'block'])
            ->pluck('filterable_id')->toArray();
        });
        $following = array_merge($following, $filters);

        $people = Profile::select('id', 'name', 'username')
            ->with('avatar')
            ->orderByRaw('rand()')
            ->whereHas('statuses')
            ->whereNull('domain')
            ->whereNotIn('id', $following)
            ->whereIsPrivate(false)
            ->take(3)
            ->get();

        $res = [
            'people' => $people->map(function($profile) {
                return [
                    'id'    => $profile->id,
                    'avatar' => $profile->avatarUrl(),
                    'name' => $profile->name,
                    'username' => $profile->username,
                    'url'   => $profile->url(),
                ];
            })
        ];
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function discoverPosts(Request $request)
    {
        $profile = Auth::user()->profile;
        $pid = $profile->id;
        $following = Cache::remember('feature:discover:following:'.$pid, 60, function() use ($pid) {
            return Follower::whereProfileId($pid)->pluck('following_id')->toArray();
        });
        $filters = Cache::remember("user:filter:list:$pid", 60, function() use($pid) {
            return UserFilter::whereUserId($pid)
            ->whereFilterableType('App\Profile')
            ->whereIn('filter_type', ['mute', 'block'])
            ->pluck('filterable_id')->toArray();
        });
        $following = array_merge($following, $filters);

        $posts = Status::select('id', 'caption', 'profile_id')
              ->whereHas('media')
              ->whereHas('profile', function($q) {
                return $q->whereNull('status');
              })
              ->whereIsNsfw(false)
              ->whereVisibility('public')
              ->whereNotIn('profile_id', $following)
              ->with('media')
              ->orderBy('created_at', 'desc')
              ->take(21)
              ->get();

        $res = [
            'posts' => $posts->map(function($post) {
                return [
                    'url' => $post->url(),
                    'thumb' => $post->thumb(),
                ];
            })
        ];
        return response()->json($res);
    }

    public function directMessage(Request $request, $profileId, $threadId)
    {
        $profile = Auth::user()->profile;

        if($profileId != $profile->id) { 
            abort(403); 
        }

        $msg = DirectMessage::whereToId($profile->id)
            ->orWhere('from_id',$profile->id)
            ->findOrFail($threadId);

        $thread = DirectMessage::with('status')->whereIn('to_id', [$profile->id, $msg->from_id])
            ->whereIn('from_id', [$profile->id,$msg->from_id])
            ->orderBy('created_at', 'asc')
            ->paginate(30);

        return response()->json(compact('msg', 'profile', 'thread'), 200, [], JSON_PRETTY_PRINT);
    }

    public function notificationMarkAllRead(Request $request)
    {
        $profile = Auth::user()->profile;

        $notifications = Notification::whereProfileId($profile->id)->get();
        foreach($notifications as $n) {
            $n->read_at = Carbon::now();
            $n->save();
        }

        return;
    }

    public function statusReplies(Request $request, int $id)
    {
        $parent = Status::findOrFail($id);

        $children = Status::whereInReplyToId($parent->id)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $resource = new Fractal\Resource\Collection($children, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }
}
