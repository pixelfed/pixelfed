<?php

use App\Jobs\StatusPipeline\StatusDelete;
use App\Jobs\StatusPipeline\StatusEntityLexer;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\Account\AccountStatService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| status_count increment/decrement must stay in lockstep with recalc
|--------------------------------------------------------------------------
|
| AccountStatService::COUNTABLE_STATUS_TYPES is the source of truth for
| recalculateStatusCount(). StatusEntityLexer (increment) and StatusDelete
| (decrement) previously duplicated the type literal inline, so the two
| could drift from the recalc set. These tests assert that every countable
| type increments and matches the recalc, and that a non-countable type
| does neither.
|
*/

beforeEach(function () {
    Queue::fake();
});

function countableProfile(): Profile
{
    $user = User::factory()->create();

    return Profile::whereUserId($user->id)->first();
}

it('increments status_count for every countable type and matches recalc', function () {
    foreach (AccountStatService::COUNTABLE_STATUS_TYPES as $type) {
        $profile = countableProfile();
        $profile->status_count = 0;
        $profile->save();

        $status = Status::factory()->create([
            'profile_id' => $profile->id,
            'type' => $type,
        ]);

        (new StatusEntityLexer($status))->handle();

        $fresh = $profile->fresh();
        expect((int) $fresh->status_count)->toBe(1, "type {$type} should increment");
        expect((int) $fresh->status_count)
            ->toBe(AccountStatService::recalculateStatusCount($profile->id), "type {$type} count must match recalc");
    }
});

it('does not increment status_count for a non-countable type', function () {
    $profile = countableProfile();
    $profile->status_count = 0;
    $profile->save();

    $status = Status::factory()->create([
        'profile_id' => $profile->id,
        'type' => 'text',
    ]);

    (new StatusEntityLexer($status))->handle();

    expect((int) $profile->fresh()->status_count)->toBe(0);
    expect(AccountStatService::recalculateStatusCount($profile->id))->toBe(0);
});

it('decrements status_count for every countable type on delete', function () {
    foreach (AccountStatService::COUNTABLE_STATUS_TYPES as $type) {
        $profile = countableProfile();
        $profile->status_count = 1;
        $profile->save();

        $status = Status::factory()->create([
            'profile_id' => $profile->id,
            'type' => $type,
        ]);

        (new StatusDelete($status))->handle();

        expect((int) $profile->fresh()->status_count)->toBe(0, "type {$type} should decrement");
    }
});

it('does not decrement status_count for a non-countable type on delete', function () {
    $profile = countableProfile();
    $profile->status_count = 5;
    $profile->save();

    $status = Status::factory()->create([
        'profile_id' => $profile->id,
        'type' => 'text',
    ]);

    (new StatusDelete($status))->handle();

    expect((int) $profile->fresh()->status_count)->toBe(5);
});
