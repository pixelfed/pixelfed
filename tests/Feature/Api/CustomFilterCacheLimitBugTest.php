<?php

use App\Models\CustomFilter;
use App\Models\CustomFilterKeyword;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| CustomFilter keyword cache cap regression
|--------------------------------------------------------------------------
|
| getCachedFiltersForAccount capped per-filter keywords with
| getMaxFiltersPerUser() (the per-user filter-count limit) instead of
| getMaxKeywordsPerFilter(). When max_filters_per_user < the stored keyword
| count, tail keywords were dropped from the regex and never matched.
|
*/

// The limit getters memoize into protected static properties; reset them so
// per-test config takes effect.
function resetCustomFilterLimits(): void
{
    $ref = new ReflectionClass(CustomFilter::class);
    foreach (['maxFiltersPerUser', 'maxKeywordsPerFilter', 'maxContentScanLimit'] as $prop) {
        if ($ref->hasProperty($prop)) {
            $p = $ref->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue(null, null);
        }
    }
}

beforeEach(function () {
    config(['instance.enable_cc' => false]);
    // The buggy state: fewer filters-per-user than keywords-per-filter.
    config(['instance.custom_filters.max_filters_per_user' => 2]);
    config(['instance.custom_filters.max_keywords_per_filter' => 10]);
    resetCustomFilterLimits();
    Cache::flush();
});

it('caches all keywords up to max_keywords_per_filter, not max_filters_per_user', function () {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    $filter = CustomFilter::create([
        'profile_id' => $pid,
        'phrase' => 'test',
        'action' => 0, // warn
        'context' => ['public'],
    ]);

    // 5 keywords: more than max_filters_per_user (2), within max_keywords_per_filter (10).
    $keywords = ['alpha', 'bravo', 'charlie', 'delta', 'echo'];
    foreach ($keywords as $kw) {
        CustomFilterKeyword::create([
            'custom_filter_id' => $filter->id,
            'keyword' => $kw,
            'whole_word' => false,
        ]);
    }

    $cached = CustomFilter::getCachedFiltersForAccount($pid);

    // A status containing only the 5th keyword (dropped under the bug) must match.
    $status = ['content' => 'this mentions echo only'];
    $matches = CustomFilter::applyCachedFilters($cached, $status);

    expect($matches)->not->toBeEmpty('the tail keyword must still be filtered');
});
