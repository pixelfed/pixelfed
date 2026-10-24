<?php

use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Account deletion must not leak statuses
|--------------------------------------------------------------------------
|
| The delete pipelines dispatched one StatusDelete/RemoteStatusDelete per
| status while iterating with chunk() (OFFSET paging). Those jobs delete the
| rows they are handed, so the live set shrinks under the cursor and OFFSET
| paging skips roughly half the statuses, leaving them behind forever. The
| pipelines now use chunkById() (keyset paging) which is stable under deletion.
|
*/

it('dispatches deletion for every status when a local account is deleted', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    // More than one chunk (chunk size is 50); the report measured a 50% leak
    // with 200 rows under the sync queue.
    Status::factory()->count(200)->create([
        'profile_id' => $pid,
        'type' => 'text',
        'scope' => 'public',
    ]);

    expect(Status::whereProfileId($pid)->count())->toBe(200);

    (new DeleteAccountPipeline($user))->handle();

    // No status may survive pointing at the deleted profile.
    expect(Status::whereProfileId($pid)->count())->toBe(0)
        ->and(Status::withTrashed()->whereProfileId($pid)->whereNull('deleted_at')->count())->toBe(0);
});

it('dispatches deletion for every status when a remote profile is deleted', function () {
    $profile = Profile::factory()->remote()->create([
        'domain' => 'remote.example',
        'private_key' => null,
    ]);
    $pid = $profile->id;

    Status::factory()->count(200)->create([
        'profile_id' => $pid,
        'type' => 'text',
        'scope' => 'public',
        'local' => false,
    ]);

    expect(Status::whereProfileId($pid)->count())->toBe(200);

    (new DeleteRemoteProfilePipeline($profile))->handle();

    expect(Status::whereProfileId($pid)->count())->toBe(0);
});

it('force-deletes every notification tied to a deleted remote profile', function () {
    $profile = Profile::factory()->remote()->create([
        'domain' => 'remote.example',
        'private_key' => null,
    ]);
    $pid = $profile->id;

    // Half addressed to the profile, half authored by it (the actor branch of
    // the OR), so the grouped predicate + chunkById is exercised on both.
    foreach (range(1, 120) as $i) {
        $n = new Notification;
        $n->profile_id = $i % 2 === 0 ? $pid : ($pid + 1000 + $i);
        $n->actor_id = $i % 2 === 0 ? ($pid + 2000 + $i) : $pid;
        $n->action = 'like';
        $n->save();
    }

    expect(Notification::where('profile_id', $pid)->orWhere('actor_id', $pid)->count())->toBe(120);

    (new DeleteRemoteProfilePipeline($profile))->handle();

    expect(Notification::withTrashed()->where('profile_id', $pid)->orWhere('actor_id', $pid)->count())->toBe(0);
});
