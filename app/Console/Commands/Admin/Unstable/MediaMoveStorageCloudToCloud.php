<?php

namespace App\Console\Commands\Admin\Unstable;

use App\Models\Media;
use App\Services\MediaService;
use App\Services\StatusService;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('unstable:MediaMoveStorageCloudToCloud {--sourceDisk=s3-old : The disk holding the OLD bucket (default: s3-old, reads AWS_OLD_*)} {--limit=500 : Max media rows to process this run} {--dry-run : Report what would happen without copying or writing} {--keep-source : Do not delete objects from the source bucket after verifying the copy} {--force : Skip confirmation prompts}')]
#[Description('Cold-migrate existing media from an old S3 bucket (source disk) to the current cloud bucket, verifying each copy and rewriting media URLs one row at a time.')]
class MediaMoveStorageCloudToCloud extends Command
{
    protected int $movedBytes = 0;

    public function handle()
    {
        $sourceName = (string) $this->option('sourceDisk');
        $destName = config('filesystems.cloud');

        if ($sourceName === $destName) {
            $this->error('Source disk and destination (cloud) disk are the same ('.$sourceName.').');
            $this->line('Point AWS_* at the NEW bucket and keep the OLD bucket creds in the source disk.');

            return 1;
        }

        try {
            $sourceDisk = Storage::disk($sourceName);
        } catch (\Throwable $e) {
            $this->error('Source disk "'.$sourceName.'" could not be resolved: '.$e->getMessage());

            return 1;
        }

        try {
            $destDisk = Storage::disk($destName);
        } catch (\Throwable $e) {
            $this->error('Destination cloud disk "'.$destName.'" could not be resolved: '.$e->getMessage());

            return 1;
        }

        $sourceHost = $this->diskHost($sourceDisk);
        $destHost = $this->diskHost($destDisk);

        if (! $sourceHost) {
            $this->error('Source disk "'.$sourceName.'" is not configured (no resolvable URL). Set AWS_OLD_* in your .env.');

            return 1;
        }
        if (! $destHost) {
            $this->error('Destination cloud disk "'.$destName.'" is not configured (no resolvable URL). Set AWS_* in your .env.');

            return 1;
        }
        if (strcasecmp($sourceHost, $destHost) === 0) {
            $this->error('Source and destination resolve to the same host ('.$sourceHost.'); nothing to migrate.');

            return 1;
        }

        $this->info('Source (old):      '.$sourceName.' -> '.$sourceHost);
        $this->info('Destination (new): '.$destName.' -> '.$destHost);
        $this->newLine();

        if (! $this->option('dry-run') && ! $this->option('force')) {
            if (! $this->confirm('Copy existing media from "'.$sourceHost.'" to "'.$destHost.'" and rewrite URLs?', true)) {
                $this->comment('Aborted.');

                return 0;
            }
        }

        $limit = (int) $this->option('limit');
        $moved = 0;
        $skipped = 0;
        $failed = 0;

        // Candidates: non-remote media whose stored URL still points at the
        // source host (i.e. not yet migrated to the destination).
        $query = Media::whereRemoteMedia(false)
            ->whereNotNull(['media_path', 'cdn_url'])
            ->orderByDesc('id')
            ->limit($limit);

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        foreach ($query->get() as $media) {
            $result = $this->migrateOne($media, $sourceDisk, $destDisk, $sourceHost, $destHost);
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
            $this->info('Transferred '.PrettyNumber::size($this->movedBytes).' to the new bucket.');
        }
        if ($moved > 0 && ! $this->option('dry-run')) {
            $this->comment('Tip: run `php artisan cache:clear` if any stale URLs remain cached elsewhere.');
        }

        return 0;
    }

    /**
     * @return string one of moved|skipped|failed
     */
    protected function migrateOne(Media $media, $sourceDisk, $destDisk, string $sourceHost, string $destHost): string
    {
        if (Str::startsWith((string) $media->media_path, 'http')) {
            return 'skipped';
        }

        // Only act on rows whose URL still references the source host.
        $currentHost = parse_url((string) $media->cdn_url, PHP_URL_HOST);
        if (! $currentHost || strcasecmp($currentHost, $sourceHost) !== 0) {
            return 'skipped';
        }

        // If already present on the destination, just rewrite the URLs.
        $onDest = $destDisk->exists($media->media_path);
        $onSource = $sourceDisk->exists($media->media_path);

        if (! $onDest && ! $onSource) {
            // File missing from both buckets; leave URLs untouched.
            return 'skipped';
        }

        if ($this->option('dry-run')) {
            return 'moved';
        }

        try {
            if (! $onDest) {
                // Copy primary + thumbnail source -> destination.
                $this->copy($media->media_path, $sourceDisk, $destDisk);
                if ($media->thumbnail_path && $sourceDisk->exists($media->thumbnail_path)) {
                    $this->copy($media->thumbnail_path, $sourceDisk, $destDisk);
                }

                if (! $this->verify($media->media_path, $sourceDisk, $destDisk, $media->original_sha256)) {
                    $this->warn(PHP_EOL.'Verify failed for media '.$media->id.' ('.$media->media_path.'); left source intact, URLs unchanged.');

                    return 'failed';
                }
            }

            // Rewrite URLs to the destination bucket.
            $media->cdn_url = $destDisk->url($media->media_path);
            $media->optimized_url = $media->cdn_url;
            if ($media->thumbnail_path && $destDisk->exists($media->thumbnail_path)) {
                $media->thumbnail_url = $destDisk->url($media->thumbnail_path);
            }
            $media->replicated_at = now();
            $media->save();

            $this->movedBytes += (int) $media->size;

            // Integrated GC on the OLD bucket, unless kept.
            if (! $this->option('keep-source') && $onSource) {
                $sourceDisk->delete($media->media_path);
                if ($media->thumbnail_path && $sourceDisk->exists($media->thumbnail_path)) {
                    $sourceDisk->delete($media->thumbnail_path);
                }
            }

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

    protected function copy(string $path, $sourceDisk, $destDisk): void
    {
        $stream = $sourceDisk->readStream($path);
        if ($stream === false || $stream === null) {
            throw new \RuntimeException('Could not open source stream for '.$path);
        }
        $destDisk->writeStream($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * Verify the destination copy matches the source by size, and by sha256
     * against the stored original checksum when available. Fails closed.
     */
    protected function verify(string $path, $sourceDisk, $destDisk, ?string $expectedSha = null): bool
    {
        if (! $destDisk->exists($path)) {
            return false;
        }

        $sourceSize = $sourceDisk->size($path);
        $destSize = $destDisk->size($path);
        if ($sourceSize === false || $destSize === false || $sourceSize !== $destSize) {
            return false;
        }

        if ($expectedSha) {
            // Hash the freshly written destination object to confirm integrity.
            $stream = $destDisk->readStream($path);
            if ($stream === false || $stream === null) {
                return false;
            }
            $ctx = hash_init('sha256');
            hash_update_stream($ctx, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            $destSha = hash_final($ctx);
            if (! hash_equals($expectedSha, $destSha)) {
                return false;
            }
        }

        return true;
    }

    protected function diskHost($disk): ?string
    {
        try {
            $url = $disk->url('probe');
            $host = parse_url($url, PHP_URL_HOST);

            return $host ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
