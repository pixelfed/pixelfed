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
        {--dry-run : Report what would happen without copying or writing}
        {--keep-local : Do not delete local files after verifying the cloud copy}
        {--debug : Print exactly what moves, from which local path to which cloud destination}
        {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate local media to cloud storage: copy up, verify (size/sha256), update URLs, then delete the local copy. Ensures new uploads go to cloud during the migration.';

    protected int $movedBytes = 0;

    protected ?ProgressBar $bar = null;

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
            $this->line('Set AWS_URL / AWS_* in your environment before migrating to cloud.');

            return 1;
        }

        // --- Ensure new uploads route to cloud during the migration --------
        // Read the effective, live setting the same way the rest of the app
        // does (config_cache is DB-backed and works with or without a .env
        // file, e.g. in containers that inject config via env vars).
        $cloudEnabled = (bool) config_cache('pixelfed.cloud_storage');

        if (! $cloudEnabled) {
            $this->warn('Cloud storage (pixelfed.cloud_storage) is currently disabled.');
            $this->line('New uploads would keep landing on LOCAL storage during this migration.');
            if ($this->option('dry-run')) {
                $this->line('[dry-run] Would enable cloud storage (runtime + config cache, and .env if writable).');
            } elseif ($this->option('force') || $this->confirm('Enable cloud storage now so new uploads go to cloud?', true)) {
                $this->setStorageEnv('PF_ENABLE_CLOUD', 'true', 'pixelfed.cloud_storage', true);
                $this->info('Cloud storage enabled (live runtime + config cache).');
            } else {
                $this->error('Aborting: refusing to migrate to cloud while new uploads stay local.');

                return 1;
            }
        } else {
            $this->info('Cloud storage is already enabled; new uploads route to cloud. ✓');
        }

        if ($this->option('debug')) {
            $this->newLine();
            $this->line('<comment>[debug] Storage routing</comment>');
            $this->line('  local disk   : '.config('filesystems.disks.local.root'));
            $this->line('  cloud disk   : '.config('filesystems.cloud'));
            $this->line('  cloud driver : '.config('filesystems.disks.'.config('filesystems.cloud').'.driver'));
            $this->line('  cloud bucket : '.config('filesystems.disks.'.config('filesystems.cloud').'.bucket'));
            $this->line('  cloud host   : '.$this->cloudHost());
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

        $this->bar = $this->output->createProgressBar($query->count());
        $this->bar->start();

        foreach ($query->get() as $media) {
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
            $this->debugLine('media '.$media->id.' skipped: media_path is a remote URL ('.$media->media_path.')');

            return 'skipped';
        }

        // Nothing to do if the local file is gone.
        if (! $localDisk->exists($media->media_path)) {
            // Already on cloud only? mark version and move on.
            if ($cloudDisk->exists($media->media_path)) {
                $this->debugLine('media '.$media->id.' skipped: local file missing but already on cloud ('.$media->media_path.')');
                if (! $this->option('dry-run') && $media->version !== '4') {
                    $media->version = 4;
                    $media->save();
                }

                return 'skipped';
            }

            $this->debugLine('media '.$media->id.' skipped: local file missing and not on cloud ('.$media->media_path.')');

            return 'skipped';
        }

        // Basic per-item info (always on): what moves and where it lands.
        $this->basicLine('media '.$media->id.' ('.PrettyNumber::size((int) $media->size).'): '.$media->media_path.' → '.$this->cloudDestination($media->media_path, $cloudDisk));

        if ($this->option('debug')) {
            $this->debugLine('  status_id     : '.($media->status_id ?? 'null'));
            $this->debugLine('  primary from  : '.$localDisk->path($media->media_path));
            $this->debugLine('  primary to    : '.$cloudDisk->url($media->media_path));
            if ($media->thumbnail_path && $localDisk->exists($media->thumbnail_path)) {
                $this->debugLine('  thumbnail from: '.$localDisk->path($media->thumbnail_path));
                $this->debugLine('  thumbnail to  : '.$cloudDisk->url($media->thumbnail_path));
            }
            $this->debugLine('  after copy    : '.($this->option('keep-local') ? 'local kept' : 'local deleted').($this->option('dry-run') ? ' (dry-run: no changes)' : ''));
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
            Log::error('MediaMoveStorageLocalToCloud: failed to migrate media', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
            $this->warn(PHP_EOL.'Error migrating media '.$media->id.': '.$e->getMessage());

            return 'failed';
        }
    }

    /**
     * Write a line that always shows, printed cleanly above an active progress bar.
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
     * Write a line only when --debug is set, cleanly above an active progress bar.
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
