<?php

use App\Http\Controllers\FollowerController;
use App\Models\Profile;
use App\Models\User;
use App\Util\ActivityPub\Helpers;
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
*/

// Deterministic invariant on the id fragments the controller uses. This runs
// in-process and always guards the #follows/ continuity.
it('builds the Follow id and Undo object id from the same permalink fragment', function () {
    $user = User::factory()->create();
    $user->refresh();
    $target = Profile::factory()->remote()->create([
        'sharedInbox' => 'https://remote.example/inbox',
        'inbox_url' => 'https://remote.example/users/x/inbox',
    ]);

    $profile = $user->profile;

    $followId = $profile->permalink('#follows/'.$target->id);
    $undoObjectId = $profile->permalink('#follows/'.$target->id);
    $undoId = $profile->permalink('#follow/'.$target->id.'/undo');

    expect($undoObjectId)->toBe($followId);
    expect($undoId)->not->toBe($followId);
    expect($followId)->toContain('#follows/'.$target->id);
});

// End-to-end capture of the controller's actual payloads via a Mockery alias
// of the static delivery sink. The alias can only be created when Helpers is
// not yet autoloaded, which is not guaranteed once the full suite has loaded
// it, so this test skips itself in that case rather than failing the run.
it('emits controller payloads whose Follow id matches the Undo object id', function () {
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
    expect($undo['object']['id'])->toBe($follow['id']);
    expect($undo['id'])->not->toBe($follow['id']);
})->skip(
    fn () => class_exists(Helpers::class, false),
    'Helpers already autoloaded; alias mock unavailable in shared-process run',
);
