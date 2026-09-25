<?php

use App\Support\TransientException;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Database\QueryException;

/*
|--------------------------------------------------------------------------
| TransientException classifier
|--------------------------------------------------------------------------
|
| Shared helper for queued jobs: infrastructure outages (DB, redis/cache,
| queue transport) are retryable; genuine bugs are not.
|
*/

it('treats a database query exception as transient', function () {
    $e = new QueryException('mysql', 'select 1', [], new RuntimeException('gone away'));

    expect(TransientException::matches($e))->toBeTrue();
});

it('treats a redis connection exception as transient', function () {
    expect(TransientException::matches(new RedisException('connection refused')))->toBeTrue();
});

it('treats a redis limiter timeout as transient', function () {
    expect(TransientException::matches(new LimiterTimeoutException))->toBeTrue();
});

it('does not treat a generic runtime exception as transient', function () {
    expect(TransientException::matches(new RuntimeException('genuine bug')))->toBeFalse();
});

it('does not treat a type error as transient', function () {
    expect(TransientException::matches(new TypeError('bad argument')))->toBeFalse();
});
