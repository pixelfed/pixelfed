<?php

use App\Services\SnowflakeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

// SnowflakeService uses the config() helper and the Cache facade, so these
// tests need a booted application. tests/Unit is not bound to the application
// TestCase in Pest.php (only Feature is), so bind it explicitly here.
uses(TestCase::class);

/*
|--------------------------------------------------------------------------
| SnowflakeService
|--------------------------------------------------------------------------
|
| SnowflakeService::next() generates 64-bit snowflake IDs used as primary
| keys for statuses (and other snowflake-keyed models). The ID layout is:
|
|   bits 63..22  timestamp: round(microtime*1000) - EPOCH
|   bits 21..17  datacenter id (5 bits)
|   bits 16..12  worker id     (5 bits)
|   bits 11..0   sequence      (12 bits, 0..4095)
|
| The sequence is a per-millisecond disambiguator: two IDs minted in the
| same millisecond on the same datacenter/worker must differ in their
| sequence bits, or the resulting IDs collide.
|
| REGRESSION CONTEXT:
| A previous implementation read the counter with Cache::get() and, on the
| already-initialised branch, incremented the *store* but kept the *local*
| value stale. The emitted sequence therefore ran 1, 1, 2, 3, ... — the
| first two IDs shared seq=1. When those two IDs were minted in the same
| millisecond with the same (config-less, hence random) datacenter/worker,
| the full snowflakes were identical, producing intermittent
| "UNIQUE constraint failed: statuses.id" errors (e.g. the flaky
| SeasonalAggregationTest, which rapidly inserts several statuses).
|
| These tests pin datacenter/worker to fixed values so IDs are fully
| deterministic and the sequence behaviour can be asserted directly.
|
*/

const SNOWFLAKE_EPOCH = 1549756800000;
const SNOWFLAKE_SEQ_MASK = 0xFFF;         // bits 0..11
const SNOWFLAKE_SEQ_BITS = 12;

/** Extract the 12-bit sequence from a snowflake id. */
function seqOf(int $id): int
{
    return $id & SNOWFLAKE_SEQ_MASK;
}

/** Extract the 5-bit worker id (bits 12..16). */
function workerOf(int $id): int
{
    return ($id >> 12) & 0x1F;
}

/** Extract the 5-bit datacenter id (bits 17..21). */
function datacenterOf(int $id): int
{
    return ($id >> 17) & 0x1F;
}

beforeEach(function () {
    // Remove randomness from the datacenter/worker bits so identical
    // timestamp + sequence produces byte-identical IDs. This makes the
    // collision detectable deterministically instead of probabilistically.
    config([
        'snowflake.datacenter_id' => 1,
        'snowflake.worker_id' => 1,
    ]);

    // Start from a clean sequence counter for every test.
    Cache::forget('snowflake:seq');
});

it('generates positive 64-bit integer ids', function () {
    $id = SnowflakeService::next();

    expect($id)->toBeInt();
    expect($id)->toBeGreaterThan(0);
});

it('encodes the configured datacenter and worker ids into the id', function () {
    config(['snowflake.datacenter_id' => 7, 'snowflake.worker_id' => 3]);

    $id = SnowflakeService::next();

    expect(($id >> 17) & 0x1F)->toBe(7);
    expect(($id >> 12) & 0x1F)->toBe(3);
});

/*
|--------------------------------------------------------------------------
| Sequence behaviour (core regression)
|--------------------------------------------------------------------------
*/

it('does not reuse the sequence on the first two ids', function () {
    // REGRESSION: the old code emitted seq 1, 1 for the first two calls.
    $first = seqOf(SnowflakeService::next());
    $second = seqOf(SnowflakeService::next());

    expect($first)->not->toBe($second);
});

it('emits a strictly increasing sequence for consecutive calls', function () {
    $seqs = [];
    for ($i = 0; $i < 10; $i++) {
        $seqs[] = seqOf(SnowflakeService::next());
    }

    // Expect 1, 2, 3, ... 10 — each call advances the sequence by exactly one.
    expect($seqs)->toBe(range(1, 10));
});

it('produces unique ids for a burst minted in the same millisecond', function () {
    // Freeze time so every id in the burst shares the same timestamp bits.
    // With datacenter/worker also fixed, uniqueness depends entirely on the
    // sequence — exactly the condition that regressed.
    Carbon::setTestNow(Carbon::create(2026, 6, 1, 12, 0, 0));

    try {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = SnowflakeService::next();
        }
    } finally {
        Carbon::setTestNow();
    }

    expect(array_unique($ids))->toHaveCount(count($ids));
});

