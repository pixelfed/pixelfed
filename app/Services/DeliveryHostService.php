<?php

namespace App\Services;

use App\Models\Instance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Per-host ActivityPub delivery health.
 *
 * Fanout to a large follower audience always includes hosts that are dead,
 * defunct or persistently unreachable. Rather than validating, signing and
 * attempting delivery to those hosts on every activity, consecutive failures
 * are counted on the host's `instances` row. Once the count reaches the
 * configured threshold the host is marked unavailable with an exponential
 * backoff and skipped until `delivery_next_after` has passed. A single
 * reachable response (anything below HTTP 500, including 4xx) clears it.
 *
 * Config keys:
 *   federation.activitypub.delivery.failure_threshold   default 5
 *   federation.activitypub.delivery.max_backoff         seconds, default 7 days
 */
class DeliveryHostService
{
    private const CACHE_KEY = 'pf:services:delivery:hosts';

    private const CACHE_TTL = 300;

    /*
     * Queue workers are long-lived, so the cached map is also memoized in
     * process for a short window. This keeps a 1,500-inbox fanout from
     * hitting Redis once per inbox.
     */
    private const MEMO_TTL = 30;

    private const BASE_BACKOFF = 3600;

    private const MAX_BACKOFF_EXPONENT = 16;

    /** @var array<string, int|null>|null */
    private static ?array $memo = null;

    private static int $memoAt = 0;

    /**
     * Lowercase host of an inbox URL, or null if it cannot be parsed.
     */
    public static function domain(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $host = parse_url(trim($url), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return strtolower($host);
    }

    public static function isUnavailable(string $domain): bool
    {
        $next = self::flagged()[strtolower($domain)] ?? null;

        return $next !== null && $next > time();
    }

    /**
     * Domains currently being skipped.
     *
     * @return array<int, string>
     */
    public static function unavailable(): array
    {
        $now = time();

        return array_keys(
            array_filter(
                self::flagged(),
                fn (?int $next) => $next !== null && $next > $now
            )
        );
    }

    /**
     * Drop inbox URLs whose host is unavailable, plus any null or empty
     * entries. Keys are preserved so callers can map back to the original
     * audience index.
     *
     * @param  array<int, mixed>  $inboxes
     * @return array<int, string>
     */
    public static function filter(array $inboxes): array
    {
        $kept = [];

        foreach ($inboxes as $index => $inbox) {
            if (! is_string($inbox) || trim($inbox) === '') {
                continue;
            }

            $domain = self::domain($inbox);

            if ($domain && self::isUnavailable($domain)) {
                continue;
            }

            $kept[$index] = $inbox;
        }

        return $kept;
    }

    public static function recordFailure(string $domain): void
    {
        self::recordFailures([$domain]);
    }

    /**
     * @param  array<int, string>  $domains
     */
    public static function recordFailures(array $domains): void
    {
        $domains = self::normalize($domains);

        if (empty($domains)) {
            return;
        }

        foreach ($domains as $domain) {
            self::applyFailure($domain);
        }

        self::flush();
    }

    public static function recordSuccess(string $domain): void
    {
        self::recordSuccesses([$domain]);
    }

    /**
     * Only hosts with an existing failure count are touched, so this costs
     * nothing for the healthy majority of a fanout.
     *
     * @param  array<int, string>  $domains
     */
    public static function recordSuccesses(array $domains): void
    {
        $flagged = self::flagged();

        $domains = array_filter(
            self::normalize($domains),
            fn (string $domain) => array_key_exists($domain, $flagged)
        );

        if (empty($domains)) {
            return;
        }

        self::clear(array_values($domains));
    }

    /**
     * Clear a host's failure state manually (admin / tinker).
     */
    public static function reset(string $domain): void
    {
        $domains = self::normalize([$domain]);

        if (empty($domains)) {
            return;
        }

        self::clear(array_values($domains));
    }

    /**
     * @param  array<int, string>  $domains
     */
    private static function clear(array $domains): void
    {
        try {
            Instance::query()
                ->whereIn('domain', $domains)
                ->update([
                    'delivery_failures' => 0,
                    'delivery_timeout' => false,
                    'delivery_next_after' => null,
                ]);
        } catch (Throwable $e) {
            Log::warning('Unable to clear delivery failures', [
                'domains' => $domains,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        self::flush();
    }

    private static function applyFailure(string $domain): void
    {
        try {
            $instance = Instance::whereDomain($domain)->first();

            if (! $instance) {
                /*
                 * Explicit assignment rather than firstOrCreate() so this
                 * does not depend on Instance::$fillable.
                 */
                $instance = new Instance;
                $instance->domain = $domain;
                $instance->save();
            }

            $instance->increment('delivery_failures');

            $failures = (int) $instance->delivery_failures;
            $threshold = self::threshold();

            if ($failures < $threshold) {
                return;
            }

            $instance->forceFill([
                'delivery_timeout' => true,
                'delivery_next_after' => Carbon::now()->addSeconds(
                    self::backoff($failures - $threshold)
                ),
            ])->save();
        } catch (Throwable $e) {
            Log::warning('Unable to record delivery failure', [
                'domain' => $domain,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Domains with a non-zero failure count, mapped to the unix timestamp
     * after which delivery may be retried, or null while still under the
     * threshold.
     *
     * @return array<string, int|null>
     */
    private static function flagged(): array
    {
        if (
            self::$memo !== null
            && (time() - self::$memoAt) < self::MEMO_TTL
        ) {
            return self::$memo;
        }

        self::$memoAt = time();

        try {
            return self::$memo = Cache::remember(
                self::CACHE_KEY,
                self::CACHE_TTL,
                function () {
                    return Instance::query()
                        ->where('delivery_failures', '>', 0)
                        ->get([
                            'domain',
                            'delivery_timeout',
                            'delivery_next_after',
                        ])
                        ->mapWithKeys(function ($instance) {
                            $next = null;

                            if (
                                $instance->delivery_timeout
                                && $instance->delivery_next_after
                            ) {
                                $next = Carbon::parse(
                                    $instance->delivery_next_after
                                )->getTimestamp();
                            }

                            return [strtolower($instance->domain) => $next];
                        })
                        ->all();
                }
            );
        } catch (Throwable $e) {
            /*
             * Never let host tracking break delivery. Memoize the empty
             * result so a broken setup (e.g. migration not run) is logged
             * once per window rather than once per inbox.
             */
            Log::warning('Unable to load delivery host state', [
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return self::$memo = [];
        }
    }

    private static function flush(): void
    {
        self::$memo = null;
        self::$memoAt = 0;

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<int, mixed>  $domains
     * @return array<string, string> Deduplicated, keyed by domain
     */
    private static function normalize(array $domains): array
    {
        $out = [];

        foreach ($domains as $domain) {
            if (! is_string($domain)) {
                continue;
            }

            $domain = strtolower(trim($domain));

            if ($domain === '') {
                continue;
            }

            $out[$domain] = $domain;
        }

        return $out;
    }

    private static function threshold(): int
    {
        return max(
            1,
            (int) config('federation.activitypub.delivery.failure_threshold', 5)
        );
    }

    /**
     * Seconds to wait before retrying, doubling with each failure past the
     * threshold: 1h, 2h, 4h ... capped at max_backoff.
     */
    private static function backoff(int $excess): int
    {
        $max = max(
            self::BASE_BACKOFF,
            (int) config('federation.activitypub.delivery.max_backoff', 604800)
        );

        $exponent = min(max($excess, 0), self::MAX_BACKOFF_EXPONENT);

        return (int) min(self::BASE_BACKOFF * (2 ** $exponent), $max);
    }
}
