<?php

use App\Models\Status;
use App\Models\User;
use App\Services\StatusService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| _pe reblog-lift CW/NSFW consistency
|--------------------------------------------------------------------------
|
| When a _pe request omits include_reblogs, StatusService::get lifts the
| boosted original's fields to the top level so clients that don't resolve
| `reblog` render the shared content. The share row carries no NSFW/CW
| metadata, so the lift must also carry sensitive/spoiler_text or the payload
| ends up with NSFW media/content but "safe" content-warning flags.
|
*/

/**
 * Bind a controlled request so the lift branch (`_pe` present, `include_reblogs`
 * absent) is exercised without hitting the HTTP layer.
 */
function bindPeRequest(array $query = ['_pe' => '1']): void
{
    app()->instance('request', Request::create('/api/v1/timelines/home', 'GET', $query));
}

function makeBoostOfNsfw(): array
{
    $author = User::factory()->create();
    $author->refresh();
    $sharer = User::factory()->create();
    $sharer->refresh();

    $original = Status::factory()->nsfw()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'cw_summary' => 'nudity',
    ]);

    // Share rows never carry NSFW/CW metadata of their own.
    $boost = Status::factory()->create([
        'profile_id' => $sharer->profile_id,
        'type' => 'share',
        'scope' => 'public',
        'visibility' => 'public',
        'reblog_of_id' => $original->id,
        'is_nsfw' => false,
        'cw_summary' => null,
    ]);

    return [$original, $boost];
}

it('lifts sensitive and spoiler_text from the boosted original for _pe clients', function () {
    [$original, $boost] = makeBoostOfNsfw();

    bindPeRequest(['_pe' => '1']);

    $res = StatusService::get($boost->id, false);

    expect($res)->not->toBeNull();
    expect($res['reblog'])->not->toBeNull();
    // Top level must agree with the lifted content, not the safe share row.
    expect($res['sensitive'])->toBeTrue();
    expect($res['spoiler_text'])->toBe('nudity');
    // And still match the nested original the lift copied from.
    expect($res['sensitive'])->toBe($res['reblog']['sensitive']);
    expect($res['spoiler_text'])->toBe($res['reblog']['spoiler_text']);
});

it('does not touch the nested reblog CW fields when include_reblogs is sent', function () {
    [$original, $boost] = makeBoostOfNsfw();

    // Reblog-reading clients pass include_reblogs, so the lift must not fire.
    bindPeRequest(['_pe' => '1', 'include_reblogs' => '1']);

    $res = StatusService::get($boost->id, false);

    expect($res['reblog'])->not->toBeNull();
    // The original still carries correct CW under `reblog`.
    expect($res['reblog']['sensitive'])->toBeTrue();
    expect($res['reblog']['spoiler_text'])->toBe('nudity');
    // Top level is left as the share row reported it (unlifted).
    expect($res['sensitive'])->toBeFalse();
    expect($res['spoiler_text'])->toBe('');
});

it('leaves a non-NSFW boost unchanged at the top level', function () {
    $author = User::factory()->create();
    $author->refresh();
    $sharer = User::factory()->create();
    $sharer->refresh();

    $original = Status::factory()->create([
        'profile_id' => $author->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    $boost = Status::factory()->create([
        'profile_id' => $sharer->profile_id,
        'type' => 'share',
        'scope' => 'public',
        'visibility' => 'public',
        'reblog_of_id' => $original->id,
    ]);

    bindPeRequest(['_pe' => '1']);

    $res = StatusService::get($boost->id, false);

    expect($res['sensitive'])->toBeFalse();
    expect($res['spoiler_text'])->toBe('');
});
