<?php

use App\Models\Status;
use App\Models\User;
use App\Services\Status\UpdateStatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::spy();
});

it('re-forces is_nsfw when the owning profile has cw enabled', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->cw = true;
    $profile->save();

    $status = Status::factory()->create([
        'profile_id' => $profile->id,
        'is_nsfw' => true,
        'type' => 'photo',
        'scope' => 'public',
    ]);

    UpdateStatusService::call($status, ['sensitive' => false]);

    $status->refresh();
    expect((bool) $status->is_nsfw)->toBeTrue();
});

it('allows clearing is_nsfw when the profile does not have cw enabled', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'is_nsfw' => true,
        'type' => 'photo',
        'scope' => 'public',
    ]);

    UpdateStatusService::call($status, ['sensitive' => false]);

    $status->refresh();
    expect((bool) $status->is_nsfw)->toBeFalse();
});

it('preserves cw_summary when profile cw forces is_nsfw back on', function () {
    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;
    $profile->cw = true;
    $profile->save();

    $status = Status::factory()->create([
        'profile_id' => $profile->id,
        'is_nsfw' => true,
        'cw_summary' => 'spoiler text',
        'type' => 'photo',
        'scope' => 'public',
    ]);

    UpdateStatusService::call($status, ['sensitive' => false]);

    $status->refresh();
    expect((bool) $status->is_nsfw)->toBeTrue();
    expect($status->cw_summary)->toBe('spoiler text');
});
