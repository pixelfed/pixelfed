<?php

use App\Models\Follower;
use App\Models\User;
use App\Services\FollowerService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| FollowerService::getFollowingIds
|--------------------------------------------------------------------------
|
| Extracted from four inline copies of the same
| Cache::remember('profile:following:'.$pid, ...) pattern. Verifies the
| method returns the followed profile ids plus the caller's own id.
|
*/

beforeEach(function () {
    Cache::flush();
});

it('returns followed ids plus the callers own id', function () {
    $user = User::factory()->create();
    $user->refresh();
    $a = User::factory()->create();
    $a->refresh();
    $b = User::factory()->create();
    $b->refresh();

    foreach ([$a->profile_id, $b->profile_id] as $targetId) {
        $f = new Follower;
        $f->profile_id = $user->profile_id;
        $f->following_id = $targetId;
        $f->save();
    }

    $ids = FollowerService::getFollowingIds($user->profile_id);

    expect($ids)->toContain($a->profile_id)
        ->toContain($b->profile_id)
        ->toContain($user->profile_id);
});

it('returns only the callers own id when following nobody', function () {
    $user = User::factory()->create();
    $user->refresh();

    $ids = FollowerService::getFollowingIds($user->profile_id);

    expect($ids)->toBe([$user->profile_id]);
});
