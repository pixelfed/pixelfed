<?php

namespace App\Console\Commands\Admin;

use App\Models\Instance;
use App\Models\Profile;
use App\Models\Status;
use App\Services\AccountService;
use App\Services\InstanceService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Helper\ProgressBar;

class DeleteRemoteInstance extends Command
{
    protected $signature = 'app:delete-remote-instance
        {domain : The remote instance domain to purge}
        {--block : Ban the instance so it stays defederated after the purge}
        {--dry-run : Report what would be deleted without changing anything}
        {--force : Skip the confirmation prompt}
        {--chunk=2000 : Rows per batch for the SQL deletes}
        {--storage-test : Run a write/read/delete probe against storage before purging}';

    protected $description = 'Delete a remote instance and every trace of it in bulk: all remote accounts on the domain, their posts, comments, interactions, and cached media files';

    /**
     * Status-keyed satellite tables (column => table), cleared per batch of
     * status ids. Morph-typed tables are handled separately below.
     *
     * @var array<string, string>
     */
    private const STATUS_SATELLITES = [
        'bookmarks' => 'status_id',
        'direct_messages' => 'status_id',
        'likes' => 'status_id',
        'media_tags' => 'status_id',
        'mentions' => 'status_id',
        'status_archiveds' => 'status_id',
        'status_edits' => 'status_id',
        'status_hashtags' => 'status_id',
        'status_views' => 'status_id',
    ];

