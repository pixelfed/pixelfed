<?php

use App\Jobs\FollowPipeline\FollowPipeline;
use App\Models\Follower;
use App\Models\Notification;
use App\Models\Profile;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('updates the local actor following_count when following a remote target', function () {
    $user = User::factory()->create();
    $user->refresh();
    $actor = $user->profile;

    $target = Profile::factory()->remote()->create();

    $follower = Follower::firstOrCreate([
        'profile_id' => $actor->id,
        'following_id' => $target->id,
    ]);

    $targetFollowersBefore = $target->fresh()->followers_count;

    FollowPipeline::dispatch($follower);

    expect($actor->fresh()->following_count)->toBe(1);
    expect(AccountService::get($actor->id)['following_count'])->toBe(1);

    // A remote target's follower count is owned by its home server, so the
    // pipeline must leave it untouched and must not notify a remote profile.
    expect($target->fresh()->followers_count)->toBe($targetFollowersBefore);
    expect(
        Notification::whereProfileId($target->id)->whereAction('follow')->exists()
    )->toBeFalse();
});

it('updates both profiles and notifies on a local to local follow', function () {
    $user = User::factory()->create();
    $user->refresh();
    $actor = $user->profile;

    $targetUser = User::factory()->create();
    $targetUser->refresh();
    $target = $targetUser->profile;

    $follower = Follower::firstOrCreate([
        'profile_id' => $actor->id,
        'following_id' => $target->id,
    ]);

    FollowPipeline::dispatch($follower);

    expect($actor->fresh()->following_count)->toBe(1);
    expect($target->fresh()->followers_count)->toBe(1);
    expect(AccountService::get($actor->id)['following_count'])->toBe(1);

    expect(
        Notification::whereProfileId($target->id)
            ->whereAction('follow')
            ->whereActorId($actor->id)
            ->exists()
    )->toBeTrue();
});
