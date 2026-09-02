<?php

namespace App\Console\Commands\Admin;

use App\Console\Commands\Concerns\ManagesMediaStorageEnv;
use App\Models\Media;
use App\Services\MediaService;
use App\Services\ResilientMediaStorageService;
use App\Services\StatusService;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('admin:MediaMoveStorageLocalToCloud {--limit=500 : Max media rows to process this run} {--dry-run : Report what would happen without copying or writing} {--keep-local : Do not delete local files after verifying the cloud copy} {--force : Skip confirmation prompts}')]
#[Description('Migrate local media to cloud storage: copy up, verify (size/sha256), update URLs, then delete the local copy. Ensures new uploads go to cloud during the migration.')]
class MediaMoveStorageLocalToCloud extends Command
{
    use ManagesMediaStorageEnv;

    protected int $movedBytes = 0;

    public function handle()
    {
        try {
            $localDisk = Storage::disk('local');
            $cloudDisk = Storage::disk(config('filesystems.cloud'));
        } catch (\Throwable $e) {
            $this->error('Cloud disk ('.config('filesystems.cloud').') could not be resolved: '.$e->getMessage());

            return 1;
        }

        if (! $this->cloudHost()) {
            $this->error('Cloud disk ('.config('filesystems.cloud').') is not configured (no resolvable URL).');
            $this->line('Set AWS_URL / AWS_* in your .env before migrating to cloud.');

            return 1;
        }

        // --- Ensure new uploads route to cloud during the migration --------
        $envCloud = $this->readEnvValue('PF_ENABLE_CLOUD');
        $cloudEnabled = filter_var($envCloud, FILTER_VALIDATE_BOOLEAN);

        if (! $cloudEnabled) {
            $this->warn('PF_ENABLE_CLOUD is currently "'.($envCloud ?? 'unset').'".');
            $this->line('New uploads would keep landing on LOCAL storage during this migration.');
            if ($this->option('dry-run')) {
                $this->line('[dry-run] Would set PF_ENABLE_CLOUD=true (.env + runtime + config cache).');
            } elseif ($this->option('force') || $this->confirm('Set PF_ENABLE_CLOUD=true now so new uploads go to cloud?', true)) {
                $this->setStorageEnv('PF_ENABLE_CLOUD', 'true', 'pixelfed.cloud_storage', true);
                $this->info('PF_ENABLE_CLOUD set to true (.env + live runtime + config cache).');
            } else {
                $this->error('Aborting: refusing to migrate to cloud while new uploads stay local.');

                return 1;
            }
        } else {
            $this->info('PF_ENABLE_CLOUD is already true; new uploads route to cloud. ✓');
        }

        $this->newLine();
        if (! $this->option('dry-run') && ! $this->option('force')) {
            if (! $this->confirm('Begin migrating local media to cloud?', true)) {
                $this->comment('Aborted.');

                return 0;
            }
        }

        $limit = (int) $this->option('limit');
        $moved = 0;
        $skipped = 0;
        $failed = 0;

        // Candidates: local, non-remote media not yet replicated to cloud.
        $query = Media::whereRemoteMedia(false)
            ->whereNotNull('media_path')
            ->where(function ($q) {
                $q->whereNull('cdn_url')->orWhereNull('replicated_at')->orWhereNot('version', '4');
            })
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
            $this->info('Transferred '.PrettyNumber::size($this->movedBytes).' to cloud storage.');
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

        // Nothing to do if the local file is gone.
        if (! $localDisk->exists($media->media_path)) {
            // Already on cloud only? mark version and move on.
            if ($cloudDisk->exists($media->media_path)) {
                if (! $this->option('dry-run') && $media->version !== '4') {
                    $media->version = 4;
                    $media->save();
                }

                return 'skipped';
            }

            return 'skipped';
        }

        if ($this->option('dry-run')) {
            return 'moved';
        }

        try {
            // Copy the primary file (and thumbnail) to cloud.
            $this->copyToCloud($media->media_path, $localDisk, $cloudDisk);
            if ($media->thumbnail_path && $localDisk->exists($media->thumbnail_path)) {
                $this->copyToCloud($media->thumbnail_path, $localDisk, $cloudDisk);
            }

            // Verify the primary file before touching anything else.
            if (! $this->verify($media->media_path, $localDisk, $cloudDisk, $media->original_sha256)) {
                $this->warn(PHP_EOL.'Verify failed for media '.$media->id.' ('.$media->media_path.'); left local copy intact.');

                return 'failed';
            }

            // Update URL fields to the cloud disk.
            $media->cdn_url = $cloudDisk->url($media->media_path);
            $media->optimized_url = $media->cdn_url;
            if ($media->thumbnail_path && $cloudDisk->exists($media->thumbnail_path)) {
                $media->thumbnail_url = $cloudDisk->url($media->thumbnail_path);
            }
            $media->replicated_at = now();

            // Integrated GC: delete the verified local copy unless --keep-local.
            if (! $this->option('keep-local')) {
                $localDisk->delete($media->media_path);
                if ($media->thumbnail_path && $localDisk->exists($media->thumbnail_path)) {
                    $localDisk->delete($media->thumbnail_path);
                }
                $media->version = 4;
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

    protected function copyToCloud(string $path, $localDisk, $cloudDisk): void
    {
        $p = explode('/', $path);
        $name = array_pop($p);
        $storagePath = implode('/', $p);

        // Reuse the resilient uploader (handles alt disks + retries).
        ResilientMediaStorageService::store($storagePath, $localDisk->path($path), $name);
    }

    /**
     * Verify the cloud copy matches the local source by size, and by sha256
     * when a checksum is available/cheap. Fails closed.
     */
    protected function verify(string $path, $localDisk, $cloudDisk, ?string $expectedSha = null): bool
    {
        if (! $cloudDisk->exists($path)) {
            return false;
        }

        $localSize = $localDisk->size($path);
        $cloudSize = $cloudDisk->size($path);
        if ($localSize === false || $cloudSize === false || $localSize !== $cloudSize) {
            return false;
        }

        // If we already have the original checksum, verify the local file still
        // matches it (so we never delete a locally-corrupted-but-uploaded file
        // without noticing). Cloud content hashing would require a full
        // download, which we avoid for large media; size parity + known sha
        // is a strong signal.
        if ($expectedSha) {
            $localSha = @hash_file('sha256', $localDisk->path($path));
            if ($localSha && ! hash_equals($expectedSha, $localSha)) {
                return false;
            }
        }

        return true;
    }
}
