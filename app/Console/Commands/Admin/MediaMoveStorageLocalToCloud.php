<?php

namespace App\Console\Commands\Admin;

use App\Console\Commands\Concerns\ManagesMediaStorageEnv;
use App\Models\Media;
use App\Services\MediaService;
use App\Services\ResilientMediaStorageService;
use App\Services\StatusService;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

class MediaMoveStorageLocalToCloud extends Command
{
    use ManagesMediaStorageEnv;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:MediaMoveStorageLocalToCloud
        {--limit=500 : Max media rows to process this run}
        {--before-id= : Only process media with an ID lower than this value}
        {--dry-run : Report what would happen without copying or writing}
        {--keep-local : Do not delete local files after verifying the cloud copy}
        {--debug : Print exactly what moves, from which local path to which cloud destination}
        {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate local media to cloud storage: copy up, verify, update URLs, then optionally delete the local copy.';

    protected int $movedBytes = 0;

    protected ?ProgressBar $bar = null;

    public function handle(): int
    {
        try {
            $localDisk = Storage::disk('local');
            $cloudDisk = Storage::disk(config('filesystems.cloud'));
        } catch (\Throwable $e) {
            $message = 'Cloud disk ('.config('filesystems.cloud').') could not be resolved: '.$e->getMessage();
            $this->error($message);
            Log::error('MediaMoveStorageLocalToCloud: '.$message, [
                'cloud_disk' => config('filesystems.cloud'),
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return self::FAILURE;
        }

        if (! $this->cloudHost()) {
            $message = 'Cloud disk ('.config('filesystems.cloud').') is not configured (no resolvable URL).';
            $this->error($message);
            $this->line('Set AWS_URL / AWS_* in your environment before migrating to cloud.');
            Log::error('MediaMoveStorageLocalToCloud: '.$message, [
                'cloud_disk' => config('filesystems.cloud'),
                'cloud_driver' => config('filesystems.disks.'.config('filesystems.cloud').'.driver'),
                'aws_url_set' => ! empty(config('filesystems.disks.'.config('filesystems.cloud').'.url')),
            ]);

            return self::FAILURE;
        }

        if (! $this->ensureCloudStorageEnabled()) {
            Log::error('MediaMoveStorageLocalToCloud: aborted because cloud storage is disabled and was not enabled.', [
                'cloud_storage' => (bool) config_cache('pixelfed.cloud_storage'),
                'dry_run' => (bool) $this->option('dry-run'),
                'force' => (bool) $this->option('force'),
            ]);

            return self::FAILURE;
        }

        if ($this->option('debug')) {
            $this->printStorageDebugInfo();
        }

        $this->newLine();

        if (
            ! $this->option('dry-run') &&
            ! $this->option('force') &&
            ! $this->confirm('Begin migrating local media to cloud?', true)
        ) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $beforeId = $this->resolveBeforeId();

        if ($beforeId === false) {
            Log::error('MediaMoveStorageLocalToCloud: invalid --before-id option.', [
                'before_id' => $this->option('before-id'),
            ]);

            return self::FAILURE;
        }

        $query = $this->candidateQuery($beforeId, $limit);

        /*
         * Fetch candidates exactly once.
         *
         * Do NOT call $query->count() here. On very large media tables that can
         * count the entire matching dataset before we immediately execute the
         * SELECT again. We only need the count of this bounded result set.
         */
        try {
            $medias = $query->get();
        } catch (\Throwable $e) {
            $this->error('Failed to fetch media candidates: '.$e->getMessage());
            Log::error('MediaMoveStorageLocalToCloud: failed to fetch media candidates.', [
                'limit' => $limit,
                'before_id' => $beforeId,
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return self::FAILURE;
        }

        if ($medias->isEmpty()) {
            $this->info('No media candidates found.');

            return self::SUCCESS;
        }

        $moved = 0;
        $skipped = 0;
        $failed = 0;

        $this->bar = $this->output->createProgressBar($medias->count());
        $this->bar->start();

        foreach ($medias as $media) {
            $result = $this->migrateOne($media, $localDisk, $cloudDisk);

            match ($result) {
                'moved' => $moved++,
                'skipped' => $skipped++,
                default => $failed++,
            };

            $this->bar->advance();
        }

        $this->bar->finish();
        $this->newLine(2);

        $prefix = $this->option('dry-run') ? '[dry-run] ' : '';

        $this->info(
            $prefix.'Done. moved='.$moved.' skipped='.$skipped.' failed='.$failed.'.'
        );

        if ($failed > 0) {
            Log::error('MediaMoveStorageLocalToCloud: completed with failures.', [
                'moved' => $moved,
                'skipped' => $skipped,
                'failed' => $failed,
                'limit' => $limit,
                'before_id' => $beforeId,
                'dry_run' => (bool) $this->option('dry-run'),
                'keep_local' => (bool) $this->option('keep-local'),
            ]);
        }

        if ($this->movedBytes) {
            $this->info(
                'Transferred '.PrettyNumber::size($this->movedBytes).' to cloud storage.'
            );
        }

        /*
         * The candidates are ordered newest -> oldest, so the final ID is a
         * safe cursor for the next run. This is especially useful when some
         * malformed/unrecoverable rows must remain untouched.
         */
        $lastMedia = $medias->last();

        if ($lastMedia && $medias->count() === $limit) {
            $this->newLine();
            $this->comment('More media may remain.');

            $nextCommand = sprintf(
                'php artisan admin:MediaMoveStorageLocalToCloud --limit=%d --before-id=%s',
                $limit,
                $lastMedia->id
            );

            if ($this->option('keep-local')) {
                $nextCommand .= ' --keep-local';
            }

            if ($this->option('dry-run')) {
                $nextCommand .= ' --dry-run';
            }

            if ($this->option('debug')) {
                $nextCommand .= ' --debug';
            }

            if ($this->option('force')) {
                $nextCommand .= ' --force';
            }

            $this->line('Next batch:');
            $this->line($nextCommand);
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function ensureCloudStorageEnabled(): bool
    {
        $cloudEnabled = (bool) config_cache('pixelfed.cloud_storage');

        if ($cloudEnabled) {
            $this->info('Cloud storage is already enabled; new uploads route to cloud. ✓');

            return true;
        }

        $this->warn('Cloud storage (pixelfed.cloud_storage) is currently disabled.');
        $this->line('New uploads would keep landing on LOCAL storage during this migration.');

        if ($this->option('dry-run')) {
            $this->line(
                '[dry-run] Would enable cloud storage (runtime + config cache, and .env if writable).'
            );

            return true;
        }

        if (
            ! $this->option('force') &&
            ! $this->confirm(
                'Enable cloud storage now so new uploads go to cloud?',
                true
            )
        ) {
            $this->error(
                'Aborting: refusing to migrate to cloud while new uploads stay local.'
            );

            return false;
        }

        $this->setStorageEnv(
            'PF_ENABLE_CLOUD',
            'true',
            'pixelfed.cloud_storage',
            true
        );

        $this->info('Cloud storage enabled (live runtime + config cache).');

        return true;
    }

    protected function printStorageDebugInfo(): void
    {
        $this->newLine();
        $this->line('<comment>[debug] Storage routing</comment>');
        $this->line('  local disk   : '.config('filesystems.disks.local.root'));
        $this->line('  cloud disk   : '.config('filesystems.cloud'));
        $this->line(
            '  cloud driver : '.config(
                'filesystems.disks.'.config('filesystems.cloud').'.driver'
            )
        );
        $this->line(
            '  cloud bucket : '.config(
                'filesystems.disks.'.config('filesystems.cloud').'.bucket'
            )
        );
        $this->line('  cloud host   : '.$this->cloudHost());

        if ($this->option('before-id')) {
            $this->line('  before id    : '.$this->option('before-id'));
        }
    }

    protected function resolveBeforeId(): string|null|false
    {
        $beforeId = $this->option('before-id');

        if ($beforeId === null || $beforeId === '') {
            return null;
        }

        $beforeId = trim((string) $beforeId);

        if (! ctype_digit($beforeId) || $beforeId === '0') {
            $this->error('--before-id must be a positive numeric media ID.');

            return false;
        }

        return $beforeId;
    }

    protected function candidateQuery(?string $beforeId, int $limit)
    {
        return Media::query()
            ->whereRemoteMedia(false)
            ->whereNotNull('media_path')
            ->where(function ($query) {
                $query
                    ->whereNull('cdn_url')
                    ->orWhereNull('replicated_at')
                    ->orWhere('version', '!=', 4);
            })
            ->when(
                $beforeId,
                fn ($query) => $query->where('id', '<', $beforeId)
            )
            ->orderByDesc('id')
            ->limit($limit);
    }

    /**
     * @return string one of moved|skipped|failed
     */
    protected function migrateOne(Media $media, $localDisk, $cloudDisk): string
    {
        $mediaPath = (string) $media->media_path;

        if (Str::startsWith($mediaPath, ['http://', 'https://'])) {
            $this->debugLine(
                'media '.$media->id.' skipped: media_path is a remote URL ('.$mediaPath.')'
            );

            return 'skipped';
        }

        /*
         * If the local file no longer exists, check whether migration had
         * actually succeeded previously but the database state was only
         * partially updated.
         */
        if (! $localDisk->exists($mediaPath)) {
            if ($cloudDisk->exists($mediaPath)) {
                $this->debugLine(
                    'media '.$media->id.' recovered: local file missing but cloud copy exists ('.$mediaPath.')'
                );

                if (! $this->option('dry-run')) {
                    $this->markAsReplicated($media, $cloudDisk, false);
                }

                return 'skipped';
            }

            $this->debugLine(
                'media '.$media->id.' skipped: local file missing and not on cloud ('.$mediaPath.')'
            );

            return 'skipped';
        }

        $this->basicLine(
            'media '.$media->id.
                ' ('.PrettyNumber::size((int) $media->size).'): '.
                $mediaPath.
                ' → '.
                $this->cloudDestination($mediaPath, $cloudDisk)
        );

        if ($this->option('debug')) {
            $this->debugLine(
                '  status_id     : '.($media->status_id ?? 'null')
            );

            $this->debugLine(
                '  primary from  : '.$localDisk->path($mediaPath)
            );

            $this->debugLine(
                '  primary to    : '.$cloudDisk->url($mediaPath)
            );

            if (
                $media->thumbnail_path &&
                $localDisk->exists($media->thumbnail_path)
            ) {
                $this->debugLine(
                    '  thumbnail from: '.$localDisk->path($media->thumbnail_path)
                );

                $this->debugLine(
                    '  thumbnail to  : '.$cloudDisk->url($media->thumbnail_path)
                );
            }

            $this->debugLine(
                '  after copy    : '.
                    ($this->option('keep-local') ? 'local kept' : 'local deleted').
                    ($this->option('dry-run') ? ' (dry-run: no changes)' : '')
            );
        }

        if ($this->option('dry-run')) {
            return 'moved';
        }

        try {
            $this->copyToCloud(
                $mediaPath,
                $localDisk
            );

            if (
                $media->thumbnail_path &&
                $localDisk->exists($media->thumbnail_path)
            ) {
                $this->copyToCloud(
                    $media->thumbnail_path,
                    $localDisk
                );
            }

            $verifyFailure = $this->verify(
                $mediaPath,
                $localDisk,
                $cloudDisk
            );

            if ($verifyFailure !== null) {
                $this->warn(
                    PHP_EOL.
                        'Verify failed for media '.
                        $media->id.
                        ' ('.
                        $mediaPath.
                        '): '.
                        $verifyFailure['reason'].
                        '; left local copy intact.'
                );

                Log::error(
                    'MediaMoveStorageLocalToCloud: verify failed after upload; left local copy intact.',
                    array_merge(
                        [
                            'media_id' => $media->id,
                            'status_id' => $media->status_id,
                            'media_path' => $mediaPath,
                            'size' => (int) $media->size,
                            'cloud_destination' => $this->cloudDestination($mediaPath, $cloudDisk),
                        ],
                        $verifyFailure
                    )
                );

                return 'failed';
            }

            /*
             * Mark the cloud copy as authoritative after verification.
             *
             * version=4 represents the migrated format/state regardless of
             * whether --keep-local was requested. Keeping the local file
             * should not cause this media row to be selected forever.
             */
            $this->markAsReplicated($media, $cloudDisk, true);

            if (! $this->option('keep-local')) {
                $localDisk->delete($mediaPath);

                if (
                    $media->thumbnail_path &&
                    $localDisk->exists($media->thumbnail_path)
                ) {
                    $localDisk->delete($media->thumbnail_path);
                }
            }

            $this->movedBytes += (int) $media->size;

            if ($media->status_id) {
                MediaService::del($media->status_id);
                StatusService::del($media->status_id, false);
            }

            return 'moved';
        } catch (\Throwable $e) {
            Log::error(
                'MediaMoveStorageLocalToCloud: failed to migrate media',
                [
                    'media_id' => $media->id,
                    'status_id' => $media->status_id,
                    'media_path' => $mediaPath,
                    'thumbnail_path' => $media->thumbnail_path,
                    'size' => (int) $media->size,
                    'cloud_destination' => $this->cloudDestination($mediaPath, $cloudDisk),
                    'exception' => get_class($e),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            $this->warn(
                PHP_EOL.
                    'Error migrating media '.
                    $media->id.
                    ': '.
                    $e->getMessage()
            );

            return 'failed';
        }
    }

    /**
     * Normalize all DB state that indicates the media has been replicated.
     *
     * $freshReplication should be true when this command just copied and
     * verified the object. For recovery of an already-existing cloud object,
     * we preserve replicated_at if it already has a value.
     */
    protected function markAsReplicated(
        Media $media,
        $cloudDisk,
        bool $freshReplication
    ): void {
        $media->cdn_url = $cloudDisk->url($media->media_path);
        $media->optimized_url = $media->cdn_url;

        if (
            $media->thumbnail_path &&
            $cloudDisk->exists($media->thumbnail_path)
        ) {
            $media->thumbnail_url = $cloudDisk->url(
                $media->thumbnail_path
            );
        }

        if ($freshReplication || ! $media->replicated_at) {
            $media->replicated_at = now();
        }

        /*
         * version=4 denotes completed cloud migration.
         *
         * This must be set even with --keep-local; otherwise those rows
         * continue matching "version != 4" forever.
         */
        $media->version = 4;

        $media->save();
    }

    /**
     * Write a line that always shows, printed cleanly above an active
     * progress bar.
     */
    protected function basicLine(string $message): void
    {
        if ($this->bar) {
            $this->bar->clear();
            $this->line($message);
            $this->bar->display();

            return;
        }

        $this->line($message);
    }

    /**
     * Write a line only when --debug is set, cleanly above an active
     * progress bar.
     */
    protected function debugLine(string $message): void
    {
        if (! $this->option('debug')) {
            return;
        }

        $this->basicLine('<comment>[debug]</comment> '.$message);
    }

    /**
     * Best-effort human-readable cloud destination for a media path
     * (bucket/key for S3-style disks, otherwise the resolved URL).
     */
    protected function cloudDestination(string $path, $cloudDisk): string
    {
        $cloud = config('filesystems.cloud');
        $bucket = config('filesystems.disks.'.$cloud.'.bucket');

        if ($bucket) {
            return $cloud.'://'.$bucket.'/'.ltrim($path, '/');
        }

        try {
            return $cloudDisk->url($path);
        } catch (\Throwable $e) {
            return $cloud.':'.$path;
        }
    }

    protected function copyToCloud(string $path, $localDisk): void
    {
        $parts = explode('/', $path);
        $name = array_pop($parts);
        $storagePath = implode('/', $parts);

        /*
         * Reuse the resilient uploader, which handles the configured cloud
         * destination, alternate disks, and retries.
         */
        ResilientMediaStorageService::store(
            $storagePath,
            $localDisk->path($path),
            $name
        );
    }

    /**
     * Verify the cloud copy matches the local source by existence and size.
     *
     * Content hashing is intentionally not used here: original_sha256 is the
     * hash of the file as originally uploaded, but the optimize pipeline
     * rewrites the local file in place afterwards, so it would never match the
     * migrated file. Cloud content hashing would require downloading the
     * object. Existence + size parity is the signal that describes the copy we
     * just uploaded.
     *
     * Returns null on success, or a context array describing exactly which
     * check failed (so the caller can log an actionable reason instead of a
     * bare "verify failed").
     */
    protected function verify(
        string $path,
        $localDisk,
        $cloudDisk
    ): ?array {
        if (! $cloudDisk->exists($path)) {
            return [
                'reason' => 'cloud_object_missing',
                'detail' => 'Cloud object does not exist after upload.',
                'path' => $path,
            ];
        }

        $localSize = $localDisk->size($path);
        $cloudSize = $cloudDisk->size($path);

        if (
            $localSize === false ||
            $cloudSize === false ||
            $localSize !== $cloudSize
        ) {
            return [
                'reason' => 'size_mismatch',
                'detail' => 'Local and cloud sizes differ (or a size could not be read).',
                'path' => $path,
                'local_size' => $localSize,
                'cloud_size' => $cloudSize,
            ];
        }

        return null;
    }
}
