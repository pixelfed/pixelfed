<?php

namespace App\Http\Controllers;

use App\Federation\ActivityBuilders\FollowActivityBuilder;
use App\Util\ActivityPub\Helpers;
use Illuminate\Http\Request;

class FollowerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request): void
    {
        abort(422, 'Deprecated API Endpoint, use /api/v1/accounts/{id}/follow or /api/v1/accounts/{id}/unfollow instead.');
    }

    public function sendFollow($user, $target): void
    {
        if ($target->domain == null || $user->domain != null) {
            return;
        }

        $payload = (new FollowActivityBuilder)->build($user, $target);

        $inbox = $target->sharedInbox ?? $target->inbox_url;

        Helpers::sendSignedObject($user, $inbox, $payload);
    }

    public function sendUndoFollow($user, $target): void
    {
        if ($target->domain == null || $user->domain != null) {
            return;
        }

        $payload = (new FollowActivityBuilder)->buildUndo($user, $target);

        $inbox = $target->sharedInbox ?? $target->inbox_url;

        Helpers::sendSignedObject($user, $inbox, $payload);
    }
}
