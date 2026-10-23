<?php

use App\Models\Instance;
use App\Services\DeliveryHostService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| DeliveryHostService stale in-process memo - regression
|--------------------------------------------------------------------------
|
| recordSuccesses() filters the success set against flagged(), a per-process
| static memo (30s TTL). Another worker recording failures for a host cannot
| invalidate this worker's memo, so a reachable response could be dropped and
| delivery_failures left un-reset. recordSuccesses() must flush its own memo
| before reading flagged() so it reloads authoritative state.
|
| Simulating a concurrent worker in a single PHP process: we cannot have two
| separate static memos, so we write the failure rows directly and forget the
| shared cache key (what the other worker's applyFailure()+flush() does) WITHOUT
| touching this process's static memo. Calling DeliveryHostService::flush()
| here would defeat the test by clearing the very memo we are proving stale.
*/

// Must match DeliveryHostService::CACHE_KEY (private).
const DELIVERY_HOSTS_CACHE_KEY = 'pf:services:delivery:hosts';

/**
 * Mimic a different worker recording a failure: bump the DB counter and drop
 * the shared Redis snapshot, leaving this process's static memo untouched.
 */
function otherWorkerRecordsFailure(string $domain): void
{
    $instance = Instance::firstOrNew(['domain' => $domain]);
    $instance->delivery_failures = (int) $instance->delivery_failures + 1;
    $instance->save();

    Cache::forget(DELIVERY_HOSTS_CACHE_KEY);
}

it('resets an under-threshold host on success even when the memo is stale', function () {
    // Prime this worker's memo with the empty (no-failures) snapshot.
    DeliveryHostService::isUnavailable('a.example');

    // A concurrent worker records 4 failures (still under the default
    // threshold of 5, so the host is not being skipped).
    for ($i = 0; $i < 4; $i++) {
        otherWorkerRecordsFailure('a.example');
    }

    // The host is under threshold, so delivery to it proceeds normally.
    expect(DeliveryHostService::isUnavailable('a.example'))->toBeFalse();

    DeliveryHostService::recordSuccess('a.example');

    expect(Instance::whereDomain('a.example')->value('delivery_failures'))->toBe(0);
});
