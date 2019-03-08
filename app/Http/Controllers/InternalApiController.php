<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{
    DirectMessage,
    DiscoverCategory,
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
use App\Util\Media\Filter;
use App\Jobs\StatusPipeline\NewStatusPipeline;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Validation\Rule;

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
            'media.*.filter' => 'nullable|alpha_dash|max:30',
            'media.*.license' => 'nullable|string|max:80',
            'visibility' => 'required|string|in:public,private|min:2|max:10'
        ]);

        $profile = Auth::user()->profile;
        $visibility = $request->input('visibility');
        $medias = $request->input('media');
        $attachments = [];
        $status = new Status;
        $mimes = [];
        $cw = false;

        foreach($medias as $k => $media) {
            $m = Media::findOrFail($media['id']);
            if($m->profile_id !== $profile->id || $m->status_id) {
                abort(403, 'Invalid media id');
            }
            $m->filter_class = in_array($media['filter'], Filter::classes()) ? $media['filter'] : null;
            $m->license = $media['license'];
            $m->caption = strip_tags($media['alt']);
            $m->order = isset($media['cursor']) && is_int($media['cursor']) ? (int) $media['cursor'] : $k;
            if($media['cw'] == true || $profile->cw == true) {
                $cw = true;
                $m->is_nsfw = true;
                $status->is_nsfw = true;
            }
            $m->save();
            $attachments[] = $m;
            array_push($mimes, $m->mime);
        }

        $status->caption = strip_tags($request->caption);
        $status->scope = 'draft';
        $status->profile_id = $profile->id;
        $status->save();

        foreach($attachments as $media) {
            $media->status_id = $status->id;
            $media->save();
        }

        $visibility = $profile->unlisted == true && $visibility == 'public' ? 'unlisted' : $visibility;
        $cw = $profile->cw == true ? true : $cw;
        $status->is_nsfw = $cw;
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
        $following = Cache::remember('feature:discover:following:'.$pid, now()->addMinutes(60), function() use ($pid) {
            return Follower::whereProfileId($pid)->pluck('following_id')->toArray();
        });
        $filters = Cache::remember("user:filter:list:$pid", now()->addMinutes(60), function() use($pid) {
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
        $following = Cache::remember('feature:discover:following:'.$pid, now()->addMinutes(60), function() use ($pid) {
            return Follower::whereProfileId($pid)->pluck('following_id')->toArray();
        });
        $filters = Cache::remember("user:filter:list:$pid", now()->addMinutes(60), function() use($pid) {
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
        $following = Cache::remember('feature:discover:following:'.$pid, now()->addMinutes(15), function() use ($pid) {
            return Follower::whereProfileId($pid)->pluck('following_id')->toArray();
        });
        $filters = Cache::remember("user:filter:list:$pid", now()->addMinutes(15), function() use($pid) {
            $private = Profile::whereIsPrivate(true)
                ->orWhere('unlisted', true)
                ->orWhere('status', '!=', null)
                ->pluck('id')
                ->toArray();
            $filters = UserFilter::whereUserId($pid)
                ->whereFilterableType('App\Profile')
                ->whereIn('filter_type', ['mute', 'block'])
                ->pluck('filterable_id')
                ->toArray();
            return array_merge($private, $filters);
        });
        $following = array_merge($following, $filters);

        $posts = Status::select('id', 'caption', 'profile_id')
              ->whereNull('uri')
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

    public function stories(Request $request)
    {
        
    }

    public function discoverCategories(Request $request)
    {
        $categories = DiscoverCategory::whereActive(true)->orderBy('order')->take(10)->get();
        $res = $categories->map(function($item) {
            return [
                'name' => $item->name,
                'url' => $item->url(),
                'thumb' => $item->thumb()
            ];
        });
        return response()->json($res);
    }

    public function modAction(Request $request)
    {
        abort_unless(Auth::user()->is_admin, 403);
        $this->validate($request, [
            'action' => [
                'required',
                'string',
                Rule::in([
                    'autocw',
                    'noautolink',
                    'unlisted',
                    'disable',
                    'suspend'
                ])
            ],
            'item_id' => 'required|integer|min:1',
            'item_type' => [
                'required',
                'string',
                Rule::in(['status'])
            ]
        ]);

        $action = $request->input('action');
        $item_id = $request->input('item_id');
        $item_type = $request->input('item_type');

        switch($action) {
            case 'autocw':
                $profile = $item_type == 'status' ? Status::findOrFail($item_id)->profile : null;
                $profile->cw = true;
                $profile->save();
            break;

            case 'noautolink':
                $profile = $item_type == 'status' ? Status::findOrFail($item_id)->profile : null;
                $profile->no_autolink = true;
                $profile->save();
            break;

            case 'unlisted':
                $profile = $item_type == 'status' ? Status::findOrFail($item_id)->profile : null;
                $profile->unlisted = true;
                $profile->save();
            break;

            case 'disable':
                $profile = $item_type == 'status' ? Status::findOrFail($item_id)->profile : null;
                $user = $profile->user;
                $profile->status = 'disabled';
                $user->status = 'disabled';
                $profile->save();
                $user->save();
            break;


            case 'suspend':
                $profile = $item_type == 'status' ? Status::findOrFail($item_id)->profile : null;
                $user = $profile->user;
                $profile->status = 'suspended';
                $user->status = 'suspended';
                $profile->save();
                $user->save();
            break;
            
            default:
                # code...
                break;
        }
        return ['msg' => 200];
    }
}
