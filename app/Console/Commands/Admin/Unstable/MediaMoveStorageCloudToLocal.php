<?php

namespace App\Console\Commands\Admin\Unstable;

use App\Console\Commands\Concerns\ManagesMediaStorageEnv;
use App\Models\Media;
use App\Services\MediaService;
use App\Services\StatusService;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaMoveStorageCloudToLocal extends Command
{
    use ManagesMediaStorageEnv;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unstable:MediaMoveStorageCloudToLocal
        {--limit=500 : Max media rows to process this run}
        {--dry-run : Report what would happen without copying or writing}
        {--keep-cloud : Do not delete the cloud copy after verifying the local file}
        {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate cloud media back to local storage: download, verify (size/sha256), update URLs, then optionally delete the cloud copy. Ensures new uploads stay local during the migration.';

    protected int $movedBytes = 0;

    public function handle()
    {
        $localDisk = Storage::disk('local');
        $cloudDisk = Storage::disk(config('filesystems.cloud'));

        // --- Ensure new uploads stay LOCAL during the migration -----------
        // Read the effective, live setting the same way the rest of the app
        // does (config_cache is DB-backed and works with or without a .env
        // file, e.g. in containers that inject config via env vars).
        $cloudEnabled = (bool) config_cache('pixelfed.cloud_storage');

        if ($cloudEnabled) {
            $this->warn('Cloud storage (pixelfed.cloud_storage) is currently enabled.');
            $this->line('New uploads would keep landing on CLOUD storage during this migration.');
            if ($this->option('dry-run')) {
                $this->line('[dry-run] Would disable cloud storage (runtime + config cache, and .env if writable).');
            } elseif ($this->option('force') || $this->confirm('Disable cloud storage now so new uploads stay local?', true)) {
                $this->setStorageEnv('PF_ENABLE_CLOUD', 'false', 'pixelfed.cloud_storage', false);
                $this->info('Cloud storage disabled (live runtime + config cache).');
            } else {
                $this->error('Aborting: refusing to migrate to local while new uploads go to cloud.');

                return 1;
            }
        } else {
            $this->info('Cloud storage is already disabled; new uploads stay local. ✓');
        }

        $this->newLine();
        if (! $this->option('dry-run') && ! $this->option('force')) {
            if (! $this->confirm('Begin migrating cloud media to local?', true)) {
                $this->comment('Aborted.');

                return 0;
            }
        }

        $limit = (int) $this->option('limit');
        $moved = 0;
        $skipped = 0;
        $failed = 0;

        // Candidates: non-remote media that has a cloud copy (cdn_url set).
        $query = Media::whereRemoteMedia(false)
            ->whereNotNull(['media_path', 'cdn_url'])
            ->orderByDesc('id')
            ->limit($limit);

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        foreach ($query->get() as $media) {
            $result = $this->migrateOne($media, $localDisk, $cloudDisk);
            match ($result) {
                'moved' => $moved++,
                'skipped' => $skipped++,
                default => $failed++,
            };
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(($this->option('dry-run') ? '[dry-run] ' : '').'Done. moved='.$moved.' skipped='.$skipped.' failed='.$failed.'.');
        if ($this->movedBytes) {
            $this->info('Transferred '.PrettyNumber::size($this->movedBytes).' back to local storage.');
        }

        return 0;
    }

    /**
     * @return string one of moved|skipped|failed
     */
    protected function migrateOne(Media $media, $localDisk, $cloudDisk): string
    {
        if (Str::startsWith((string) $media->media_path, 'http')) {
            return 'skipped';
        }

        // Must exist on cloud to pull down.
        if (! $cloudDisk->exists($media->media_path)) {
            // Already local-only? just clear the cloud url fields.
            if ($localDisk->exists($media->media_path)) {
                if (! $this->option('dry-run')) {
                    $this->clearCloudFields($media);
                    $media->save();
                }

                return 'skipped';
            }

            return 'failed';
        }

        if ($this->option('dry-run')) {
            return 'moved';
        }

        try {
            $this->copyToLocal($media->media_path, $localDisk, $cloudDisk);
            if ($media->thumbnail_path && $cloudDisk->exists($media->thumbnail_path)) {
                $this->copyToLocal($media->thumbnail_path, $localDisk, $cloudDisk);
            }

            if (! $this->verify($media->media_path, $localDisk, $cloudDisk, $media->original_sha256)) {
                $this->warn(PHP_EOL.'Verify failed for media '.$media->id.' ('.$media->media_path.'); left cloud copy intact.');

                return 'failed';
            }

            // Point URLs back at local storage.
            $this->clearCloudFields($media);

            // Integrated GC: delete the verified cloud copy unless --keep-cloud.
            if (! $this->option('keep-cloud')) {
                $cloudDisk->delete($media->media_path);
                if ($media->thumbnail_path && $cloudDisk->exists($media->thumbnail_path)) {
                    $cloudDisk->delete($media->thumbnail_path);
                }
            }

            $media->save();
            $this->movedBytes += (int) $media->size;

            if ($media->status_id) {
                MediaService::del($media->status_id);
                StatusService::del($media->status_id, false);
            }

            return 'moved';
        } catch (\Throwable $e) {
            $this->warn(PHP_EOL.'Error migrating media '.$media->id.': '.$e->getMessage());

            return 'failed';
        }
    }

    /**
     * Reset a media row to local-served state.
     */
    protected function clearCloudFields(Media $media): void
    {
        $media->cdn_url = null;
        $media->optimized_url = null;
        $media->thumbnail_url = null;
        $media->replicated_at = null;
        // version 4 meant "local deleted, cloud only"; reset so the file is
        // treated as locally present again.
        if ($media->version === '4' || $media->version === 4) {
            $media->version = 3;
        }
    }

    protected function copyToLocal(string $path, $localDisk, $cloudDisk): void
    {
        $stream = $cloudDisk->readStream($path);
        if ($stream === false || $stream === null) {
            throw new \RuntimeException('Could not open cloud stream for '.$path);
        }
        $localDisk->writeStream($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * Verify the local copy matches the cloud source by size, and by sha256
     * against the stored original checksum when available. Fails closed.
     */
    protected function verify(string $path, $localDisk, $cloudDisk, ?string $expectedSha = null): bool
    {
        if (! $localDisk->exists($path)) {
            return false;
        }

        $localSize = $localDisk->size($path);
        $cloudSize = $cloudDisk->size($path);
        if ($localSize === false || $cloudSize === false || $localSize !== $cloudSize) {
            return false;
        }

        if ($expectedSha) {
            $localSha = @hash_file('sha256', $localDisk->path($path));
            if ($localSha && ! hash_equals($expectedSha, $localSha)) {
                return false;
            }
        }

        return true;
    }
}
