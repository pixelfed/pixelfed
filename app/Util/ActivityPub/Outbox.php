<?php

namespace App\Util\ActivityPub;

use App\Profile;
use App\Status;
use League\Fractal;
use App\Http\Controllers\ProfileController;
use App\Transformer\ActivityPub\ProfileOutbox;
use App\Transformer\ActivityPub\Verb\CreateNote;

class Outbox {

	public static function get($profile)
	{
        abort_if(!config_cache('federation.activitypub.enabled'), 404);
        abort_if(!config('federation.activitypub.outbox'), 404);

        if($profile->status != null) {
            return ProfileController::accountCheck($profile);
        }

        if($profile->is_private) {
            return ['error'=>'403', 'msg' => 'private profile'];
        }

        $timeline = $profile
                    ->statuses()
                    ->whereScope('public')
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();

        $count = Status::whereProfileId($profile->id)->count();

        $fractal = new Fractal\Manager();
        $resource = new Fractal\Resource\Collection($timeline, new CreateNote());
        $res = $fractal->createData($resource)->toArray();

        $outbox = [
            '@context'     => 'https://www.w3.org/ns/activitystreams',
            '_debug'       => 'Outbox only supports latest 10 objects, pagination is not supported',
            'id'           => $profile->permalink('/outbox'),
            'type'         => 'OrderedCollection',
            'totalItems'   => $count,
            'orderedItems' => $res['data']
        ];
        return $outbox;
	}

}
