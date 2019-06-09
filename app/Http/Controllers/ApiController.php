<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\{
    Follower,
    Like,
    Profile,
    UserFilter
};
use Auth, Cache, Redis;
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
        $res = Cache::remember('api:site:configuration', now()->addMinutes(30), function() {
            return [
                'uploader' => [
                    'max_photo_size' => config('pixelfed.max_photo_size'),
                    'max_caption_length' => config('pixelfed.max_caption_length'),
                    'album_limit' => config('pixelfed.max_album_length'),
                    'image_quality' => config('pixelfed.image_quality'),

                    'optimize_image' => config('pixelfed.optimize_image'),
                    'optimize_video' => config('pixelfed.optimize_video'),

                    'media_types' => config('pixelfed.media_types'),
                    'enforce_account_limit' => config('pixelfed.enforce_account_limit')
                ],

                'activitypub' => [
                    'enabled' => config('federation.activitypub.enabled'),
                    'remote_follow' => config('federation.activitypub.remoteFollow')
                ],

                'ab' => [
                    'lc' => config('exp.lc'),
                    'rec' => config('exp.rec'),
                    'loops' => config('exp.loops')
                ],
            ];
        });
        return response()->json($res);
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

}
