<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\{
    Controller,
    AvatarController
};
use Auth, Cache, URL;
use Carbon\Carbon;
use App\{
    Avatar,
    Notification,
    Media,
    Profile
};
use App\Transformer\Api\{
    AccountTransformer,
    NotificationTransformer,
    MediaTransformer,
    StatusTransformer
};
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Jobs\VideoPipeline\{
    VideoOptimize,
    VideoPostProcess,
    VideoThumbnail
};

class BaseApiController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->middleware('auth');
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    public function notification(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        $resource = new Fractal\Resource\Item($notification, new NotificationTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function notifications(Request $request)
    {
        $pid = Auth::user()->profile->id;
        $timeago = Carbon::now()->subMonths(6);
        $notifications = Notification::with('actor')
            ->whereProfileId($pid)
            ->whereDate('created_at', '>', $timeago)
            ->orderBy('created_at','desc')
            ->paginate(10);
        $resource = new Fractal\Resource\Collection($notifications, new NotificationTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function accounts(Request $request, $id)
    {
        $profile = Profile::findOrFail($id);
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
        $pid = Auth::user()->profile->id;
        $profile = Profile::findOrFail($id);
        $statuses = $profile->statuses(); 
        if($pid === $profile->id) {
            $statuses = $statuses->orderBy('id', 'desc')->paginate(20);
        } else {
            $statuses = $statuses->whereVisibility('public')->orderBy('id', 'desc')->paginate(20);
        }
        $resource = new Fractal\Resource\Collection($statuses, new StatusTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function followSuggestions(Request $request)
    {
        $followers = Auth::user()->profile->recommendFollowers();
        $resource = new Fractal\Resource\Collection($followers, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }

    public function avatarUpdate(Request $request)
    {
        $this->validate($request, [
            'upload'   => 'required|mimes:jpeg,png,gif|max:'.config('pixelfed.max_avatar_size'),
        ]);

        try {
            $user = Auth::user();
            $profile = $user->profile;
            $file = $request->file('upload');
            $path = (new AvatarController())->getPath($user, $file);
            $dir = $path['root'];
            $name = $path['name'];
            $public = $path['storage'];
            $currentAvatar = storage_path('app/'.$profile->avatar->media_path);
            $loc = $request->file('upload')->storeAs($public, $name);

            $avatar = Avatar::whereProfileId($profile->id)->firstOrFail();
            $opath = $avatar->media_path;
            $avatar->media_path = "$public/$name";
            $avatar->thumb_path = null;
            $avatar->change_count = ++$avatar->change_count;
            $avatar->last_processed_at = null;
            $avatar->save();

            Cache::forget("avatar:{$profile->id}");
            AvatarOptimize::dispatch($user->profile, $currentAvatar);
        } catch (Exception $e) {
        }

        return response()->json([
            'code' => 200,
            'msg'  => 'Avatar successfully updated',
        ]);
    }

    public function showTempMedia(Request $request, int $profileId, $mediaId)
    {
        if (!$request->hasValidSignature()) {
            abort(401);
        }
        $profile = Auth::user()->profile;
        if($profile->id !== $profileId) {
            abort(403);
        }
        $media = Media::whereProfileId($profile->id)->findOrFail($mediaId);
        $path = storage_path('app/'.$media->media_path);
        return response()->file($path);
    }

    public function uploadMedia(Request $request)
    {
        $this->validate($request, [
              'file.*'      => function() {
                return [
                    'required',
                    'mimes:' . config('pixelfed.media_types'),
                    'max:' . config('pixelfed.max_photo_size'),
                ];
              },
        ]);

        $user = Auth::user();
        $profile = $user->profile;

        if(config('pixelfed.enforce_account_limit') == true) {
            $size = Media::whereUserId($user->id)->sum('size') / 1000;
            $limit = (int) config('pixelfed.max_account_size');
            if ($size >= $limit) {
               abort(403, 'Account size limit reached.');
            }
        }

        $recent = Media::whereProfileId($profile->id)->whereNull('status_id')->count();

        if($recent > 50) {
            abort(403);
        }

        $monthHash = hash('sha1', date('Y').date('m'));
        $userHash = hash('sha1', $user->id.(string) $user->created_at);

        $photo = $request->file('file');

        $mimes = explode(',', config('pixelfed.media_types'));
        if(in_array($photo->getMimeType(), $mimes) == false) {
            return;
        }

        $storagePath = "public/m/{$monthHash}/{$userHash}";
        $path = $photo->store($storagePath);
        $hash = \hash_file('sha256', $photo);

        $media = new Media();
        $media->status_id = null;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getSize();
        $media->mime = $photo->getMimeType();
        $media->filter_class = null;
        $media->filter_name = null;
        $media->save();

        $url = URL::temporarySignedRoute(
            'temp-media', now()->addHours(1), ['profileId' => $profile->id, 'mediaId' => $media->id]
        );

        switch ($media->mime) {
            case 'image/jpeg':
            case 'image/png':
                ImageOptimize::dispatch($media);
                break;

            case 'video/mp4':
                VideoThumbnail::dispatch($media);
                break;
            
            default:
                break;
        }

        $res = [
            'id'          => $media->id,
            'type'        => $media->activityVerb(),
            'url'         => $url,
            'remote_url'  => null,
            'preview_url' => $url,
            'text_url'    => null,
            'meta'        => $media->metadata,
            'description' => null,
        ];

        return response()->json($res);
    }

    public function verifyCredentials(Request $request)
    {
        $profile = Auth::user()->profile;
        $resource = new Fractal\Resource\Item($profile, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }
}
