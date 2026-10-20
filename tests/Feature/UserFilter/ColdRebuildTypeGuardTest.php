<?php

use App\Models\Profile;
use App\Models\User;
use App\Models\UserFilter;
use App\Services\UserFilterService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Redis::flushdb();
});

afterEach(function () {
    Redis::flushdb();
});

function clearColdRebuildCache(int $pid): void
{
    Redis::del('pf:services:mutes:ids:'.$pid);
    Redis::del('pf:services:blocks:ids:'.$pid);
}

it('excludes non-profile morph rows when rebuilding the block id cache', function () {
    $victim = User::factory()->create();
    $victim->refresh();
    $pid = $victim->profile_id;

    $modernTarget = User::factory()->create();
    $modernTarget->refresh();

    $legacyTarget = User::factory()->create();
    $legacyTarget->refresh();

    // Unrelated profile whose id is reused by the legacy instance row below.
    $bystander = User::factory()->create();
    $bystander->refresh();

    UserFilter::create([
        'user_id' => $pid,
        'filterable_id' => $modernTarget->profile_id,
        'filterable_type' => Profile::class,
        'filter_type' => 'block',
    ]);

    UserFilter::create([
        'user_id' => $pid,
        'filterable_id' => $legacyTarget->profile_id,
        'filterable_type' => 'App\Profile',
        'filter_type' => 'block',
    ]);

    UserFilter::create([
        'user_id' => $pid,
        'filterable_id' => $bystander->profile_id,
        'filterable_type' => 'App\Instance',
        'filter_type' => 'block',
    ]);

    clearColdRebuildCache($pid);

    $blocks = collect(UserFilterService::blocks($pid))->map(fn ($id) => (int) $id)->all();

    expect($blocks)->toContain($modernTarget->profile_id)
        ->and($blocks)->toContain($legacyTarget->profile_id)
        ->and($blocks)->not->toContain($bystander->profile_id);

    expect(UserFilterService::blockCount($pid))->toBe(2);
});

it('excludes non-profile morph rows when rebuilding the mute id cache', function () {
    $victim = User::factory()->create();
    $victim->refresh();
    $pid = $victim->profile_id;

    $modernTarget = User::factory()->create();
    $modernTarget->refresh();

    $legacyTarget = User::factory()->create();
    $legacyTarget->refresh();

    $bystander = User::factory()->create();
    $bystander->refresh();

    UserFilter::create([
        'user_id' => $pid,
        'filterable_id' => $modernTarget->profile_id,
        'filterable_type' => Profile::class,
        'filter_type' => 'mute',
    ]);

    UserFilter::create([
        'user_id' => $pid,
        'filterable_id' => $legacyTarget->profile_id,
        'filterable_type' => 'App\Profile',
        'filter_type' => 'mute',
    ]);

    UserFilter::create([
        'user_id' => $pid,
        'filterable_id' => $bystander->profile_id,
        'filterable_type' => 'App\Instance',
        'filter_type' => 'mute',
    ]);

    clearColdRebuildCache($pid);

    $mutes = collect(UserFilterService::mutes($pid))->map(fn ($id) => (int) $id)->all();

    expect($mutes)->toContain($modernTarget->profile_id)
        ->and($mutes)->toContain($legacyTarget->profile_id)
        ->and($mutes)->not->toContain($bystander->profile_id);

    expect(UserFilterService::muteCount($pid))->toBe(2);
});

it('keeps the contaminating row out of the merged filter set', function () {
    $victim = User::factory()->create();
    $victim->refresh();
    $pid = $victim->profile_id;

    $blocked = User::factory()->create();
    $blocked->refresh();

    $bystander = User::factory()->create();
    $bystander->refresh();

    UserFilter::create([
        'user_id' => $pid,
        'filterable_id' => $blocked->profile_id,
        'filterable_type' => Profile::class,
        'filter_type' => 'block',
    ]);

    UserFilter::create([
        'user_id' => $pid,
        'filterable_id' => $bystander->profile_id,
        'filterable_type' => 'App\Instance',
        'filter_type' => 'block',
    ]);

    clearColdRebuildCache($pid);

    $filters = collect(UserFilterService::filters($pid))->map(fn ($id) => (int) $id)->all();

    expect($filters)->toContain($blocked->profile_id)
        ->and($filters)->not->toContain($bystander->profile_id);
});
