<?php

namespace App\Support;

use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Database\QueryException;
use Throwable;

/**
 * Classifies infrastructure exceptions that a queued job should retry rather
 * than fail-fast on.
 *
 * A transient failure is an outage in a backing service — the database,
 * redis/cache, or the queue transport — that a retry can plausibly recover
 * from (connection drop, failover, deadlock, timeout). Anything else is
 * treated as a genuine bug and left to fail fast so it surfaces quickly.
 */
class TransientException
{
    /**
     * Exception classes/interfaces treated as transient. Matched by name so a
     * driver that is not installed (phpredis vs predis) does not need loading.
     *
     * @var array<int, class-string|string>
     */
    private const MATCHERS = [
        QueryException::class,
        LimiterTimeoutException::class,
        // phpredis
        'RedisException',
        // predis
        'Predis\Connection\ConnectionExceptionInterface',
    ];

    /**
     * True when the exception is a recoverable infrastructure failure.
     */
    public static function matches(Throwable $e): bool
    {
        foreach (self::MATCHERS as $matcher) {
            if ($e instanceof $matcher) {
                return true;
            }
        }

        return false;
    }
}
