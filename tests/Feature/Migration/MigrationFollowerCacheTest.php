<?php

use App\Jobs\ProfilePipeline\ProfileMigrationMoveFollowersPipeline;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\User;
use App\Services\FollowerService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Migration follower cache invalidation
|--------------------------------------------------------------------------
|
| Moving a follower's following_id via save() does not fire the create/delete
| observers, so the Redis follower sets must be moved explicitly. Otherwise
| FollowerService::follows() keeps returning the stale OLD relationship,
| which gates access to private content.
|
*/

it('moves the cached follower relationship from the old to the new profile', function () {
    Redis::spy();

    $old = User::factory()->create();
    $old->refresh();
    $new = User::factory()->create();
    $new->refresh();
    $follower = User::factory()->create();
    $follower->refresh();

    $oldPid = $old->profile_id;
    $newPid = $new->profile_id;

    Follower::create([
        'profile_id' => $follower->profile_id,
        'following_id' => $oldPid,
    ]);

    [$job, $batch] = (new ProfileMigrationMoveFollowersPipeline($oldPid, $newPid))->withFakeBatch();
    $job->handle();

    // DB relationship moved.
    expect(Follower::where('profile_id', $follower->profile_id)->where('following_id', $newPid)->exists())->toBeTrue();
    expect(Follower::where('profile_id', $follower->profile_id)->where('following_id', $oldPid)->exists())->toBeFalse();

    // Old cached follower entry was removed and the new one added.
    Redis::shouldHaveReceived('zrem')
        ->with(FollowerService::FOLLOWERS_KEY.$oldPid, (string) $follower->profile_id);

    Redis::shouldHaveReceived('zadd')
        ->withArgs(function ($key, $ts, $member) use ($newPid, $follower) {
            return $key === FollowerService::FOLLOWERS_KEY.$newPid
                && (string) $member === (string) $follower->profile_id;
        });

    // Old profile's cached sets cleared.
    Redis::shouldHaveReceived('del')
        ->with(FollowerService::CACHE_KEY.$oldPid);
});
