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

it('returns -1 for a suspended user', function () {
    $user = User::factory()->create(['status' => 'delete']);
    $user->refresh();

    expect(UserStorageService::get($user->id))->toBe(-1);
});

/*
| Staleness boundary: a counter is trusted right up to STALE_AFTER_HOURS and
| recomputed only once older than the window. Pins the exact threshold used by
| every self-heal path.
*/
it('treats a counter just inside the stale window as fresh', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 555;
    // 1 hour short of the window -> still fresh.
    $user->storage_used_updated_at = now()->subHours(UserStorageService::STALE_AFTER_HOURS - 1);
    $user->save();

    makeMedia($user, 999000, 1); // disagrees; must NOT be recomputed

    expect(UserStorageService::get($user->id))->toBe(555);
});

it('treats a counter just past the stale window as stale', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 555;
    $user->storage_used_updated_at = now()->subHours(UserStorageService::STALE_AFTER_HOURS + 1);
    $user->save();

    makeMedia($user, 100000, 1); // 100 KB real usage

    // Past the window -> recomputed from source.
    expect(UserStorageService::get($user->id))->toBe(100);
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

it('returns the cached value on subsequent gets without recomputing when fresh', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Seed a fresh cached value that intentionally disagrees with actual media.
    $user->storage_used = 12345;
    $user->storage_used_updated_at = now();
    $user->save();

    makeMedia($user, 500000, 1);

    // get() trusts a fresh cache; it does NOT recompute here.
    expect(UserStorageService::get($user->id))->toBe(12345);
});

/*
| Self-heal on read (#7169): a user stuck at the limit is unblocked on their
| next request because get() recomputes a stale counter from source before the
| limit check reads it. Without this, get() returned the inflated cached value
| forever and the upload was rejected before any write-path heal could run.
*/
it('recomputes a stale counter from source on get', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Inflated counter, last touched beyond the stale window (the stuck user).
    $user->storage_used = 999999;
    $user->storage_used_updated_at = now()->subHours(UserStorageService::STALE_AFTER_HOURS + 1);
    $user->save();

    // Real usage is only 200 KB.
    makeMedia($user, 200000, 1);

    // get() self-heals: returns and persists the real value, not the stale one.
    expect(UserStorageService::get($user->id))->toBe(200);

    $user->refresh();
    expect((int) $user->storage_used)->toBe(200);
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

it('recalculate does not touch a suspended user', function () {
    $user = User::factory()->create(['status' => 'delete']);
    $user->refresh();

    $user->storage_used = 999999;
    $user->storage_used_updated_at = now();
    $user->save();

    expect(UserStorageService::recalculateUpdateStorageUsed($user->id))->toBeNull();

    // Counter left untouched.
    $user->refresh();
    expect((int) $user->storage_used)->toBe(999999);
});

it('recalculate returns null for a missing user', function () {
    expect(UserStorageService::recalculateUpdateStorageUsed(999999))->toBeNull();
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

it('recalculates from source instead of adding when the counter was never calculated', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Fresh user with pre-existing media but no cached counter yet. Callers
    // save the media row before calling increaseStorageUsed, so an
    // uncalculated (stale) counter recomputes from source rather than adding.
    expect($user->storage_used_updated_at)->toBeNull();
    makeMedia($user, 200000, 1); // 200 KB already on disk (the "just-saved" media)

    $result = UserStorageService::increaseStorageUsed($user->id, 200000);

    // Source already includes the 200 KB row; delta is NOT re-added.
    expect($result)->toBe(200);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(200);
    expect($user->storage_used_updated_at)->not->toBeNull();
});

/*
| Self-healing: when the cached counter is older than STALE_AFTER_HOURS, the
| upload path recalculates from source (which already includes the just-saved
| media) instead of trusting a possibly-drifted incremental value (#7169).
*/
it('recalculates from source on increase when the counter is stale', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Wildly inflated counter, last touched well beyond the stale window.
    $user->storage_used = 999999;
    $user->storage_used_updated_at = now()->subHours(UserStorageService::STALE_AFTER_HOURS + 1);
    $user->save();

    // Actual media on disk (the just-saved upload) is 300 KB.
    makeMedia($user, 300000, 1);

    $result = UserStorageService::increaseStorageUsed($user->id, 300000);

    expect($result)->toBe(300);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(300);
});

it('trusts the incremental value on increase when the counter is fresh', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Fresh counter that intentionally disagrees with actual media; the fast
    // path must trust it and only add the delta, not recompute.
    $user->storage_used = 300;
    $user->storage_used_updated_at = now();
    $user->save();
    makeMedia($user, 999000, 1); // disagrees with the cached 300

    $result = UserStorageService::increaseStorageUsed($user->id, 500000);

    // 300 + 500 = 800 (incremental), NOT recalculated from the 999 KB media.
    expect($result)->toBe(800);
});

it('returns null when increasing a missing user', function () {
    expect(UserStorageService::increaseStorageUsed(999999, 500000))->toBeNull();
});

