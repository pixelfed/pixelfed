<?php

namespace App\Http\Controllers\Api;

use Auth;
use App\{Like, Profile, Status};
use League\Fractal;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Util\Webfinger\Webfinger;
use App\Transformer\Api\{
  AccountTransformer,
  StatusTransformer
};
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
}