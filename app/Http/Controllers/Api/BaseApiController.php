<?php

namespace App\Http\Controllers\Api;

use App\Avatar;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\Controller;
use App\Jobs\AvatarPipeline\AvatarOptimize;
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use App\Media;
use App\Profile;
use App\Transformer\Api\AccountTransformer;
use App\Transformer\Api\MediaTransformer;
use App\Transformer\Api\StatusTransformer;
use Auth;
use Cache;
use Illuminate\Http\Request;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class BaseApiController extends Controller
{
    protected $fractal;

    public function __construct()
    {
        $this->middleware('auth');
        $this->fractal = new Fractal\Manager();
        $this->fractal->setSerializer(new ArraySerializer());
    }

    public function accounts(Request $request, $id)
    {
        $profile = Profile::findOrFail($id);
        $resource = new Fractal\Resource\Item($profile, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function accountFollowers(Request $request, $id)
    {
        $profile = Profile::findOrFail($id);
        $followers = $profile->followers;
        $resource = new Fractal\Resource\Collection($followers, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function accountFollowing(Request $request, $id)
    {
        $profile = Profile::findOrFail($id);
        $following = $profile->following;
        $resource = new Fractal\Resource\Collection($following, new AccountTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
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

        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
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
            'upload'   => 'required|mimes:jpeg,png,gif|max:2000',
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
        $monthHash = hash('sha1', date('Y').date('m'));
        $userHash = hash('sha1', $user->id.(string) $user->created_at);
        $photo = $request->file('file');

        $storagePath = "public/m/{$monthHash}/{$userHash}";
        $path = $photo->store($storagePath);
        $hash = \hash_file('sha256', $photo);

        $media = new Media();
        $media->status_id = null;
        $media->profile_id = $profile->id;
        $media->user_id = $user->id;
        $media->media_path = $path;
        $media->original_sha256 = $hash;
        $media->size = $photo->getClientSize();
        $media->mime = $photo->getClientMimeType();
        $media->filter_class = null;
        $media->filter_name = null;
        $media->save();

        ImageOptimize::dispatch($media);
        $resource = new Fractal\Resource\Item($media, new MediaTransformer());
        $res = $this->fractal->createData($resource)->toArray();

        return response()->json($res);
    }
}
