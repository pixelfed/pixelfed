<?php

namespace App\Console\Commands\Admin;

use App\Models\Instance;
use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Services\AccountService;
use App\Services\InstanceService;
use App\Services\Media\MediaHlsService;
use App\Services\NetworkTimelineService;
use App\Services\PublicTimelineService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteRemoteInstance extends Command
{
    protected $signature = 'app:delete-remote-instance
        {domain : The remote instance domain to purge}
        {--block : Ban the instance so it stays defederated after the purge}
        {--dry-run : Report what would be deleted without changing anything}
        {--force : Skip the confirmation prompt}
        {--chunk=2000 : Rows per batch for the SQL deletes}';

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

        $statusCount = $profileIds->isEmpty()
            ? 0
            : Status::whereIn('profile_id', $profileIds)->count();

        $mediaFileCount = $profileIds->isEmpty()
            ? 0
            : DB::table('media')
                ->whereIn('profile_id', $profileIds)
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

        // Ban first (if requested) so content stops re-federating mid-purge.
        if ($this->option('block')) {
            $this->banInstance($domain, $instance);
        }

        if ($profileIds->isNotEmpty()) {
            $this->purgeMediaFromStorage($profileIds);
            $this->purgeStatuses($profileIds, $chunk);
            $this->purgeProfiles($profileIds, $chunk);
        }

        $this->flushCaches($profileIds);

        if (! $this->option('block') && $instance) {
            $instance->delete();
            InstanceService::refresh();
            $this->info('Removed the instance record.');
        }

        $this->info("Purge of '{$domain}' complete.");

        return self::SUCCESS;
    }

    /**
     * Collect every cached media file for the domain's statuses and delete it
     * from object storage in bulk (S3 DeleteObjects: up to 1000 keys/request),
     * falling back to per-file deletes on the local disk.
     *
     * @param  Collection<int, int>  $profileIds
     */
    private function purgeMediaFromStorage($profileIds): void
    {
        $usesCloud = (bool) config_cache('pixelfed.cloud_storage');
        $cloudDisk = $usesCloud ? Storage::disk(config('filesystems.cloud')) : null;
        $localDisk = Storage::disk(config('filesystems.local'));

        $keys = [];
        $deleted = 0;

        $flush = function (bool $force) use (&$keys, &$deleted, $usesCloud, $cloudDisk, $localDisk): void {
            if (empty($keys) || (! $force && count($keys) < 1000)) {
                return;
            }

            foreach (array_chunk($keys, 1000) as $batch) {
                if ($usesCloud && $cloudDisk) {
                    $cloudDisk->delete($batch);
                }
                $localDisk->delete($batch);
                $deleted += count($batch);
            }

            $keys = [];
        };

        DB::table('media')
            ->whereIn('profile_id', $profileIds)
            ->whereNotNull('media_path')
            ->orderBy('id')
            ->select(['id', 'media_path', 'thumbnail_path', 'hls_path'])
            ->chunkById(2000, function ($rows) use (&$keys, $flush) {
                foreach ($rows as $row) {
                    if ($row->media_path) {
                        $keys[] = $row->media_path;
                    }
                    if ($row->thumbnail_path) {
                        $keys[] = $row->thumbnail_path;
                    }
                    if ($row->hls_path) {
                        foreach ($this->hlsFiles($row) as $file) {
                            $keys[] = $file;
                        }
                    }
                }
                $flush(false);
            });

        $flush(true);

        $this->info("Deleted {$deleted} media file(s) from storage.");
    }

    /**
     * Resolve all files for an HLS media row via the model, tolerating any
     * row shape; returns an empty list if it cannot be resolved.
     *
     * @return array<int, string>
     */
    private function hlsFiles(object $row): array
    {
        try {
            $media = Media::find($row->id);

            return $media ? (array) MediaHlsService::allFiles($media) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Delete all statuses for the domain's profiles and their satellite rows,
     * batched by status id with a transaction per batch.
     *
     * @param  Collection<int, int>  $profileIds
     */
    private function purgeStatuses($profileIds, int $chunk): void
    {
        $total = 0;

        Status::whereIn('profile_id', $profileIds)
            ->orderBy('id')
            ->select('id')
            ->chunkById($chunk, function ($rows) use (&$total) {
                $ids = $rows->pluck('id')->all();

                DB::transaction(function () use ($ids) {
                    foreach (self::STATUS_SATELLITES as $table => $column) {
                        DB::table($table)->whereIn($column, $ids)->delete();
                    }

                    // Orphan any remaining media rows (files already removed).
                    DB::table('media')->whereIn('status_id', $ids)->update(['status_id' => null]);

                    // Morph-typed tables (match both the current and legacy alias).
                    DB::table('notifications')
                        ->whereIn('item_type', ['App\\Status', Status::class])
                        ->whereIn('item_id', $ids)
                        ->delete();

                    DB::table('reports')
                        ->whereIn('object_type', ['App\\Status', Status::class])
                        ->whereIn('object_id', $ids)
                        ->delete();

                    DB::table('collection_items')
                        ->whereIn('object_type', ['App\\Status', Status::class])
                        ->whereIn('object_id', $ids)
                        ->delete();

                    DB::table('account_interstitials')
                        ->whereIn('item_type', ['App\\Status', Status::class])
                        ->whereIn('item_id', $ids)
                        ->delete();

                    DB::table('statuses')->whereIn('id', $ids)->delete();
                });

                $total += count($ids);
            });

        $this->info("Deleted {$total} status(es) and their interactions.");
    }

    /**
     * Delete the domain's profiles and their profile-keyed satellite rows.
     *
     * @param  Collection<int, int>  $profileIds
     */
    private function purgeProfiles($profileIds, int $chunk): void
    {
        foreach ($profileIds->chunk($chunk) as $batch) {
            $ids = $batch->values()->all();

            DB::transaction(function () use ($ids) {
                $eitherSide = fn ($a, $b) => function ($q) use ($a, $b, $ids) {
                    $q->whereIn($a, $ids)->orWhereIn($b, $ids);
                };

                DB::table('followers')->where($eitherSide('profile_id', 'following_id'))->delete();
                DB::table('follow_requests')->where($eitherSide('follower_id', 'following_id'))->delete();
                DB::table('likes')->whereIn('profile_id', $ids)->delete();
                DB::table('bookmarks')->whereIn('profile_id', $ids)->delete();
                DB::table('mentions')->whereIn('profile_id', $ids)->delete();
                DB::table('media_tags')->whereIn('profile_id', $ids)->delete();
                DB::table('poll_votes')->whereIn('profile_id', $ids)->delete();
                DB::table('polls')->whereIn('profile_id', $ids)->delete();
                DB::table('story_views')->whereIn('profile_id', $ids)->delete();
                DB::table('stories')->whereIn('profile_id', $ids)->delete();
                DB::table('direct_messages')->where($eitherSide('from_id', 'to_id'))->delete();
                DB::table('conversations')->where($eitherSide('from_id', 'to_id'))->delete();
                DB::table('quote_authorizations')->whereIn('actor_id', $ids)->delete();
                DB::table('reports')->where($eitherSide('profile_id', 'reported_profile_id'))->delete();
                DB::table('notifications')->where($eitherSide('profile_id', 'actor_id'))->delete();

                // Mutes/blocks against these profiles.
                DB::table('user_filters')
                    ->where('filterable_type', Profile::class)
                    ->whereIn('filterable_id', $ids)
                    ->delete();

                // Media files were already removed from storage; drop the rows.
                DB::table('media')->whereIn('profile_id', $ids)->delete();

                DB::table('profiles')->whereIn('id', $ids)->delete();
            });
        }

        $this->info("Deleted {$profileIds->count()} account(s).");
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

        $this->info('Rebuilt local and network timeline caches.');
    }

    private function banInstance(string $domain, ?Instance $instance): void
    {
        $instance = $instance ?: new Instance(['domain' => $domain]);
        $instance->domain = $domain;
        $instance->banned = true;
        $instance->save();
        InstanceService::refresh();
        $this->info("Banned '{$domain}'.");
    }

    private function confirmByDomain(string $domain): bool
    {
        $this->warn('This permanently deletes every remote account on this domain and all of their content. It cannot be undone.');
        $answer = $this->ask("Type the domain ({$domain}) to confirm");

        return is_string($answer) && strtolower(trim($answer)) === $domain;
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
