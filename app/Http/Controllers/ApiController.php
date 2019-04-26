<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\BaseApiController;
use App\Like;
use Auth;
use Cache;
use Illuminate\Http\Request;

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
                    'enabled' => config('pixelfed.activitypub_enabled'),
                    'remote_follow' => config('pixelfed.remote_follow_enabled')
                ],

                'ab' => [
                    'lc' => config('exp.lc'),
                    'rec' => config('exp.rec'),
                ],
            ];
        });
        return response()->json($res);
    }

}