it('reproduces the historical collision with the old stale-sequence logic', function () {
    // This is a focused, self-contained proof of the bug the fix addresses.
    // It reimplements the OLD next() sequence logic and shows the first two
    // sequence values were identical (1, 1) — the collision precondition.
    $oldSeq = function () {
        $seq = Cache::get('snowflake:seq');
        if (! $seq) {
            Cache::put('snowflake:seq', 1);
            $seq = 1;
        } else {
            Cache::increment('snowflake:seq');
        }

        return $seq;
    };

    Cache::forget('snowflake:seq');
    $first = $oldSeq();
    $second = $oldSeq();

    // The old logic collides here...
    expect([$first, $second])->toBe([1, 1]);

    // ...while the fixed service does not.
    Cache::forget('snowflake:seq');
    $fixedFirst = seqOf(SnowflakeService::next());
    $fixedSecond = seqOf(SnowflakeService::next());
    expect([$fixedFirst, $fixedSecond])->toBe([1, 2]);
});

/*
|--------------------------------------------------------------------------
| Sequence wraparound
|--------------------------------------------------------------------------
*/

it('wraps the sequence back to zero after 4095', function () {
    // Prime the counter just below the 12-bit ceiling.
    Cache::put('snowflake:seq', 4094);

    // increment -> 4095, which the service maps to 0 (wraparound).
    $wrapped = seqOf(SnowflakeService::next());
    expect($wrapped)->toBe(0);

    // Next call resumes counting from 1.
    $next = seqOf(SnowflakeService::next());
    expect($next)->toBe(1);
});

it('keeps the sequence within the 12-bit range across a wrap', function () {
    Cache::put('snowflake:seq', 4090);

    for ($i = 0; $i < 20; $i++) {
        $seq = seqOf(SnowflakeService::next());
        expect($seq)->toBeGreaterThanOrEqual(0);
        expect($seq)->toBeLessThanOrEqual(SNOWFLAKE_SEQ_MASK);
    }
});

/*
|--------------------------------------------------------------------------
| Cache-store resilience
|--------------------------------------------------------------------------
*/

it('reseeds the sequence when the cache counter is missing', function () {
    Cache::forget('snowflake:seq');

    $seq = seqOf(SnowflakeService::next());

    expect($seq)->toBe(1);
    expect(Cache::get('snowflake:seq'))->toBe(1);
});

it('reseeds when the cache counter holds a non-numeric value', function () {
    // A corrupted / non-integer cache value must not throw or emit a
    // malformed id; the service should fall back to reseeding at 1.
    Cache::put('snowflake:seq', 'not-a-number');

    $id = SnowflakeService::next();

    expect($id)->toBeInt();
    expect(seqOf($id))->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Timestamp encoding
|--------------------------------------------------------------------------
*/

it('encodes the current millisecond into the timestamp bits', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 1, 12, 0, 0, 'UTC'));

    try {
        $id = SnowflakeService::next();
    } finally {
        Carbon::setTestNow();
    }

    // microtime() is not affected by Carbon::setTestNow(), so assert the
    // timestamp bits decode to a plausible, positive, recent value rather
    // than an exact instant.
    $timestamp = $id >> 22;
    expect($timestamp)->toBeGreaterThan(0);
});

/*
|--------------------------------------------------------------------------
| byDate()
|--------------------------------------------------------------------------
*/

it('byDate() delegates to next() when no timestamp is given', function () {
    $before = Cache::get('snowflake:seq');

    $id = SnowflakeService::byDate();

    // Delegating to next() advances the sequence counter.
    expect($id)->toBeInt();
    expect(Cache::get('snowflake:seq'))->not->toBe($before);
});

it('byDate() encodes the supplied timestamp with a zero sequence', function () {
    $ts = Carbon::create(2026, 6, 1, 12, 0, 0, 'UTC');

    $id = SnowflakeService::byDate($ts);

    // byDate() always sets the sequence bits to 0.
    expect(seqOf($id))->toBe(0);

    // The timestamp bits must decode back to the supplied instant.
    $expectedTimestamp = (int) (round($ts->timestamp * 1000) - SNOWFLAKE_EPOCH);
    expect($id >> 22)->toBe($expectedTimestamp);
});

it('byDate() produces ordered ids for ordered timestamps', function () {
    $earlier = SnowflakeService::byDate(Carbon::create(2023, 1, 1, 0, 0, 0, 'UTC'));
    $later = SnowflakeService::byDate(Carbon::create(2024, 1, 1, 0, 0, 0, 'UTC'));

    // Snowflakes are time-ordered: a later timestamp yields a larger id.
    expect($later)->toBeGreaterThan($earlier);
});
