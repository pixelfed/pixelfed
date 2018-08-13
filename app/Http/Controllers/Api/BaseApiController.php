<?php

namespace App\Http\Controllers\Api;

use Auth, Cache;
use App\{
    Avatar, 
    Like, 
    Profile, 
    Status
};
use League\Fractal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AvatarController;
use App\Util\Webfinger\Webfinger;
use App\Transformer\Api\{
  AccountTransformer,
  StatusTransformer
};
use App\Jobs\AvatarPipeline\AvatarOptimize;
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
        $resource = new Fractal\Resource\Item($profile, new AccountTransformer);
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function accountFollowers(Request $request, $id)
    {
        $profile = Profile::findOrFail($id);
        $followers = $profile->followers;
        $resource = new Fractal\Resource\Collection($followers, new AccountTransformer);
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function accountFollowing(Request $request, $id)
    {
        $profile = Profile::findOrFail($id);
        $following = $profile->following;
        $resource = new Fractal\Resource\Collection($following, new AccountTransformer);
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    public function accountStatuses(Request $request, $id)
    {
        $profile = Profile::findOrFail($id);
        $statuses = $profile->statuses()->orderBy('id', 'desc')->paginate(20);
        $resource = new Fractal\Resource\Collection($statuses, new StatusTransformer);
        $res = $this->fractal->createData($resource)->toArray();
        return response()->json($res, 200, [], JSON_PRETTY_PRINT);
    }

    
    public function followSuggestions(Request $request)
    {
        $followers = Auth::user()->profile->recommendFollowers();
        $resource = new Fractal\Resource\Collection($followers, new AccountTransformer);
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
            'msg' => 'Avatar successfully updated'
        ]);
    }
}