it('returns null when increasing a suspended user', function () {
    $user = User::factory()->create(['status' => 'delete']);
    $user->refresh();

    expect(UserStorageService::increaseStorageUsed($user->id, 500000))->toBeNull();
});

/*
| Sub-KB rounding: the incremental add/subtract paths round up via
| ceil(bytes/1000), so any non-zero file under 1000 bytes counts as 1 KB.
| Documents the (intentional) rounding behavior of the hot path.
*/
it('adds one KB when the increased size is under 1000 bytes', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 500;
    $user->storage_used_updated_at = now();
    $user->save();

    // 999 bytes -> ceil(999/1000) = 1 KB.
    $result = UserStorageService::increaseStorageUsed($user->id, 999);

    expect($result)->toBe(501);
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

it('recalculates from source on decrement when the counter was never calculated', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Fresh user: storage_used_updated_at is null (treated as stale). Callers
    // delete the media row before calling decrementStorageUsed, so the remaining
    // media is the source of truth.
    expect($user->storage_used_updated_at)->toBeNull();
    makeMedia($user, 150000, 1); // 150 KB of remaining media

    $result = UserStorageService::decrementStorageUsed($user->id, 500000);

    // Recomputed from remaining media; delta is NOT subtracted on top.
    expect($result)->toBe(150);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(150);
    expect($user->storage_used_updated_at)->not->toBeNull();
});

/*
| Self-healing: when the cached counter is older than STALE_AFTER_HOURS, the
| delete path recalculates from source (which already excludes the removed
| media) instead of trusting a possibly-drifted incremental value (#7169).
*/
it('recalculates from source on decrement when the counter is stale', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 999999;
    $user->storage_used_updated_at = now()->subHours(UserStorageService::STALE_AFTER_HOURS + 1);
    $user->save();

    makeMedia($user, 250000, 1); // 250 KB remaining after the delete

    $result = UserStorageService::decrementStorageUsed($user->id, 500000);

    expect($result)->toBe(250);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(250);
});

it('trusts the incremental value on decrement when the counter is fresh', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Fresh counter that disagrees with actual media; fast path must trust it.
    $user->storage_used = 800;
    $user->storage_used_updated_at = now();
    $user->save();
    makeMedia($user, 999000, 1); // disagrees with the cached 800

    $result = UserStorageService::decrementStorageUsed($user->id, 500000);

    // 800 - 500 = 300 (incremental), NOT recalculated from the 999 KB media.
    expect($result)->toBe(300);
});

it('returns null when decrementing a missing user', function () {
    expect(UserStorageService::decrementStorageUsed(999999, 500000))->toBeNull();
});

it('returns null when decrementing a suspended user', function () {
    $user = User::factory()->create(['status' => 'delete']);
    $user->refresh();

    expect(UserStorageService::decrementStorageUsed($user->id, 500000))->toBeNull();
});

it('subtracts one KB when the decreased size is under 1000 bytes', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 500;
    $user->storage_used_updated_at = now();
    $user->save();

    // 999 bytes -> ceil(999/1000) = 1 KB.
    $result = UserStorageService::decrementStorageUsed($user->id, 999);

    expect($result)->toBe(499);
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

it('recalculate command fails for a missing user id', function () {
    $this->artisan('user:storage:recalculate', ['--user' => 999999])
        ->assertExitCode(1);
});

/*
| --stale=<hours> limits the bulk run to counters older than N hours (or
| never calculated), so the scheduled reconciler only touches drifted rows.
*/
it('recalculate command with --stale only recomputes stale users', function () {
    $fresh = User::factory()->create();
    $stale = User::factory()->create();
    $fresh->refresh();
    $stale->refresh();

    // Fresh user: recently updated, inflated value that must be left alone.
    $fresh->storage_used = 111111;
    $fresh->storage_used_updated_at = now()->subHours(1);
    $fresh->save();
    makeMedia($fresh, 500000, 1);

    // Stale user: updated long ago, inflated value that must be corrected.
    $stale->storage_used = 222222;
    $stale->storage_used_updated_at = now()->subHours(200);
    $stale->save();
    makeMedia($stale, 300000, 2);

    $this->artisan('user:storage:recalculate', ['--stale' => 168])
        ->assertExitCode(0);

    $fresh->refresh();
    $stale->refresh();

    // Fresh untouched, stale corrected to real usage.
    expect((int) $fresh->storage_used)->toBe(111111);
    expect((int) $stale->storage_used)->toBe(300);
});

it('recalculate command with --stale recomputes never-calculated users', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Never calculated (null timestamp) counts as stale for the filter.
    expect($user->storage_used_updated_at)->toBeNull();
    makeMedia($user, 250000, 1);

    $this->artisan('user:storage:recalculate', ['--stale' => 168])
        ->assertExitCode(0);

    $user->refresh();
    expect((int) $user->storage_used)->toBe(250);
});
