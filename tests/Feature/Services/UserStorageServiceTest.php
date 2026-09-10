<?php

use App\Models\Media;
use App\Models\User;
use App\Services\UserStorageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| UserStorageService
|--------------------------------------------------------------------------
|
| storage_used is the value enforced against max_account_size on upload. It
| must reflect actual media usage. Issue #7169 was caused by the cached value
| only ever growing, so these tests pin the calculate/recalculate behavior.
|
*/

function makeMedia(User $user, int $bytes, int $order): Media
{
    return Media::create([
        'status_id' => null,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => "public/m/_v2/1/file{$order}.jpeg",
        'mime' => 'image/jpeg',
        'size' => $bytes,
        'order' => $order,
    ]);
}

it('calculates storage used as the sum of media size in KB', function () {
    $user = User::factory()->create();
    $user->refresh();

    makeMedia($user, 500000, 1);
    makeMedia($user, 300000, 2);

    // (500000 + 300000) / 1000 = 800 KB
    expect(UserStorageService::calculateStorageUsed($user->id))->toBe(800);
});

it('returns -1 for a missing user', function () {
    expect(UserStorageService::get(999999))->toBe(-1);
});

it('populates storage_used on first get when never calculated', function () {
    $user = User::factory()->create();
    $user->refresh();

    expect($user->storage_used_updated_at)->toBeNull();

    makeMedia($user, 250000, 1);

    // First get computes and persists (250 KB).
    expect(UserStorageService::get($user->id))->toBe(250);

    $user->refresh();
    expect((int) $user->storage_used)->toBe(250);
    expect($user->storage_used_updated_at)->not->toBeNull();
});

it('returns the cached value on subsequent gets without recomputing', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Seed a cached value that intentionally disagrees with actual media.
    $user->storage_used = 12345;
    $user->storage_used_updated_at = now();
    $user->save();

    makeMedia($user, 500000, 1);

    // get() trusts the cache; it does NOT recompute here.
    expect(UserStorageService::get($user->id))->toBe(12345);
});

/*
| Regression (#7169): a stale, inflated storage_used must be repairable by
| recalculateUpdateStorageUsed, dropping to the real media sum.
*/
it('recalculate resets a stale inflated counter to actual usage', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 999999;
    $user->storage_used_updated_at = now();
    $user->save();

    makeMedia($user, 100000, 1);

    $updated = UserStorageService::recalculateUpdateStorageUsed($user->id);

    expect($updated)->toBe(100);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(100);
});

it('recalculate returns zero when the user has no media', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 500000;
    $user->storage_used_updated_at = now();
    $user->save();

    expect(UserStorageService::recalculateUpdateStorageUsed($user->id))->toBe(0);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(0);
});

/*
| increaseStorageUsed is the fast upload path: add the stored media's size
| (bytes) to the cached KB counter, without re-summing.
*/
it('increases storage_used by the added media size in KB', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 300;
    $user->storage_used_updated_at = now();
    $user->save();

    // 500000 bytes = 500 KB added.
    $result = UserStorageService::increaseStorageUsed($user->id, 500000);

    expect($result)->toBe(800);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(800);
});

it('seeds from actual media when increasing an uncalculated counter', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Fresh user with pre-existing media but no cached counter yet.
    expect($user->storage_used_updated_at)->toBeNull();
    makeMedia($user, 200000, 1); // 200 KB already on disk

    // Add another 500 KB; base should be seeded from the 200 KB of media.
    $result = UserStorageService::increaseStorageUsed($user->id, 500000);

    expect($result)->toBe(700);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(700);
    expect($user->storage_used_updated_at)->not->toBeNull();
});

it('returns null when increasing a missing user', function () {
    expect(UserStorageService::increaseStorageUsed(999999, 500000))->toBeNull();
});

it('increase then decrement of the same size is a no-op on the counter', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 1000;
    $user->storage_used_updated_at = now();
    $user->save();

    UserStorageService::increaseStorageUsed($user->id, 400000);
    UserStorageService::decrementStorageUsed($user->id, 400000);

    $user->refresh();
    expect((int) $user->storage_used)->toBe(1000);
});

/*
| decrementStorageUsed is the fast delete path: subtract the removed media's
| size (bytes) from the cached KB counter, clamped at zero, without re-summing.
*/
it('decrements storage_used by the removed media size in KB', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 800;
    $user->storage_used_updated_at = now();
    $user->save();

    // 500000 bytes = 500 KB removed.
    $result = UserStorageService::decrementStorageUsed($user->id, 500000);

    expect($result)->toBe(300);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(300);
});

it('clamps decrement at zero and never goes negative', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 100;
    $user->storage_used_updated_at = now();
    $user->save();

    $result = UserStorageService::decrementStorageUsed($user->id, 500000);

    expect($result)->toBe(0);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(0);
});

it('skips decrement when the counter was never calculated', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Fresh user: storage_used_updated_at is null.
    expect($user->storage_used_updated_at)->toBeNull();

    $result = UserStorageService::decrementStorageUsed($user->id, 500000);

    // Skipped so a later get()/recalculate establishes the true value.
    expect($result)->toBeNull();
    $user->refresh();
    expect($user->storage_used_updated_at)->toBeNull();
});

it('returns null when decrementing a missing user', function () {
    expect(UserStorageService::decrementStorageUsed(999999, 500000))->toBeNull();
});

/*
| The user:storage:recalculate command repairs already-affected accounts,
| which is how existing users escape a stale limit after upgrading (#7169).
*/
it('recalculate command repairs a specific stale user', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 888888;
    $user->storage_used_updated_at = now();
    $user->save();

    makeMedia($user, 200000, 1);

    $this->artisan('user:storage:recalculate', ['--user' => $user->id])
        ->assertExitCode(0);

    $user->refresh();
    expect((int) $user->storage_used)->toBe(200);
});

it('recalculate command repairs all users in a bulk run', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $a->refresh();
    $b->refresh();

    foreach ([$a, $b] as $u) {
        $u->storage_used = 777777;
        $u->storage_used_updated_at = now();
        $u->save();
    }

    makeMedia($a, 400000, 1);
    // $b has no media.

    $this->artisan('user:storage:recalculate')->assertExitCode(0);

    $a->refresh();
    $b->refresh();
    expect((int) $a->storage_used)->toBe(400);
    expect((int) $b->storage_used)->toBe(0);
});
