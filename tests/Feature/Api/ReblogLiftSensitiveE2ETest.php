<?php

use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/v1/statuses/{id} _pe reblog-lift CW consistency
|--------------------------------------------------------------------------
|
| A _pe request that omits include_reblogs lifts the boosted original's fields
| to the top level. The share row is always is_nsfw=false/cw_summary=null, so
| the top-level sensitive/spoiler_text must be lifted alongside media/content,
| otherwise the payload advertises NSFW media with "safe" CW flags.
|
*/

function makeBoostOfNsfwStatus(): array
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

    $boost = Status::factory()->create([
        'profile_id' => $sharer->profile_id,
        'type' => 'share',
        'scope' => 'public',
        'visibility' => 'public',
        'reblog_of_id' => $original->id,
        'is_nsfw' => false,
        'cw_summary' => null,
    ]);

    return [$sharer, $original, $boost];
}

it('serves a boosted NSFW post with lifted CW flags in _pe mode', function () {
    [$sharer, $original, $boost] = makeBoostOfNsfwStatus();

    Passport::actingAs($sharer, ['read']);

    $res = $this->getJson("/api/v1/statuses/{$boost->id}?_pe=1")
        ->assertOk()
        ->json();

    expect($res['reblog'])->not->toBeNull();
    expect($res['sensitive'])->toBeTrue();
    expect($res['spoiler_text'])->toBe('nudity');
    // Top-level CW must agree with the lifted original.
    expect($res['sensitive'])->toBe($res['reblog']['sensitive']);
    expect($res['spoiler_text'])->toBe($res['reblog']['spoiler_text']);
});

it('keeps the correct CW under reblog when include_reblogs is requested', function () {
    [$sharer, $original, $boost] = makeBoostOfNsfwStatus();

    Passport::actingAs($sharer, ['read']);

    $res = $this->getJson("/api/v1/statuses/{$boost->id}?_pe=1&include_reblogs=1")
        ->assertOk()
        ->json();

    expect($res['reblog']['sensitive'])->toBeTrue();
    expect($res['reblog']['spoiler_text'])->toBe('nudity');
});
