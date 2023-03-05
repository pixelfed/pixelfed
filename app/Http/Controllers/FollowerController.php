<?php

namespace App\Http\Controllers;

use App\{
    Follower,
    FollowRequest,
    Profile,
    UserFilter
};
use Auth, Cache;
use Illuminate\Http\Request;
use App\Jobs\FollowPipeline\FollowPipeline;
use App\Util\ActivityPub\Helpers;
use App\Services\FollowerService;

class FollowerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        abort(422, 'Deprecated API Endpoint, use /api/v1/accounts/{id}/follow or /api/v1/accounts/{id}/unfollow instead.');
    }

    public function sendFollow($user, $target)
    {
        if($target->domain == null || $user->domain != null) {
            return;
        }

        $payload = [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'id'        => $user->permalink('#follow/'.$target->id),
            'type'      => 'Follow',
            'actor'     => $user->permalink(),
            'object'    => $target->permalink()
        ];

        $inbox = $target->sharedInbox ?? $target->inbox_url;

        Helpers::sendSignedObject($user, $inbox, $payload);
    }

    public function sendUndoFollow($user, $target)
    {
        if($target->domain == null || $user->domain != null) {
            return;
        }

        $payload = [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'id'        => $user->permalink('#follow/'.$target->id.'/undo'),
            'type'      => 'Undo',
            'actor'     => $user->permalink(),
            'object'    => [
                'id' => $user->permalink('#follows/'.$target->id),
                'actor' => $user->permalink(),
                'object' => $target->permalink(),
                'type' => 'Follow'
            ]
        ];

        $inbox = $target->sharedInbox ?? $target->inbox_url;

        Helpers::sendSignedObject($user, $inbox, $payload);
    }
}
