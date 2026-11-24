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
 *   federation.activitypub.delivery.max_backoff         seconds, default 24 hours
 */
class DeliveryHostService
{
    public const string REASON_FAILED = 'host_failed';

    public const string STATE_CLOSED = 'closed';

    public const string STATE_FAILING = 'failing';

    public const string STATE_OPEN = 'open';

    public const string STATE_HALF_OPEN = 'half_open';

    public const string STATE_FAILED = 'failed';

    private const string CACHE_KEY = 'pf:services:delivery:hosts:v2';

    private const string PROBE_KEY = 'pf:services:delivery:probe:';

    private const string REVIVE_KEY = 'pf:services:delivery:revive:';

    private const int REVIVE_COOLDOWN = 86400;

    private const int FAILED = -1;

    private const int CACHE_TTL = 300;

    /*
     * Queue workers are long-lived, so the cached map is also memoized in
     * process for a short window. This keeps a 1,500-inbox fanout from
     * hitting Redis once per inbox.
     */
    private const int MEMO_TTL = 30;

    private const int BASE_BACKOFF = 3600;

    private const int MAX_BACKOFF_EXPONENT = 16;

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
        return self::skipping(self::flagged()[strtolower($domain)] ?? null, self::now());
    }

    public static function isFailed(string $domain): bool
    {
        return (self::flagged()[strtolower($domain)] ?? null) === self::FAILED;
    }

    public static function allows(string $domain): bool
    {
        $domain = strtolower($domain);

        $next = self::flagged()[$domain] ?? null;

        if ($next === null) {
            return true;
        }

        if (self::skipping($next, self::now())) {
            return false;
        }

        return self::acquireProbe($domain);
    }

    /**
     * Domains currently being skipped.
     *
     * @return array<int, string>
     */
    public static function unavailable(): array
    {
        $now = self::now();

        return array_keys(
            array_filter(
                self::flagged(),
                fn (?int $next): bool => self::skipping($next, $now)
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

        $gate = [];

        foreach ($inboxes as $index => $inbox) {
            if (! is_string($inbox) || trim($inbox) === '') {
                continue;
            }

            $domain = self::domain($inbox);

            if ($domain) {
                $gate[$domain] ??= self::allows($domain);

                if (! $gate[$domain]) {
                    continue;
                }
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

        if ($domains === []) {
            return;
        }

        foreach ($domains as $domain) {
            if (self::isFailed($domain)) {
                continue;
            }

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
        self::flush();

        $flagged = self::flagged();

        $domains = array_filter(
            self::normalize($domains),
            fn (string $domain): bool => array_key_exists($domain, $flagged)
        );

        if ($domains === []) {
            return;
        }

        self::clear(array_values($domains));
    }

    public static function recordInbound(mixed $actor): void
    {
        if (is_array($actor)) {
            $actor = $actor['id'] ?? null;
        }

        $domain = is_string($actor) ? self::domain($actor) : null;

        if (! $domain || ! self::isFailed($domain)) {
            return;
        }

        try {
            if (! Cache::add(self::REVIVE_KEY.$domain, 1, self::REVIVE_COOLDOWN)) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        self::revive($domain);
    }

    /**
     * Clear a host's failure state manually (admin / tinker).
     */
    public static function reset(string $domain): void
    {
        $domains = self::normalize([$domain]);

        if ($domains === []) {
            return;
        }

        self::clear(array_values($domains));
    }

    public static function resetFailed(): int
    {
        $domains = Instance::query()
            ->whereNotNull('delivery_failed_at')
            ->pluck('domain')
            ->map(fn (string $domain): string => strtolower($domain))
            ->unique()
            ->values()
            ->all();

        foreach (array_chunk($domains, 500) as $chunk) {
            self::clear($chunk);
        }

        return count($domains);
    }

    public static function state(Instance $instance): string
    {
        if ($instance->delivery_failed_at !== null) {
            return self::STATE_FAILED;
        }

        if ($instance->delivery_timeout && $instance->delivery_next_after !== null) {
            return $instance->delivery_next_after->isFuture()
                ? self::STATE_OPEN
                : self::STATE_HALF_OPEN;
        }

        if ((int) $instance->delivery_failures > 0) {
            return self::STATE_FAILING;
        }

        return self::STATE_CLOSED;
    }

    public static function describe(Instance $instance): array
    {
        $state = self::state($instance);

        return [
            'state' => $state,
            'failures' => (int) $instance->delivery_failures,
            'down_since' => $instance->delivery_down_since,
            'next_attempt_at' => $state === self::STATE_OPEN ? $instance->delivery_next_after : null,
            'failed_at' => $instance->delivery_failed_at,
        ];
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
                    'delivery_down_since' => null,
                    'delivery_failed_at' => null,
                ]);
        } catch (Throwable $e) {
            Log::warning('Unable to clear delivery failures', [
                'domains' => $domains,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($domains as $domain) {
            self::forget(self::PROBE_KEY.$domain);
            self::forget(self::REVIVE_KEY.$domain);
        }

        self::flush();
    }

    private static function revive(string $domain): void
    {
        try {
            $revived = Instance::query()
                ->where('domain', $domain)
                ->whereNotNull('delivery_failed_at')
                ->update([
                    'delivery_failed_at' => null,
                    'delivery_timeout' => true,
                    'delivery_next_after' => Carbon::now(),
                ]);
        } catch (Throwable $e) {
            Log::warning('Unable to revive delivery host', [
                'domain' => $domain,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        self::forget(self::PROBE_KEY.$domain);

        self::flush();

        if ($revived > 0) {
            Log::info('ActivityPub delivery host revived by inbound activity', [
                'domain' => $domain,
            ]);
        }
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

            if ($instance->delivery_failed_at !== null) {
                return;
            }

            $instance->increment('delivery_failures');

            $failures = (int) $instance->delivery_failures;
            $threshold = self::threshold();

            if ($failures < $threshold) {
                return;
            }

            $now = Carbon::now();
            $since = $instance->delivery_down_since ?? $now;
            $updates = ['delivery_timeout' => true];

            if ($instance->delivery_down_since === null) {
                $updates['delivery_down_since'] = $now;
            }

            if ($since->lte($now->copy()->subDays(self::failAfterDays()))) {
                $updates['delivery_failed_at'] = $now;
                $updates['delivery_next_after'] = null;
            } else {
                $updates['delivery_next_after'] = $now->copy()->addSeconds(
                    self::backoff($failures - $threshold)
                );
            }

            $instance->forceFill($updates)->save();

            if (isset($updates['delivery_failed_at'])) {
                Log::warning('ActivityPub delivery host marked failed', [
                    'domain' => $domain,
                    'failures' => $failures,
                    'down_since' => $since->toIso8601String(),
                ]);
            }
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
     * after which delivery may be retried, null while still under the
     * threshold, or FAILED once the host has been marked failed.
     *
     * @return array<string, int|null>
     */
    private static function flagged(): array
    {
        $now = self::now();

        if (
            self::$memo !== null
            && $now >= self::$memoAt
            && ($now - self::$memoAt) < self::MEMO_TTL
        ) {
            return self::$memo;
        }

        self::$memoAt = $now;

        try {
            return self::$memo = Cache::remember(
                self::CACHE_KEY,
                self::CACHE_TTL,
                function () {
                    return Instance::query()
                        ->where(function ($query) {
                            $query->where('delivery_failures', '>', 0)
                                ->orWhereNotNull('delivery_failed_at');
                        })
                        ->get([
                            'domain',
                            'delivery_timeout',
                            'delivery_next_after',
                            'delivery_failed_at',
                        ])
                        ->mapWithKeys(function ($instance) {
                            $next = null;

                            if ($instance->delivery_failed_at) {
                                $next = self::FAILED;
                            } elseif (
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

        self::forget(self::CACHE_KEY);
    }

    private static function now(): int
    {
        return Carbon::now()->getTimestamp();
    }

    private static function skipping(?int $next, int $now): bool
    {
        return $next === self::FAILED || ($next !== null && $next > $now);
    }

    private static function acquireProbe(string $domain): bool
    {
        try {
            return Cache::add(self::PROBE_KEY.$domain, 1, self::probeTtl());
        } catch (Throwable) {
            return true;
        }
    }

    private static function forget(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (Throwable) {
        }
    }

    private static function probeTtl(): int
    {
        return max(1, (int) config('federation.activitypub.delivery.timeout', 30))
            + self::MEMO_TTL
            + 30;
    }

    private static function failAfterDays(): int
    {
        return max(
            1,
            (int) config('federation.activitypub.delivery.fail_after_days', 7)
        );
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
            (int) config('federation.activitypub.delivery.max_backoff', 86400)
        );

        $exponent = min(max($excess, 0), self::MAX_BACKOFF_EXPONENT);

        return (int) min(self::BASE_BACKOFF * (2 ** $exponent), $max);
    }
}
