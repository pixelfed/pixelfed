<?php

use App\Models\Status;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Seasonal "Year in Review" aggregation
|--------------------------------------------------------------------------
|
| SeasonalController::getData previously computed the average number of
| posts per profile by loading every grouped row into PHP and calling
| ->pluck('count')->avg(). This verifies the SQL-side replacement:
| AVG over a subquery of per-profile counts returns the same value
| without materialising every group in memory.
|
*/

/**
 * Mirrors the aggregation used in SeasonalController::getData for
 * average posts per profile.
 */
function averagePostsPerProfile(string $epochStart, string $epochEnd): float
{
    return (float) DB::query()->fromSub(
        Status::query()
            ->whereNull('uri')
            ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
            ->where('created_at', '>', $epochStart)
            ->where('created_at', '<', $epochEnd)
            ->groupBy('profile_id')
            ->selectRaw('count(*) as count'),
        'per_profile'
    )->avg('count');
}

it('averages matching posts per profile in sql', function () {
    $epochStart = '2020-01-01 00:00:00';
    $epochEnd = '2020-12-31 23:59:59';
    $inRange = '2020-06-01 12:00:00';

    // Profile 1: 2 matching photos. Profile 2: 4 matching photos.
    // Average per profile = 3.
    Status::factory()->count(2)->photo()->create([
        'profile_id' => 1001,
        'created_at' => $inRange,
    ]);
    Status::factory()->count(4)->photo()->create([
        'profile_id' => 1002,
        'created_at' => $inRange,
    ]);

    // Noise that must be excluded from the average:
    // remote (uri set), wrong type, and out-of-range date.
    Status::factory()->photo()->create([
        'profile_id' => 1003,
        'uri' => 'https://remote.example/statuses/1',
        'created_at' => $inRange,
    ]);
    Status::factory()->create([
        'profile_id' => 1003,
        'type' => 'text',
        'created_at' => $inRange,
    ]);
    Status::factory()->photo()->create([
        'profile_id' => 1004,
        'created_at' => '2019-01-01 00:00:00',
    ]);

    expect(averagePostsPerProfile($epochStart, $epochEnd))->toBe(3.0);
});

it('returns zero when no posts match', function () {
    $average = averagePostsPerProfile('2020-01-01 00:00:00', '2020-12-31 23:59:59');

    // No rows -> AVG returns null -> cast to 0.0
    expect($average)->toBe(0.0);
});
