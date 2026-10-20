<?php

use App\Models\CustomFilter;
use App\Models\CustomFilterKeyword;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Expired custom filters and the per-user quota
|--------------------------------------------------------------------------
|
| The per-user filter quota must count only unexpired filters, matching the
| listing and the timeline cache which both hide expired rows. Expired rows
| used to count against the quota forever, locking users out of creating new
| filters once their slots had expired.
|
*/

function resetExpiredQuotaLimits(): void
{
    $ref = new ReflectionClass(CustomFilter::class);
    foreach (['maxFiltersPerUser', 'maxCreatePerHour', 'maxKeywordsPerFilter'] as $prop) {
        if ($ref->hasProperty($prop)) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue(null, null);
        }
    }
}

function seedFilter(int $pid, string $title, string $keyword, ?\Illuminate\Support\Carbon $expiresAt): CustomFilter
{
    $filter = CustomFilter::create([
        'profile_id' => $pid,
        'phrase' => $title,
        'action' => CustomFilter::ACTION_WARN,
        'context' => ['home'],
        'expires_at' => $expiresAt,
    ]);

    CustomFilterKeyword::create([
        'custom_filter_id' => $filter->id,
        'keyword' => $keyword,
        'whole_word' => true,
    ]);

    return $filter;
}

function createFilterRequest(string $title, string $keyword)
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
    config(['instance.enable_cc' => false]);
    config(['instance.custom_filters.max_create_per_hour' => 1000]);
    resetExpiredQuotaLimits();
});

it('does not count expired filters against the quota', function () {
    $max = CustomFilter::getMaxFiltersPerUser();

    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    for ($i = 0; $i < $max; $i++) {
        seedFilter($pid, 'expired-'.$i, 'expired'.$i, now()->subDay());
    }

    Passport::actingAs($user, ['read', 'write']);

    test()->getJson('/api/v2/filters')
        ->assertOk()
        ->assertJsonCount(0);

    createFilterRequest('fresh', 'alpha')
        ->assertOk()
        ->assertJsonPath('title', 'fresh');

    test()->getJson('/api/v2/filters')
        ->assertOk()
        ->assertJsonCount(1);
});

it('still enforces the quota for unexpired filters', function () {
    $max = CustomFilter::getMaxFiltersPerUser();

    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    for ($i = 0; $i < $max; $i++) {
        seedFilter($pid, 'live-'.$i, 'live'.$i, now()->addWeek());
    }

    Passport::actingAs($user, ['read', 'write']);

    createFilterRequest('over-limit', 'bravo')
        ->assertStatus(422)
        ->assertJsonPath('error', 'Filter limit exceeded');
});

it('allows the policy create check to pass when all filters are expired', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    for ($i = 0; $i < 150; $i++) {
        seedFilter($pid, 'expired-'.$i, 'policy'.$i, now()->subDay());
    }

    expect($user->can('create', CustomFilter::class))->toBeTrue();
});
