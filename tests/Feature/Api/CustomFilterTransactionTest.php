<?php

use App\Models\CustomFilter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Custom filter transaction hygiene
|--------------------------------------------------------------------------
|
| store()/update() open a manual DB::beginTransaction() and return early
| (duplicate keyword, keyword not found) without a matching commit or
| rollback. That leaves the transaction open on the connection past the
| end of the request. Assert the connection's transaction level is back
| to where it started once the early-exit response is returned.
|
*/

function resetCustomFilterTransactionTestLimitStatics(): void
{
    foreach (['maxCreatePerHour', 'maxUpdatesPerHour', 'maxFiltersPerUser', 'maxKeywordsPerFilter'] as $prop) {
        $ref = new ReflectionProperty(CustomFilter::class, $prop);
        $ref->setAccessible(true);
        $ref->setValue(null, null);
    }
}

beforeEach(function () {
    Cache::flush();
    config(['instance.custom_filters.max_create_per_hour' => 100]);
    config(['instance.custom_filters.max_updates_per_hour' => 100]);
    config(['instance.custom_filters.max_filters_per_user' => 100]);
    config(['instance.custom_filters.max_keywords_per_filter' => 100]);
    resetCustomFilterTransactionTestLimitStatics();
});

it('does not leave an open transaction when store() rejects a duplicate keyword', function () {
    $user = User::factory()->create();
    $user->refresh();
    Passport::actingAs($user, ['read', 'write']);

    $filter = CustomFilter::create([
        'title' => 'existing',
        'context' => ['home'],
        'action' => CustomFilter::ACTION_WARN,
        'profile_id' => $user->profile_id,
    ]);
    $filter->keywords()->create(['keyword' => 'alpha', 'whole_word' => true]);

    $levelBefore = DB::transactionLevel();

    $this->postJson('/api/v2/filters', [
        'title' => 'new filter',
        'context' => ['home'],
        'filter_action' => 'warn',
        'keywords_attributes' => [
            ['keyword' => 'alpha', 'whole_word' => true],
        ],
    ])->assertStatus(422)->assertJson(['error' => 'Duplicate keywords found']);

    expect(DB::transactionLevel())->toBe($levelBefore);
});

it('does not leave an open transaction when update() rejects a duplicate keyword', function () {
    $user = User::factory()->create();
    $user->refresh();
    Passport::actingAs($user, ['read', 'write']);

    $other = CustomFilter::create([
        'title' => 'other',
        'context' => ['home'],
        'action' => CustomFilter::ACTION_WARN,
        'profile_id' => $user->profile_id,
    ]);
    $other->keywords()->create(['keyword' => 'apple', 'whole_word' => true]);

    $target = CustomFilter::create([
        'title' => 'target',
        'context' => ['home'],
        'action' => CustomFilter::ACTION_WARN,
        'profile_id' => $user->profile_id,
    ]);
    $target->keywords()->create(['keyword' => 'banana', 'whole_word' => true]);

    $levelBefore = DB::transactionLevel();

    $this->putJson('/api/v2/filters/'.$target->id, [
        'keywords_attributes' => [
            ['keyword' => 'apple', 'whole_word' => true],
        ],
    ])->assertStatus(422)->assertJson(['error' => 'Duplicate keywords found']);

    expect(DB::transactionLevel())->toBe($levelBefore);
});
