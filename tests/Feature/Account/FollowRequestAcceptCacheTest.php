<?php

use App\Models\FollowRequest;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Follow-request accept must invalidate the follower's following-count cache
|--------------------------------------------------------------------------
|
| followRequestHandle increments the accepted follower's following_count but
| only forgot the acceptor's ($pid) count caches, leaving
| profile:following_count:$follower->id stale (served for up to a month).
|
*/

it('clears the accepted follower following-count cache on accept', function () {
    $locked = User::factory()->create();
    $locked->refresh();
    $locked->profile->is_private = true;
    $locked->profile->save();

    $follower = User::factory()->create();
    $follower->refresh();
    $follower->settings->update(['show_profile_following_count' => true]);

    // Warm the follower's following-count cache at 0 (no follows yet).
    $followerProfile = Profile::find($follower->profile_id);
    expect($followerProfile->followingCount())->toBe(0);

    $fr = FollowRequest::create([
        'follower_id' => $follower->profile_id,
        'following_id' => $locked->profile_id,
        'is_local' => true,
    ]);

    $this->actingAs($locked)
        ->post('/account/follow-requests', [
            'action' => 'accept',
            'id' => $fr->id,
        ])
        ->assertOk();

    // The follower now follows one account; the cached count must reflect it.
    $fresh = Profile::find($follower->profile_id);
    expect($fresh->followingCount())->toBe(1);
});
