<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\{
    Follower,
    Like,
    Place,
    Profile,
    UserFilter
};
use Auth, Cache, Redis;
use App\Util\Site\Config;
use Illuminate\Http\Request;
use App\Services\SuggestionService;

class ApiController extends BaseApiController
{
    // todo: deprecate and remove
    public function hydrateLikes(Request $request)
    {
        return response()->json([]);
    }

    public function siteConfiguration(Request $request)
    {
        return response()->json(Config::get());
    }

    public function userRecommendations(Request $request)
    {
        abort_if(!Auth::check(), 403);
        abort_if(!config('exp.rec'), 400);

        $id = Auth::user()->profile->id;

        $following = Cache::remember('profile:following:'.$id, now()->addHours(12), function() use ($id) {
            return Follower::whereProfileId($id)->pluck('following_id')->toArray();
        });
        array_push($following, $id);
        $ids = SuggestionService::get();
        $filters = UserFilter::whereUserId($id)
                  ->whereFilterableType('App\Profile')
                  ->whereIn('filter_type', ['mute', 'block'])
                  ->pluck('filterable_id')->toArray();
        $following = array_merge($following, $filters);

        $key = config('cache.prefix').':api:local:exp:rec:'.$id;
        $ttl = (int) Redis::ttl($key);

        if($request->filled('refresh') == true  && (290 > $ttl) == true) {
            Cache::forget('api:local:exp:rec:'.$id);
        }

        $res = Cache::remember('api:local:exp:rec:'.$id, now()->addMinutes(5), function() use($id, $following, $ids) {
            return Profile::select(
                'id',
                'username'
            )
            ->whereNotIn('id', $following)
            ->whereIn('id', $ids)
            ->whereIsPrivate(0)
            ->whereNull('status')
            ->whereNull('domain')
            ->inRandomOrder()
            ->take(3)
            ->get()
            ->map(function($item, $key) {
                return [
                    'id' => $item->id,
                    'avatar' => $item->avatarUrl(),
                    'username' => $item->username,
                    'message' => 'Recommended for You'
                ];
            });
        });

        return response()->json($res->all());
    }

    public function composeLocationSearch(Request $request)
    {
        abort_if(!Auth::check(), 403);
        $this->validate($request, [
            'q' => 'required|string'
        ]);
        $q = filter_var($request->input('q'), FILTER_SANITIZE_STRING);
        $q = '%' . $q . '%';
        $places = Place::where('name', 'like', $q)
            ->take(25)
            ->get()
            ->map(function($r) {
                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'country' => $r->country,
                    'url'   => $r->url()
                ];
        });
        return $places;
    }

}
