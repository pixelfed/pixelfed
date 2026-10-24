<?php

use App\Models\Follower;
use App\Models\Profile;
use App\Models\User;
use App\Services\FollowerService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Going private must evict removed followers from the FollowerService cache
|--------------------------------------------------------------------------
|
| privateAccountOptions() bulk-deleted followers with a query-builder delete,
| which skips FollowerObserver and leaves the removed follower in the Redis
| sorted sets that back FollowerService::follows() — the gate for private
| content access. A removed follower could still pass the follows() check
| (authorization bypass) until the 7-day cache marker expired. Removal must
| update the cache so follows() returns false immediately.
|
*/

beforeEach(function () {
    Queue::fake();
});

function warmedFollower(Profile $target): Profile
{
    $follower = Profile::factory()->create();
    Follower::create([
        'profile_id' => $follower->id,
        'following_id' => $target->id,
        'local_profile' => true,
    ]);
    // Warm the FollowerService Redis sorted sets, as a live follow would.
    FollowerService::add($follower->id, $target->id);

    return $follower;
}

function goPrivate(User $user, string $mode): void
{
    test()->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('settings.privacy.account'), ['mode' => $mode, 'duration' => 60])
        ->assertOk();
}

it('evicts a removed follower from FollowerService::follows on remove-all', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->update(['is_private' => false, 'is_suggestable' => true]);

    $follower = warmedFollower($profile);

    // Warm cache reports the follow relationship.
    expect(FollowerService::follows($follower->id, $profile->id))->toBeTrue();

    goPrivate($user, 'remove-all');

    // The row is gone AND the cache no longer reports the follow.
    expect(Follower::whereFollowingId($profile->id)->count())->toBe(0);
    expect(FollowerService::follows($follower->id, $profile->id))->toBeFalse();
});

it('evicts removed followers from the cache on mutual-only', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->update(['is_private' => false, 'is_suggestable' => true]);

    // A one-way follower (not followed back) must be removed under mutual-only.
    $follower = warmedFollower($profile);

    expect(FollowerService::follows($follower->id, $profile->id))->toBeTrue();

    goPrivate($user, 'mutual-only');

    expect(FollowerService::follows($follower->id, $profile->id))->toBeFalse();
});

it('keeps a mutual follower under mutual-only', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->update(['is_private' => false, 'is_suggestable' => true]);

    $mutual = warmedFollower($profile);
    // The profile follows them back -> mutual, must be kept.
    Follower::create([
        'profile_id' => $profile->id,
        'following_id' => $mutual->id,
        'local_profile' => true,
    ]);
    FollowerService::add($profile->id, $mutual->id);

    goPrivate($user, 'mutual-only');

    expect(Follower::whereProfileId($mutual->id)->whereFollowingId($profile->id)->exists())->toBeTrue();
    expect(FollowerService::follows($mutual->id, $profile->id))->toBeTrue();
});
