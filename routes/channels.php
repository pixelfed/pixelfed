<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('live.chat.{id}', function ($user, $id) {
    return true;
}, ['guards' => ['web', 'api']]);

Broadcast::channel('live.presence.{id}', function ($user, $id) {
    return [ $user->profile_id ];
}, ['guards' => ['web', 'api']]);
