<?php

use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\Account\AccountStatService;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| AccountService::syncPostCount must use the canonical status_count
|--------------------------------------------------------------------------
|
| syncPostCount (archive/unarchive path) used a divergent predicate
| (scope IN [...] + whereNull(in_reply_to_id, reblog_of_id), no type gate)
| that disagreed with AccountStatService::recalculateStatusCount() — the
| single source of truth shared with StatusEntityLexer, StatusDelete, the
| 6h updater and admin:fixProfileCounts. It now delegates to that helper
| and invalidates the AccountService::get cache.
|
*/

function freshProfile(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function clearThrottle(int $pid): void
{
    Cache::forget(AccountService::CACHE_KEY.'pcs:'.$pid);
}

it('does not count top-level text statuses', function () {
    $profile = freshProfile();
    Status::factory()->count(3)->create(['profile_id' => $profile->id, 'type' => 'text']);

    clearThrottle($profile->id);
    AccountService::syncPostCount($profile->id);

    $count = (int) $profile->fresh()->status_count;
    expect($count)->toBe(0);
    expect($count)->toBe(AccountStatService::recalculateStatusCount($profile->id));
});

it('still counts archived media statuses (no scope filter)', function () {
    $profile = freshProfile();
    Status::factory()->create(['profile_id' => $profile->id, 'type' => 'photo', 'scope' => 'public']);
    // Archived media: scope=archived / visibility=draft.
    Status::factory()->create([
        'profile_id' => $profile->id,
        'type' => 'photo',
        'scope' => 'archived',
        'visibility' => 'draft',
    ]);

    clearThrottle($profile->id);
    AccountService::syncPostCount($profile->id);

    $count = (int) $profile->fresh()->status_count;
    expect($count)->toBe(2);
    expect($count)->toBe(AccountStatService::recalculateStatusCount($profile->id));
});

it('counts media replies (type-only gate, includes in_reply_to_id)', function () {
    $profile = freshProfile();
    $parent = Status::factory()->create(['profile_id' => $profile->id, 'type' => 'photo']);
    // A photo reply: media type with in_reply_to_id set.
    Status::factory()->create([
        'profile_id' => $profile->id,
        'type' => 'photo',
        'in_reply_to_id' => $parent->id,
        'in_reply_to_profile_id' => $profile->id,
    ]);

    clearThrottle($profile->id);
    AccountService::syncPostCount($profile->id);

    $count = (int) $profile->fresh()->status_count;
    expect($count)->toBe(2);
    expect($count)->toBe(AccountStatService::recalculateStatusCount($profile->id));
});

it('matches recalculateStatusCount across a mixed corpus', function () {
    $profile = freshProfile();
    Status::factory()->count(2)->create(['profile_id' => $profile->id, 'type' => 'photo']);
    Status::factory()->create(['profile_id' => $profile->id, 'type' => 'video']);
    Status::factory()->count(4)->create(['profile_id' => $profile->id, 'type' => 'text']);
    Status::factory()->create([
        'profile_id' => $profile->id,
        'type' => 'photo',
        'scope' => 'archived',
        'visibility' => 'draft',
    ]);

    clearThrottle($profile->id);
    AccountService::syncPostCount($profile->id);

    $count = (int) $profile->fresh()->status_count;
    expect($count)->toBe(AccountStatService::recalculateStatusCount($profile->id));
    // 2 photo + 1 video + 1 archived photo = 4 countable; text excluded.
    expect($count)->toBe(4);
});

it('invalidates the AccountService::get cache after recompute', function () {
    $profile = freshProfile();
    Status::factory()->create(['profile_id' => $profile->id, 'type' => 'photo']);

    // Warm the 12h account cache.
    AccountService::get($profile->id, true);
    expect(Cache::get(AccountService::CACHE_KEY.$profile->id))->not->toBeNull();

    clearThrottle($profile->id);
    AccountService::syncPostCount($profile->id);

    // syncPostCount must clear the cached account snapshot.
    expect(Cache::get(AccountService::CACHE_KEY.$profile->id))->toBeNull();
});

it('honors the 72h throttle and skips redundant recomputes', function () {
    $profile = freshProfile();
    Status::factory()->create(['profile_id' => $profile->id, 'type' => 'photo']);

    clearThrottle($profile->id);
    expect(AccountService::syncPostCount($profile->id))->toBeTrue();

    // Throttled: second call within the window is a no-op.
    expect(AccountService::syncPostCount($profile->id))->toBeNull();
});