    public function handle(): int
    {
        $domain = $this->normalizeDomain((string) $this->argument('domain'));

        if ($domain === '') {
            $this->error('A domain is required.');

            return self::FAILURE;
        }

        if ($domain === strtolower((string) config('pixelfed.domain.app'))) {
            $this->error('Refusing to delete the local instance domain.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(100, (int) $this->option('chunk'));

        $profileIds = Profile::whereDomain($domain)->whereNotNull('domain')->pluck('id');
        $instance = Instance::whereDomain($domain)->first();

        if ($profileIds->isEmpty() && ! $instance) {
            $this->warn("Nothing found for '{$domain}'.");

            return self::SUCCESS;
        }

        // Scope the big scans by a profiles.domain subquery rather than a literal
        // IN list of thousands of ids, so MySQL can use the indexes.
        $this->line('Counting content (this can take a moment on large instances) ...');

        $statusCount = $profileIds->isEmpty()
            ? 0
            : DB::table('statuses')->whereIn('profile_id', $this->domainProfileSub($domain))->count();

        $mediaFileCount = $profileIds->isEmpty()
            ? 0
            : DB::table('media')
                ->whereIn('profile_id', $this->domainProfileSub($domain))
                ->whereNotNull('media_path')
                ->count();

        $this->table(['Domain', 'Accounts', 'Statuses', 'Media files', 'Instance row'], [[
            $domain,
            number_format($profileIds->count()),
            number_format($statusCount),
            number_format($mediaFileCount),
            $instance ? 'yes' : 'no',
        ]]);

        if ($dryRun) {
            $this->info('Dry run: nothing was deleted.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirmByDomain($domain)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $startedAt = microtime(true);
        $this->newLine();

        // Optional: prove object storage is reachable before touching real files.
        if ($this->option('storage-test') && ! $this->storageSelfTest()) {
            $this->error('Storage self-test failed. Aborting before any deletion.');

            return self::FAILURE;
        }

        $this->info("Purging '{$domain}' ...");

        // Ban first (if requested) so content stops re-federating mid-purge.
        if ($this->option('block')) {
            $this->step(1, 5, 'Banning instance');
            $this->banInstance($domain, $instance);
        } else {
            $this->step(1, 5, 'Skipping ban (use --block to keep + ban the instance)');
        }

        if ($profileIds->isNotEmpty()) {
            $this->step(2, 5, "Deleting {$this->fmt($mediaFileCount)} media file(s) from storage");
            $this->purgeMediaFromStorage($domain, $mediaFileCount);

            $this->step(3, 5, "Deleting {$this->fmt($statusCount)} status(es) and their interactions");
            $this->purgeStatuses($domain, $statusCount, $chunk);

            $this->step(4, 5, "Deleting {$this->fmt($profileIds->count())} account(s)");
            $this->purgeProfiles($profileIds);
        } else {
            $this->step(2, 5, 'No accounts to delete');
        }

        $this->step(5, 5, 'Rebuilding timeline caches');
        $this->flushCaches($profileIds);

        if (! $this->option('block') && $instance) {
            $instance->delete();
            InstanceService::refresh();
            $this->line('  Removed the instance record.');
        }

        $elapsed = $this->humanDuration(microtime(true) - $startedAt);
        $this->newLine();
        $this->info("Purge of '{$domain}' complete in {$elapsed}.");

        return self::SUCCESS;
    }

    /**
     * Delete every cached media file for the domain's media rows from object
     * storage, batched (S3 DeleteObjects: up to 1000 keys/request). The bar is
     * sized by media ROWS and advances per row so it moves steadily; the slow
     * S3 round-trips happen as key batches fill. HLS videos need a per-video
     * directory listing, so they are handled in a clearly-labelled second pass.
     *
     * @param  Collection<int, int>  $profileIds
     */
    private function purgeMediaFromStorage(string $domain, int $total): void
    {
        if ($total === 0) {
            $this->line('  No media files to delete.');

            return;
        }

        $usesCloud = (bool) config_cache('pixelfed.cloud_storage');
        $cloudDisk = $usesCloud ? Storage::disk(config('filesystems.cloud')) : null;
        $localDisk = Storage::disk(config('filesystems.local'));

        // Native S3 client + bucket for true batch deletes (one DeleteObjects
        // request per 1000 keys) instead of Flysystem per-key deletes.
        $s3 = null;
        $bucket = null;
        if ($usesCloud) {
            try {
                $s3 = $cloudDisk->getClient();
                $bucket = config('filesystems.disks.'.config('filesystems.cloud').'.bucket');
            } catch (\Throwable $e) {
                // Fall back to the Flysystem disk delete below.
            }
        }

        $bar = $this->makeBar($total);

        $keys = [];
        $rowsBuffered = 0;
        $deleted = 0;
        $hlsRows = [];

        $deleteBatch = function (array $batch) use ($usesCloud, $s3, $bucket, $cloudDisk, $localDisk): void {
            if ($usesCloud) {
                if ($s3 && $bucket) {
                    $s3->deleteObjects([
                        'Bucket' => $bucket,
                        'Delete' => [
                            'Objects' => array_map(fn ($k) => ['Key' => $k], $batch),
                            'Quiet' => true,
                        ],
                    ]);
                } else {
                    $cloudDisk->delete($batch);
                }
            } else {
                $localDisk->delete($batch);
            }
        };

        $flush = function (bool $force) use (&$keys, &$rowsBuffered, &$deleted, $deleteBatch, $bar): void {
            if (empty($keys) || (! $force && count($keys) < 1000)) {
                return;
            }

            foreach (array_chunk($keys, 1000) as $batch) {
                $deleteBatch($batch);
                $deleted += count($batch);
            }

            $bar->advance($rowsBuffered);
            $bar->setMessage($this->fmt($deleted).' files deleted');

            $keys = [];
            $rowsBuffered = 0;
        };

        DB::table('media')
            ->whereIn('profile_id', $this->domainProfileSub($domain))
            ->whereNotNull('media_path')
            ->orderBy('id')
            ->select(['id', 'media_path', 'thumbnail_path', 'hls_path'])
            ->chunkById(2000, function ($rows) use (&$keys, &$rowsBuffered, &$hlsRows, $flush) {
                foreach ($rows as $row) {
                    if ($row->media_path) {
                        $keys[] = $row->media_path;
                    }
                    if ($row->thumbnail_path) {
                        $keys[] = $row->thumbnail_path;
                    }
                    if ($row->hls_path) {
                        $hlsRows[] = $row->media_path;
                    }
                    $rowsBuffered++;
                }
                $flush(false);
            });

        $flush(true);
        $bar->finish();
        $this->newLine();
        $this->line("  Deleted {$this->fmt($deleted)} base media file(s).");

        // Pass 2: HLS videos. Each needs a per-video directory listing, so this
        // is slower; it is skipped entirely when there are no HLS rows.
        if (! empty($hlsRows)) {
            $this->line('  Removing HLS video segments ('.$this->fmt(count($hlsRows)).' video(s), this is slower) ...');
            $hlsBar = $this->makeBar(count($hlsRows));
            $hlsDeleted = 0;

            foreach ($hlsRows as $mediaPath) {
                $segments = $this->hlsSegmentKeys($mediaPath);
                if (! empty($segments)) {
                    foreach (array_chunk($segments, 1000) as $batch) {
                        $deleteBatch($batch);
                        $hlsDeleted += count($batch);
                    }
                }
                $hlsBar->advance();
            }

            $hlsBar->finish();
            $this->newLine();
            $this->line("  Deleted {$this->fmt($hlsDeleted)} HLS segment file(s).");
        }
    }

    /**
     * List the HLS segment/playlist keys that share a video's base name, from
     * whichever disk holds them. Row-shape tolerant; returns [] on any failure.
     *
     * @return array<int, string>
     */
    private function hlsSegmentKeys(?string $mediaPath): array
    {
        if (! $mediaPath) {
            return [];
        }

        try {
            $parts = explode('/', $mediaPath);
            $filename = array_pop($parts);
            $dir = implode('/', $parts);
            $name = explode('.', $filename)[0];

            $disk = (bool) config_cache('pixelfed.cloud_storage')
                ? Storage::disk(config('filesystems.cloud'))
                : Storage::disk(config('filesystems.local'));

            return collect($disk->files($dir))
                ->filter(fn ($p) => str_starts_with($p, $dir.'/'.$name))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Delete all statuses for the domain's profiles and their satellite rows,
     * batched by status id with a transaction per batch.
     */
    private function purgeStatuses(string $domain, int $total, int $chunk): void
    {
        if ($total === 0) {
            $this->line('  No statuses to delete.');

            return;
        }

        $bar = $this->makeBar($total);
        $done = 0;

        // Tally rows removed per satellite table across all batches so a single
        // breakdown can be printed at the end (per-batch lines would be spammy).
        $tally = [];
        $add = function (string $label, int $n) use (&$tally): void {
            $tally[$label] = ($tally[$label] ?? 0) + $n;
        };

        DB::table('statuses')
            ->whereIn('profile_id', $this->domainProfileSub($domain))
            ->orderBy('id')
            ->select('id')
            ->chunkById($chunk, function ($rows) use (&$done, $bar, $add) {
                $ids = $rows->pluck('id')->all();

                DB::transaction(function () use ($ids, $add) {
                    foreach (self::STATUS_SATELLITES as $table => $column) {
                        $add($table, DB::table($table)->whereIn($column, $ids)->delete());
                    }

                    // Orphan any remaining media rows (files already removed).
                    $add('media (orphaned)', DB::table('media')->whereIn('status_id', $ids)->update(['status_id' => null]));

                    // Morph-typed tables (match both the current and legacy alias).
                    $add('notifications', DB::table('notifications')
                        ->whereIn('item_type', ['App\\Status', Status::class])
                        ->whereIn('item_id', $ids)
                        ->delete());

                    $add('reports', DB::table('reports')
                        ->whereIn('object_type', ['App\\Status', Status::class])
                        ->whereIn('object_id', $ids)
                        ->delete());

                    $add('collection_items', DB::table('collection_items')
                        ->whereIn('object_type', ['App\\Status', Status::class])
                        ->whereIn('object_id', $ids)
                        ->delete());

                    $add('account_interstitials', DB::table('account_interstitials')
                        ->whereIn('item_type', ['App\\Status', Status::class])
                        ->whereIn('item_id', $ids)
                        ->delete());

                    $add('statuses', DB::table('statuses')->whereIn('id', $ids)->delete());
                });

                $done += count($ids);
                $bar->advance(count($ids));
                $bar->setMessage($this->fmt($done).' deleted');
            });

        $bar->finish();
        $this->newLine();

        foreach ($tally as $label => $n) {
            if ($n > 0) {
                $this->line('  - '.$label.': deleted '.$this->fmt($n));
            }
        }

        $this->line("  Deleted {$this->fmt($done)} status(es) and their interactions.");
    }

    /**
     * Delete the domain's profiles and their profile-keyed satellite rows.
     *
     * Prints a per-table breakdown of what will be removed and reports each
     * delete as it runs, so the account phase is never a silent wait even when
     * one heavy account owns hundreds of thousands of rows.
     *
     * @param  Collection<int, int>  $profileIds
     */
    private function purgeProfiles($profileIds): void
    {
        $ids = $profileIds->all();

        // Per-table deletes: [table, how to scope the delete].
        $tables = [
            'followers (as follower/followee)' => ['followers', $this->eitherSide('profile_id', 'following_id', $ids)],
            'follow_requests' => ['follow_requests', $this->eitherSide('follower_id', 'following_id', $ids)],
            'likes' => ['likes', $this->byColumn('profile_id', $ids)],
            'bookmarks' => ['bookmarks', $this->byColumn('profile_id', $ids)],
            'mentions' => ['mentions', $this->byColumn('profile_id', $ids)],
            'media_tags' => ['media_tags', $this->byColumn('profile_id', $ids)],
            'poll_votes' => ['poll_votes', $this->byColumn('profile_id', $ids)],
            'polls' => ['polls', $this->byColumn('profile_id', $ids)],
            'story_views' => ['story_views', $this->byColumn('profile_id', $ids)],
            'stories' => ['stories', $this->byColumn('profile_id', $ids)],
            'direct_messages' => ['direct_messages', $this->eitherSide('from_id', 'to_id', $ids)],
            'conversations' => ['conversations', $this->eitherSide('from_id', 'to_id', $ids)],
            'quote_authorizations' => ['quote_authorizations', $this->byColumn('actor_id', $ids)],
            'reports (by/against)' => ['reports', $this->eitherSide('profile_id', 'reported_profile_id', $ids)],
            'notifications (by/actor)' => ['notifications', $this->eitherSide('profile_id', 'actor_id', $ids)],
            'user_filters (mutes/blocks against)' => ['user_filters', function ($q) use ($ids) {
                $q->where('filterable_type', Profile::class)->whereIn('filterable_id', $ids);
            }],
            'media (orphaned rows)' => ['media', $this->byColumn('profile_id', $ids)],
            'profiles' => ['profiles', $this->byColumn('id', $ids)],
        ];

        // Pre-count so the operator sees what the account phase will remove and
        // which table carries the weight.
        $this->line('  Rows to delete for '.$this->fmt(count($ids)).' account(s):');
        $rows = [];
        $counts = [];
        $grand = 0;
        foreach ($tables as $label => [$table, $scope]) {
            $n = DB::table($table)->where($scope)->count();
            $counts[$label] = $n;
            $grand += $n;
            if ($n > 0) {
                $rows[] = [$label, $this->fmt($n)];
            }
        }
        $rows[] = ['<fg=yellow>TOTAL</>', '<fg=yellow>'.$this->fmt($grand).'</>'];
        $this->table(['Table', 'Rows'], $rows);

        // Delete table by table, announcing each before it runs so a slow table
        // is visible while it works, then reporting the result.
        foreach ($tables as $label => [$table, $scope]) {
            if (($counts[$label] ?? 0) === 0) {
                continue;
            }
            $this->output->write('  - '.$label.': deleting '.$this->fmt($counts[$label]).' ...');
            $t = microtime(true);
            $deleted = DB::table($table)->where($scope)->delete();
            $this->line("\r".'  - '.$label.': deleted '.$this->fmt($deleted).' in '.$this->humanDuration(microtime(true) - $t).'          ');
        }

        $this->line("  Deleted {$this->fmt(count($ids))} account(s).");
    }

    /**
     * A delete scope matching $ids in either of two columns.
     *
     * @param  array<int, int>  $ids
     */
    private function eitherSide(string $a, string $b, array $ids): \Closure
    {
        return function ($q) use ($a, $b, $ids) {
            $q->whereIn($a, $ids)->orWhereIn($b, $ids);
        };
    }

    /**
     * A delete scope matching $ids in a single column.
     *
     * @param  array<int, int>  $ids
     */
    private function byColumn(string $column, array $ids): \Closure
    {
        return function ($q) use ($column, $ids) {
            $q->whereIn($column, $ids);
        };
    }

    /**
     * Clear the shared timeline caches so deleted ids stop being served.
     *
     * @param  Collection<int, int>  $profileIds
     */
    private function flushCaches($profileIds): void
    {
        PublicTimelineService::warmCache(true, (int) config('instance.timeline.local.cache_size'));
        NetworkTimelineService::warmCache(true, (int) config('instance.timeline.network.cache_dropoff'));

        foreach ($profileIds as $pid) {
            AccountService::del($pid);
        }

        $this->line('  Rebuilt local and network timeline caches.');
    }

    private function banInstance(string $domain, ?Instance $instance): void
    {
        $instance = $instance ?: new Instance(['domain' => $domain]);
        $instance->domain = $domain;
        $instance->banned = true;
        $instance->save();
        InstanceService::refresh();
        $this->line("  Banned '{$domain}'.");
    }

    private function confirmByDomain(string $domain): bool
    {
        $this->warn('This permanently deletes every remote account on this domain and all of their content. It cannot be undone.');
        $answer = $this->ask("Type the domain ({$domain}) to confirm");

        return is_string($answer) && strtolower(trim($answer)) === $domain;
    }

    /**
     * Round-trip a probe file through the active disk(s): write, confirm it
     * exists, read it back, delete it, confirm it is gone. Returns false on any
     * failure so the caller can abort before touching real files.
     */
    private function storageSelfTest(): bool
    {
        $this->comment('[0/5] Storage self-test');

        $usesCloud = (bool) config_cache('pixelfed.cloud_storage');
        $probe = 'tmp/delete-remote-instance-selftest-'.uniqid().'.txt';
        $payload = 'selftest-'.now()->timestamp;

        $disks = ['local' => Storage::disk(config('filesystems.local'))];
        if ($usesCloud) {
            $disks['cloud ('.config('filesystems.cloud').')'] = Storage::disk(config('filesystems.cloud'));
        } else {
            $this->line('  cloud_storage disabled; testing local disk only.');
        }

        foreach ($disks as $label => $disk) {
            try {
                $t = microtime(true);
                $disk->put($probe, $payload);

                if (! $disk->exists($probe)) {
                    $this->error("  {$label}: file not found after write.");

                    return false;
                }

                $read = $disk->get($probe);
                if ($read !== $payload) {
                    $this->error("  {$label}: read-back mismatch.");
                    $disk->delete($probe);

                    return false;
                }

                $disk->delete($probe);

                if ($disk->exists($probe)) {
                    $this->error("  {$label}: file still present after delete.");

                    return false;
                }

                $this->line("  {$label}: write/read/delete OK ({$this->humanDuration(microtime(true) - $t)}).");
            } catch (\Throwable $e) {
                $this->error("  {$label}: {$e->getMessage()}");

                return false;
            }
        }

        return true;
    }

    /**
     * A subquery of profile ids for the domain, so large scans filter via an
     * indexed EXISTS/IN subquery instead of a literal list of thousands of ids.
     */
    private function domainProfileSub(string $domain): \Closure
    {
        return function ($query) use ($domain) {
            $query->select('id')->from('profiles')->where('domain', $domain);
        };
    }

    private function step(int $n, int $total, string $label): void
    {
        $this->newLine();
        $this->comment("[{$n}/{$total}] {$label}");
    }

    private function fmt(int $n): string
    {
        return number_format($n);
    }

    /**
     * A progress bar showing count, percentage, elapsed time and ETA.
     */
    private function makeBar(int $max): ProgressBar
    {
        $bar = $this->output->createProgressBar($max);
        $bar->setFormat('  %current%/%max% [%bar%] %percent:3s%%  %elapsed:6s%/%estimated:-6s%  %message%');
        $bar->setMessage('');
        $bar->start();

        return $bar;
    }

    private function humanDuration(float $seconds): string
    {
        $seconds = (int) round($seconds);

        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = intdiv($seconds, 60);
        $rem = $seconds % 60;

        if ($minutes < 60) {
            return $minutes.'m '.$rem.'s';
        }

        $hours = intdiv($minutes, 60);

        return $hours.'h '.($minutes % 60).'m';
    }

    private function normalizeDomain(string $input): string
    {
        $input = trim($input);

        if (str_contains($input, '://')) {
            $input = (string) parse_url($input, PHP_URL_HOST);
        }

        return strtolower(trim($input, " \t\n\r\0\x0B/@"));
    }
}
