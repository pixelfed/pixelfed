<?php

use App\Jobs\AutospamPipeline\AutospamUpdateCachedDataPipeline;
use App\Services\AutospamService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

const IDEMPOTENT_MERGE_TOKEN_WEIGHT = 10;

function seedIdempotentAutospamToken(string $token, string $category): void
{
    DB::table('autospam_custom_tokens')->insert([
        'token' => $token,
        'weight' => IDEMPOTENT_MERGE_TOKEN_WEIGHT,
        'is_spam' => $category === 'spam',
        'category' => $category,
        'active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/*
|--------------------------------------------------------------------------
| Custom-token merge must be idempotent
|--------------------------------------------------------------------------
|
| Re-running the cached-data pipeline without any database change used to add
| a token's weight onto the previously persisted count, so the same token kept
| growing (10 -> 20 -> 30). The merge now sets each active token's weight as an
| absolute value, so a token with weight 10 contributes exactly 10 no matter
| how many times the pipeline runs.
|
| No spam.json / ham.json is pre-seeded, so each word's value comes solely from
| the custom token under test and is exactly assertable.
|
*/

beforeEach(function () {
    Storage::fake('local');
});

it('applies a spam token weight exactly once across repeated runs', function () {
    seedIdempotentAutospamToken('idempotentspamword', 'spam');

    (new AutospamUpdateCachedDataPipeline)->handle();

    $first = json_decode(Storage::get(AutospamService::MODEL_SPAM_PATH), true);
    expect($first['words']['spam'])->toHaveKey('idempotentspamword');
    expect($first['words']['spam']['idempotentspamword'])->toBe(IDEMPOTENT_MERGE_TOKEN_WEIGHT);

    (new AutospamUpdateCachedDataPipeline)->handle();
    (new AutospamUpdateCachedDataPipeline)->handle();

    $later = json_decode(Storage::get(AutospamService::MODEL_SPAM_PATH), true);
    expect($later['words']['spam'])->toHaveKey('idempotentspamword');
    expect($later['words']['spam']['idempotentspamword'])->toBe(IDEMPOTENT_MERGE_TOKEN_WEIGHT);
});

it('applies a ham token weight exactly once across repeated runs', function () {
    seedIdempotentAutospamToken('idempotenthamword', 'ham');

    (new AutospamUpdateCachedDataPipeline)->handle();

    $first = json_decode(Storage::get(AutospamService::MODEL_HAM_PATH), true);
    expect($first['words']['ham'])->toHaveKey('idempotenthamword');
    expect($first['words']['ham']['idempotenthamword'])->toBe(IDEMPOTENT_MERGE_TOKEN_WEIGHT);

    (new AutospamUpdateCachedDataPipeline)->handle();
    (new AutospamUpdateCachedDataPipeline)->handle();

    $later = json_decode(Storage::get(AutospamService::MODEL_HAM_PATH), true);
    expect($later['words']['ham'])->toHaveKey('idempotenthamword');
    expect($later['words']['ham']['idempotenthamword'])->toBe(IDEMPOTENT_MERGE_TOKEN_WEIGHT);
});
