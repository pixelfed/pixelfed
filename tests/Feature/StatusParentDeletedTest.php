<?php

use App\Models\Status;
use App\Models\User;
use App\Transformer\ActivityPub\Verb\Announce;
use App\Transformer\ActivityPub\Verb\CreateNote;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use League\Fractal;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Status::parent() must not throw when the parent is deleted
|--------------------------------------------------------------------------
|
| parent() used findOrFail(), throwing ModelNotFoundException when the
| replied-to/reblogged status was removed. Callers (ActivityPub transformers)
| treat the result as nullable and dereference it, so a deleted parent broke
| federation for the reply/boost. parent() now returns null, and the
| transformers null-guard before dereferencing.
|
*/

it('returns null instead of throwing when the reply parent is deleted', function () {
    $user = User::factory()->create();
    $user->refresh();

    $parent = Status::factory()->create(['profile_id' => $user->profile_id]);
    $reply = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $user->profile_id,
    ]);

    $parent->forceDelete();

    expect($reply->fresh()->parent())->toBeNull();
});

it('returns null instead of throwing when the reblogged parent is deleted', function () {
    $user = User::factory()->create();
    $user->refresh();

    $original = Status::factory()->create(['profile_id' => $user->profile_id]);
    $boost = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'reblog_of_id' => $original->id,
    ]);

    $original->forceDelete();

    expect($boost->fresh()->parent())->toBeNull();
});

it('returns false when there is no parent id', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create(['profile_id' => $user->profile_id]);

    expect($status->parent())->toBeFalse();
});

it('builds CreateNote for a reply whose parent was deleted without crashing', function () {
    $user = User::factory()->create();
    $user->refresh();

    $parent = Status::factory()->create(['profile_id' => $user->profile_id]);
    $reply = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $user->profile_id,
    ]);

    $parent->forceDelete();

    $fractal = new Fractal\Manager;
    $data = $fractal->createData(new Fractal\Resource\Item($reply->fresh(), new CreateNote))->toArray()['data'];

    expect($data)->toBeArray();
    expect($data['type'])->toBe('Create');
});

it('builds Announce for a boost whose original was deleted without crashing', function () {
    $user = User::factory()->create();
    $user->refresh();

    $original = Status::factory()->create(['profile_id' => $user->profile_id]);
    $boost = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'reblog_of_id' => $original->id,
    ]);

    $original->forceDelete();

    $fractal = new Fractal\Manager;
    $data = $fractal->createData(new Fractal\Resource\Item($boost->fresh(), new Announce))->toArray()['data'];

    expect($data)->toBeArray();
    expect($data['type'])->toBe('Announce');
    // The deleted original has no url; the object degrades to null, not a crash.
    expect($data['object'])->toBeNull();
});
