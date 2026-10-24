<?php

use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserFilter;
use App\Services\UserFilterService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Deleting a remote profile must evict it from mute/block caches
|--------------------------------------------------------------------------
|
| The delete pipeline removed UserFilter rows with a bulk ->delete(), which
| skips UserFilterObserver, so UserFilterService::unblock()/unmute() never
| ran and the removed profile stayed in each muting/blocking user's warm
| Redis set (inflating counts, breaking limit checks) for up to the 90-day
| TTL. Per-row deletes must fire the observer and clear the cache.
|
*/

beforeEach(function () {
    Queue::fake();
});

function blockingUserFor(Profile $target, string $type = 'block'): User
{
    $user = User::factory()->create();
    $user->refresh();

    $filter = new UserFilter;
    $filter->user_id = $user->profile_id;
    $filter->filterable_id = $target->id;
    $filter->filterable_type = Profile::class;
    $filter->filter_type = $type;
    $filter->save();

    return $user;
}

it('evicts a deleted remote profile from a users warm block cache', function () {
    $remote = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $user = blockingUserFor($remote, 'block');

    // Warm the block cache so blocks() reads Redis directly (not a cold rebuild
    // that would drop the deleted profile on its own).
    expect(UserFilterService::blocks($user->profile_id))->toContain((string) $remote->id);

    (new DeleteRemoteProfilePipeline($remote))->handle();

    // The observer fired via per-row delete: the id is gone from the cache and
    // the block count reflects it.
    expect(collect(UserFilterService::blocks($user->profile_id))->map(fn ($i) => (string) $i))
        ->not->toContain((string) $remote->id);
    expect(UserFilterService::blockCount($user->profile_id))->toBe(0);
});

it('evicts a deleted remote profile from a users warm mute cache', function () {
    $remote = Profile::factory()->create(['user_id' => null, 'domain' => 'remote.example']);
    $user = blockingUserFor($remote, 'mute');

    expect(UserFilterService::mutes($user->profile_id))->toContain((string) $remote->id);

    (new DeleteRemoteProfilePipeline($remote))->handle();

    expect(collect(UserFilterService::mutes($user->profile_id))->map(fn ($i) => (string) $i))
        ->not->toContain((string) $remote->id);
    expect(UserFilterService::muteCount($user->profile_id))->toBe(0);
});
