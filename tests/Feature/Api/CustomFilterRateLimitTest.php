<?php

use App\Models\CustomFilter;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Custom filter create rate limit TTL
|--------------------------------------------------------------------------
|
| The per-hour create limit counter must carry a 1h TTL so it resets after the
| window. Previously Cache::increment created the key with no TTL, so once the
| cap was hit the 429 persisted indefinitely (until external cache eviction).
|
*/

/**
 * Reset the memoized limit statics so per-test config overrides take effect.
 */
function resetFilterLimitStatics(): void
{
    foreach (['maxCreatePerHour', 'maxUpdatesPerHour', 'maxFiltersPerUser'] as $prop) {
        $ref = new ReflectionProperty(CustomFilter::class, $prop);
        $ref->setAccessible(true);
        $ref->setValue(null, null);
    }
}

function createFilter(string $title, string $keyword)
{
    return test()->postJson('/api/v2/filters', [
        'title' => $title,
        'context' => ['home'],
        'filter_action' => 'warn',
        'keywords_attributes' => [
            ['keyword' => $keyword, 'whole_word' => true],
        ],
    ]);
}

beforeEach(function () {
    Cache::flush();
    config(['instance.custom_filters.max_create_per_hour' => 2]);
    config(['instance.custom_filters.max_filters_per_user' => 100]);
    resetFilterLimitStatics();
});

it('resets the create rate limit after the 1 hour window', function () {
    $user = User::factory()->create();
    $user->refresh();
    Passport::actingAs($user, ['read', 'write']);

    // Two creates within the cap succeed.
    createFilter('one', 'alpha')->assertOk();
    createFilter('two', 'bravo')->assertOk();

    // Third exceeds the cap -> 429.
    createFilter('three', 'charlie')->assertStatus(429);

    // After the 1h window elapses, the counter must reset and allow writes.
    $this->travel(3700)->seconds();

    createFilter('four', 'delta')->assertOk();
});

it('rejects writes once the cap is reached within the window', function () {
    $user = User::factory()->create();
    $user->refresh();
    Passport::actingAs($user, ['read', 'write']);

    createFilter('one', 'alpha')->assertOk();
    createFilter('two', 'bravo')->assertOk();
    createFilter('three', 'charlie')->assertStatus(429);
});
