<?php

use App\Http\Controllers\FollowerController;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Follow / Undo id continuity
|--------------------------------------------------------------------------
|
| sendFollow emitted a Follow id of #follow/{id} (singular) while
| sendUndoFollow's nested object.id cited #follows/{id} (plural), so the Undo
| referenced an activity id the server never sent. The Follow activity id and
| the Undo's nested object.id must match; the Undo's own id stays distinct.
|
| Helpers::sendSignedObject is aliased with Mockery to capture the outbound
| payloads the controller actually builds.
|
*/

it('emits a Follow id that matches the Undo object id', function () {
    $captured = [];

    $helpers = Mockery::mock('alias:App\Util\ActivityPub\Helpers');
    $helpers->shouldReceive('sendSignedObject')
        ->andReturnUsing(function ($user, $inbox, $payload) use (&$captured) {
            $captured[] = $payload;
        });

    $user = User::factory()->create();
    $user->refresh();
    $target = Profile::factory()->remote()->create([
        'sharedInbox' => 'https://remote.example/inbox',
        'inbox_url' => 'https://remote.example/users/x/inbox',
    ]);

    $controller = new FollowerController;
    $controller->sendFollow($user->profile, $target);
    $controller->sendUndoFollow($user->profile, $target);

    expect($captured)->toHaveCount(2);

    [$follow, $undo] = $captured;

    expect($follow['type'])->toBe('Follow');
    expect($undo['type'])->toBe('Undo');

    // The Undo's nested object.id must reference the exact Follow id emitted.
    expect($undo['object']['id'])->toBe($follow['id']);

    // The Undo activity's own id is a distinct activity id.
    expect($undo['id'])->not->toBe($follow['id']);
})->skip(fn () => ! class_exists(Mockery::class), 'Mockery required');
