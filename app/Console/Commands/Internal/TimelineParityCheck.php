<?php

namespace App\Console\Commands\Internal;

use App\Models\Follower;
use App\Models\Status;
use App\Models\User;
use App\Models\UserDomainBlock;
use App\Services\AdminShadowFilterService;
use App\Services\HomeTimelineService;
use App\Services\InstanceService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use App\Services\SnowflakeService;
use App\Services\UserFilterService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

#[Signature('timeline:parity-check {--feed=all : Which feed to check: home|local|network|all} {--window=400 : How many top entries to compare per feed} {--home-samples=5 : How many home timelines to sample} {--json : Emit a machine-readable JSON report instead of a table}')]
#[Description('READ-ONLY diagnostic. Compares the cached timeline read path (Redis ZSET) against the uncached DB keyset read path for the network, local/public, and a sample of home timelines, reporting missing/extra/out-of-order posts and gaps.')]
class TimelineParityCheck extends Command
{
    // Status types eligible for the timelines; home also includes 'share' (see checkHome).
    private const STATUS_TYPES = ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'];

    public function handle(): int
    {
        $feed = (string) $this->option('feed');
        if (! in_array($feed, ['home', 'local', 'network', 'all'], true)) {
            $this->error("Invalid --feed value '{$feed}'. Use home|local|network|all.");

            return self::FAILURE;
        }

        $window = max(1, (int) $this->option('window'));
        $homeSamples = max(0, (int) $this->option('home-samples'));
        $asJson = (bool) $this->option('json');

        /** @var list<array<string, mixed>> $reports */
        $reports = [];

        if ($feed === 'network' || $feed === 'all') {
            $reports[] = $this->checkNetwork($window);
        }

        if ($feed === 'local' || $feed === 'all') {
            $reports[] = $this->checkLocal($window);
        }

        if (($feed === 'home' || $feed === 'all') && $homeSamples > 0) {
            foreach ($this->sampleHomeProfileIds($homeSamples) as $profileId) {
                $reports[] = $this->checkHome((int) $profileId, $window);
            }
        }

        if ($asJson) {
            $this->line((string) json_encode([
                'generated_at' => now()->toIso8601String(),
                'window' => $window,
                'feeds' => $reports,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->renderReports($reports, $window);

        return self::SUCCESS;
    }

    // Read the top $window ids of a ZSET directly (the service get() helpers clamp to 100).
    private function readZset(string $key, int $window): array
    {
        return $this->normalizeIds(Redis::zrevrange($key, 0, $window - 1));
    }

    // Compare the network cache against the DB, ordered by id (created_at is only a horizon filter).
    private function checkNetwork(int $window): array
    {
        $label = 'network';

        if ((int) NetworkTimelineService::count() === 0) {
            return $this->coldReport($label);
        }

        $cached = $this->readZset(NetworkTimelineService::CACHE_KEY, $window);

        $hideNsfw = config('instance.hide_nsfw_on_public_feeds');
        $filteredDomains = collect(InstanceService::getBannedDomains())
            ->merge(InstanceService::getUnlistedDomains())
            ->unique()
            ->values()
            ->all();

        $rows = Status::whereNotNull('uri')
            ->whereScope('public')
            ->when($hideNsfw, fn ($q) => $q->where('is_nsfw', false))
            ->whereNull('in_reply_to_id')
            ->whereNull('reblog_of_id')
            ->whereIn('type', self::STATUS_TYPES)
            ->where('created_at', '>', now()->subHours((int) config('instance.timeline.network.max_hours_old')))
            ->orderByDesc('id')
            ->limit($window)
            ->get(['id', 'uri']);

        $uncached = $rows
            ->reject(function ($row) use ($filteredDomains) {
                $domain = parse_url((string) $row->uri, PHP_URL_HOST);

                return in_array($domain, $filteredDomains, true);
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $report = $this->compare($label, $cached, $uncached, $window);
        $report['notes'][] = 'Network is ordered by id to match the ZSET score; any divergence here is a real problem.';

        return $report;
    }

    // Compare the local/public cache against the DB keyset (both ordered by id).
    private function checkLocal(int $window): array
    {
        $label = 'local';

        if ((int) PublicTimelineService::count() === 0) {
            return $this->coldReport($label);
        }

        $cached = $this->readZset(PublicTimelineService::CACHE_KEY, $window);

        $hideNsfw = config('instance.hide_nsfw_on_public_feeds');
        $minId = SnowflakeService::byDate(now()->subDays((int) config('instance.timeline.local.max_backfill_days', 90)));

        $rows = Status::where('id', '>', $minId)
            ->whereNull(['uri', 'in_reply_to_id', 'reblog_of_id'])
            ->when($hideNsfw, fn ($q) => $q->where('is_nsfw', false))
            ->whereIn('type', self::STATUS_TYPES)
            ->whereScope('public')
            ->orderByDesc('id')
            ->limit($window)
            ->get(['id', 'profile_id']);

        $uncached = $rows
            ->filter(fn ($row) => AdminShadowFilterService::canAddToPublicFeedByProfileId($row->profile_id))
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return $this->compare($label, $cached, $uncached, $window);
    }

    // Compare one home feed cache against the DB keyset; home includes boosts ('share').
    private function checkHome(int $profileId, int $window): array
    {
        $label = "home:{$profileId}";

        if ((int) HomeTimelineService::count($profileId) === 0) {
            return $this->coldReport($label);
        }

        $cached = $this->readZset(HomeTimelineService::CACHE_KEY.$profileId, $window);

        $following = Follower::whereProfileId($profileId)->pluck('following_id')->push($profileId)->all();

        $filters = UserFilterService::filters($profileId);
        if ($filters && count($filters)) {
            $following = array_values(array_diff($following, $filters));
        }

        $minId = SnowflakeService::byDate(now()->subDays((int) config('instance.timeline.home.max_backfill_days', 180)));
        $domainBlocks = UserDomainBlock::whereProfileId($profileId)->pluck('domain')->all();

        $rows = Status::where('id', '>', $minId)
            ->whereIn('profile_id', $following)
            ->whereNull('in_reply_to_id')
            ->whereIn('type', [...self::STATUS_TYPES, 'share'])
            ->whereIn('visibility', ['public', 'unlisted', 'private'])
            ->orderByDesc('id')
            ->limit($window)
            ->get(['id', 'uri']);

        $uncached = $rows
            ->reject(function ($row) use ($domainBlocks) {
                if (! $domainBlocks || ! $row->uri) {
                    return false;
                }
                $domain = strtolower((string) parse_url((string) $row->uri, PHP_URL_HOST));

                return in_array($domain, $domainBlocks, true);
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return $this->compare($label, $cached, $uncached, $window);
    }

    /**
     * Pick home profile ids that already have a non-empty home ZSET. Scans a
     * bounded set of existing cache keys (read-only, never warms) and falls
     * back to sampling recent active local users whose ZSET is already
     * populated. Cold/empty feeds are never warmed.
     *
     * @return list<int>
     */
    private function sampleHomeProfileIds(int $count): array
    {
        $prefix = (string) config('database.redis.options.prefix', '');
        $pattern = $prefix.HomeTimelineService::CACHE_KEY.'*';
        $found = [];
        $cursor = '0';
        $scanLimit = 5000;
        $scanned = 0;

        do {
            [$cursor, $keys] = Redis::scan($cursor, ['match' => $pattern, 'count' => 500]);
            if ($keys) {
                foreach ($keys as $key) {
                    $scanned++;
                    $bare = $prefix !== '' && str_starts_with($key, $prefix)
                        ? substr($key, strlen($prefix))
                        : $key;
                    $pid = (int) substr($bare, strlen(HomeTimelineService::CACHE_KEY));
                    if ($pid > 0) {
                        $found[$pid] = true;
                    }
                }
            }
        } while ($cursor !== '0' && $cursor !== 0 && $scanned < $scanLimit);

        $ids = array_keys($found);

        if (empty($ids)) {
            // Redis SCAN unavailable (e.g. clustered/managed) or no keys
            // matched. Fall back to recent active local users and keep only
            // those whose home ZSET is already populated, so nothing is warmed.
            $candidates = User::whereNull('deleted_at')
                ->whereNotNull('profile_id')
                ->whereNull('status')
                ->orderByDesc('last_active_at')
                ->limit($count * 20)
                ->pluck('profile_id')
                ->filter(fn ($pid) => (int) HomeTimelineService::count((int) $pid) > 0)
                ->values()
                ->all();
            $ids = $candidates;
        }

        if (empty($ids)) {
            return [];
        }

        shuffle($ids);

        return array_slice(array_map('intval', $ids), 0, $count);
    }

    /**
     * Normalize a raw ZSET read into a list of string ids.
     *
     * @param  mixed  $raw
     * @return list<string>
     */
    private function normalizeIds($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_map(fn ($id) => (string) $id, $raw));
    }

    /**
     * Build a cold/empty report. We never warm an empty feed on prod.
     *
     * @return array<string, mixed>
     */
    private function coldReport(string $label): array
    {
        return [
            'feed' => $label,
            'verdict' => 'COLD/EMPTY',
            'cached_count' => 0,
            'uncached_count' => 0,
            'overlap' => 0,
            'jaccard' => 0.0,
            'missing_in_cache' => [],
            'missing_in_cache_count' => 0,
            'extra_in_cache' => [],
            'extra_in_cache_count' => 0,
            'order_divergence' => null,
            'largest_gap' => 0,
            'notes' => ['Cached ZSET is empty; skipped without warming.'],
        ];
    }

    /**
     * Compare two ordered id lists and produce the parity report.
     *
     * @param  list<string>  $cached
     * @param  list<string>  $uncached
     * @return array<string, mixed>
     */
    private function compare(string $label, array $cached, array $uncached, int $window): array
    {
        $cachedSet = array_flip($cached);
        $uncachedSet = array_flip($uncached);

        $overlapIds = array_values(array_intersect($cached, $uncached));
        $overlap = count($overlapIds);

        $unionCount = count($cachedSet + $uncachedSet);
        $jaccard = $unionCount > 0 ? round($overlap / $unionCount, 4) : 0.0;

        $missingInCache = array_values(array_filter($uncached, fn ($id) => ! isset($cachedSet[$id])));
        $extraInCache = array_values(array_filter($cached, fn ($id) => ! isset($uncachedSet[$id])));

        $orderDivergence = $this->orderDivergence($cached, $uncached);
        $largestGap = $this->largestGap($uncached, $cachedSet);
        $verdict = $this->verdict($jaccard, $orderDivergence, count($uncached));

        return [
            'feed' => $label,
            'verdict' => $verdict,
            'cached_count' => count($cached),
            'uncached_count' => count($uncached),
            'overlap' => $overlap,
            'jaccard' => $jaccard,
            'missing_in_cache' => $missingInCache,
            'missing_in_cache_count' => count($missingInCache),
            'extra_in_cache' => $extraInCache,
            'extra_in_cache_count' => count($extraInCache),
            'order_divergence' => $orderDivergence,
            'largest_gap' => $largestGap,
            'window' => $window,
            'notes' => [],
        ];
    }

    /**
     * Measure ordering divergence using only the ids common to both lists,
     * preserving each list's relative order. Reports the first index where
     * the common-id sequences differ and the number of out-of-order pairs.
     *
     * @param  list<string>  $cached
     * @param  list<string>  $uncached
     * @return array<string, int|null>
     */
    private function orderDivergence(array $cached, array $uncached): array
    {
        $cachedSet = array_flip($cached);
        $uncachedSet = array_flip($uncached);

        $commonCached = array_values(array_filter($cached, fn ($id) => isset($uncachedSet[$id])));
        $commonUncached = array_values(array_filter($uncached, fn ($id) => isset($cachedSet[$id])));

        $firstDivergentIndex = null;
        $limit = min(count($commonCached), count($commonUncached));
        for ($i = 0; $i < $limit; $i++) {
            if ($commonCached[$i] !== $commonUncached[$i]) {
                $firstDivergentIndex = $i;
                break;
            }
        }

        // Count out-of-order pairs: rank each common id by its position in
        // the uncached list, then count inversions in the cached ordering.
        $rank = array_flip($commonUncached);
        $outOfOrderPairs = 0;
        $sequence = array_map(fn ($id) => $rank[$id], $commonCached);
        $n = count($sequence);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                if ($sequence[$i] > $sequence[$j]) {
                    $outOfOrderPairs++;
                }
            }
        }

        return [
            'common_count' => $limit,
            'first_divergent_index' => $firstDivergentIndex,
            'out_of_order_pairs' => $outOfOrderPairs,
        ];
    }

    /**
     * Largest contiguous run of uncached ids missing from the cache. This
     * surfaces the "jumps around" gaps users report.
     *
     * @param  list<string>  $uncached
     * @param  array<string, int>  $cachedSet
     */
    private function largestGap(array $uncached, array $cachedSet): int
    {
        $largest = 0;
        $current = 0;
        foreach ($uncached as $id) {
            if (isset($cachedSet[$id])) {
                $current = 0;

                continue;
            }
            $current++;
            if ($current > $largest) {
                $largest = $current;
            }
        }

        return $largest;
    }

    /**
     * Verdict thresholds: Jaccard < 0.7 is a major divergence; Jaccard < 0.9
     * or any out-of-order pairs is minor drift.
     *
     * @param  array<string, int|null>  $orderDivergence
     */
    private function verdict(float $jaccard, array $orderDivergence, int $uncachedCount): string
    {
        if ($uncachedCount === 0) {
            return 'NO DB RESULTS';
        }
        if ($jaccard < 0.7) {
            return 'MAJOR DIVERGENCE';
        }
        $outOfOrder = (int) ($orderDivergence['out_of_order_pairs'] ?? 0);
        if ($jaccard < 0.9 || $outOfOrder > 0) {
            return 'MINOR DRIFT';
        }

        return 'OK';
    }

    /**
     * Render the human-readable report.
     *
     * @param  list<array<string, mixed>>  $reports
     */
    private function renderReports(array $reports, int $window): void
    {
        $this->info("Timeline parity check (window={$window}, read-only)");
        $this->newLine();

        $rows = [];
        foreach ($reports as $r) {
            $order = $r['order_divergence'];
            $orderSummary = $order === null
                ? '-'
                : sprintf('firstDiff=%s pairs=%d', $order['first_divergent_index'] ?? '—', $order['out_of_order_pairs'] ?? 0);
            $rows[] = [
                $r['feed'],
                $r['verdict'],
                $r['cached_count'],
                $r['uncached_count'],
                $r['overlap'],
                $r['jaccard'],
                $r['missing_in_cache_count'] ?? count($r['missing_in_cache']),
                $r['extra_in_cache_count'] ?? count($r['extra_in_cache']),
                $r['largest_gap'],
                $orderSummary,
            ];
        }

        $this->table(
            ['Feed', 'Verdict', 'Cached', 'Uncached', 'Overlap', 'Jaccard', 'Missing', 'Extra', 'MaxGap', 'Order'],
            $rows
        );

        foreach ($reports as $r) {
            if (! empty($r['notes'])) {
                $this->newLine();
                $this->comment("[{$r['feed']}]");
                foreach ($r['notes'] as $note) {
                    $this->line('  - '.$note);
                }
            }
            $missing = $r['missing_in_cache'] ?? [];
            if (! empty($missing)) {
                $sample = array_slice($missing, 0, 10);
                $this->line('  missing_in_cache (first 10): '.implode(', ', $sample));
            }
        }
    }
}
