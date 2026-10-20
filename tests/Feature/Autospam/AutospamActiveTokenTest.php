<?php

use App\Jobs\AutospamPipeline\AutospamUpdateCachedDataPipeline;
use App\Services\AutospamService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

function seedAutospamToken(string $token, string $category, bool $active): void
{
    DB::table('autospam_custom_tokens')->insert([
        'token' => $token,
        'weight' => 7,
        'is_spam' => $category === 'spam',
        'category' => $category,
        'active' => $active,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/*
|--------------------------------------------------------------------------
| Custom tokens must honor the active flag
|--------------------------------------------------------------------------
|
| The cached-data pipeline rebuilt the spam/ham word lists from every custom
| token, ignoring the active flag. Deactivated tokens were still merged into
| the model. The pipeline now filters on active, so only live tokens
| contribute.
|
| No spam.json / ham.json is pre-seeded here, so the job starts from an empty
| base model and the only words present come from the custom tokens under
| test.
|
*/

beforeEach(function () {
    Storage::fake('local');
});

it('includes active spam tokens and excludes inactive ones', function () {
    seedAutospamToken('activespamword', 'spam', true);
    seedAutospamToken('inactivespamword', 'spam', false);

    (new AutospamUpdateCachedDataPipeline)->handle();

    $model = json_decode(Storage::get(AutospamService::MODEL_SPAM_PATH), true);

    expect($model['words']['spam'])->toHaveKey('activespamword');
    expect($model['words']['spam'])->not->toHaveKey('inactivespamword');
});

it('includes active ham tokens and excludes inactive ones', function () {
    seedAutospamToken('activehamword', 'ham', true);
    seedAutospamToken('inactivehamword', 'ham', false);

    (new AutospamUpdateCachedDataPipeline)->handle();

    $model = json_decode(Storage::get(AutospamService::MODEL_HAM_PATH), true);

    expect($model['words']['ham'])->toHaveKey('activehamword');
    expect($model['words']['ham'])->not->toHaveKey('inactivehamword');
});
