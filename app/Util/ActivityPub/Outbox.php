<?php

namespace App\Util\ActivityPub;

use App\Profile;
use League\Fractal;
use App\Http\Controllers\ProfileController;
use App\Transformer\ActivityPub\ProfileOutbox;

class Outbox
{
    public static function get($username)
    {
        abort_if(!config('federation.activitypub.enabled'), 404);
        abort_if(!config('federation.activitypub.outbox'), 404);

        $profile = Profile::whereNull('remote_url')->whereUsername($username)->firstOrFail();
        if ($profile->status != null) {
            return ProfileController::accountCheck($profile);
        }
        if ($profile->is_private) {
            return response()->json(['error'=>'403', 'msg' => 'private profile'], 403);
        }
        $timeline = $profile->statuses()->whereVisibility('public')->orderBy('created_at', 'desc')->paginate(10);
        $fractal = new Fractal\Manager();
        $resource = new Fractal\Resource\Item($profile, new ProfileOutbox());
        $res = $fractal->createData($resource)->toArray();

        return $res['data'];
    }
}
