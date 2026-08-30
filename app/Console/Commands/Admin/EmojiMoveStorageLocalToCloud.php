<?php

namespace App\Console\Commands\Admin;

use App\Console\Commands\Concerns\ManagesMediaStorageEnv;
use App\Models\CustomEmoji;
use Aws\CommandPool;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmojiMoveStorageLocalToCloud extends Command
{
    use ManagesMediaStorageEnv;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:EmojiMoveStorageLocalToCloud
        {--limit=0 : Max files to process this run (0 = no limit, process all)}
        {--offset=0 : Skip this many files before processing (for manual chunking)}
        {--concurrency=100 : Concurrent in-flight S3 uploads via the async SDK (0 = simple synchronous fallback)}
        {--no-acl : Do not send an ACL header on uploads (some S3-compatible stores reject it)}
        {--keep-local : Do not delete local files after a successful upload}
        {--dry-run : Report what would happen without uploading or deleting}
        {--debug : Print detailed diagnostics}
        {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate local custom emoji to cloud storage (concurrent async uploads), then delete the local copy.';

    public function handle(): int
    {
        // Consider cloud enabled if either the live config or the (possibly
        // 12h-cached) config_cache value says so, so a stale cache can't make
        // this silently no-op right after cloud is turned on.
        $cloudEnabled = (bool) config('pixelfed.cloud_storage') || (bool) config_cache('pixelfed.cloud_storage');

        if (! $cloudEnabled) {
            $this->error('Cloud storage is not enabled (pixelfed.cloud_storage is false).');

            return self::FAILURE;
        }

        try {
            $localDisk = Storage::disk('local');
        } catch (\Throwable $e) {
            $this->error('Local disk could not be resolved: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $this->cloudHost()) {
            $this->error('Cloud disk ('.config('filesystems.cloud').') is not configured (no resolvable URL).');
            $this->line('Set AWS_URL / AWS_* in your .env before migrating to cloud.');

            return self::FAILURE;
        }

        if ($this->option('debug')) {
            $this->printDebug($localDisk);
        }

        // Build the list of local emoji files to migrate. Disk-driven: the file
        // existing locally is the source of truth for "needs moving". We do NOT
        // filter by DB columns — federated emoji have their media stored locally
        // too (with a uri set), so a DB filter would wrongly exclude them.
        $files = $this->collectFiles($localDisk);
        $total = count($files);

        if ($total === 0) {
            $this->info('No emoji files to migrate.');

            return self::SUCCESS;
        }

        $concurrency = max(0, (int) $this->option('concurrency'));
        $mode = $concurrency > 0 ? "async S3 (concurrency={$concurrency})" : 'synchronous';

        if ($this->option('dry-run')) {
            $this->info("[dry-run] Would upload {$total} emoji to cloud using {$mode}.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Upload {$total} emoji to cloud using {$mode}?", true)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        return $concurrency > 0
            ? $this->uploadAsync($files, $concurrency, $localDisk)
            : $this->uploadSync($files, $localDisk);
    }

    /**
     * Enumerate local emoji files, applying offset/limit and skipping the
     * missing.png placeholder (hardcoded local /storage/emoji/missing.png
     * onerror fallback) and dotfiles.
     *
     * @return list<string>
     */
    protected function collectFiles($localDisk): array
    {
        $files = $localDisk->exists('public/emoji') ? $localDisk->files('public/emoji') : [];

        $offset = max(0, (int) $this->option('offset'));
        if ($offset > 0) {
            $files = array_slice($files, $offset);
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $files = array_slice($files, 0, $limit);
        }

        return array_values(array_filter($files, function ($p) {
            $name = basename($p);

            return ! str_starts_with($name, '.') && $name !== 'missing.png';
        }));
    }

    /**
     * Upload via the async S3 SDK with a fixed number of concurrent in-flight
     * PutObject requests. A successful PutObject response is the confirmation
     * (no separate HEAD verify); the local copy is deleted on success.
     *
     * @param  list<string>  $files
     */
    protected function uploadAsync(array $files, int $concurrency, $localDisk): int
    {
        $conf = config('filesystems.disks.s3');
        $bucket = $conf['bucket'] ?? null;

        if (! $bucket) {
            $this->error('S3 bucket is not configured (filesystems.disks.s3.bucket).');

            return self::FAILURE;
        }

        $args = [
            'version' => 'latest',
            'region' => $conf['region'] ?? 'us-east-1',
            'credentials' => [
                'key' => $conf['key'] ?? null,
                'secret' => $conf['secret'] ?? null,
            ],
        ];
        if (! empty($conf['endpoint'])) {
            $args['endpoint'] = $conf['endpoint'];
        }
        if (! empty($conf['use_path_style_endpoint'])) {
            $args['use_path_style_endpoint'] = true;
        }

        try {
            $client = new S3Client($args);
        } catch (\Throwable $e) {
            $this->error('Could not build S3 client: '.$e->getMessage());

            return self::FAILURE;
        }

        $keepLocal = (bool) $this->option('keep-local');
        $sendAcl = ! $this->option('no-acl');
        $visibility = ($conf['visibility'] ?? 'public') === 'public' ? 'public-read' : 'private';

        $moved = 0;
        $failed = 0;
        $startedAt = microtime(true);

        $bar = $this->output->createProgressBar(count($files));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %rate% up/s');
        $bar->setMessage('0.0', 'rate');
        $bar->start();

        // Lazily yield a PutObject command per file, KEYED BY THE LOCAL PATH,
        // so CommandPool pulls work as concurrency slots free up (memory stays
        // flat) and the fulfilled/rejected callbacks receive the exact local
        // path as their key. Combined with preserve_iterator_keys=true this
        // avoids any index-based mapping between async results and files.
        //
        // Without this, CommandPool re-indexes promises and the callback key
        // does NOT reliably map back to $files[$key] on out-of-order async
        // completions — which mismaps results to the wrong file and can delete
        // a local copy whose upload actually belonged to (or failed for)
        // another file. That corruption is what left gaps on cloud.
        $commands = function () use ($client, $files, $bucket, $localDisk, $visibility, $sendAcl) {
            foreach ($files as $localPath) {
                $params = [
                    'Bucket' => $bucket,
                    'Key' => Str::after($localPath, 'public/'),
                    'SourceFile' => $localDisk->path($localPath),
                ];
                if ($sendAcl) {
                    $params['ACL'] = $visibility;
                }
                yield $localPath => $client->getCommand('PutObject', $params);
            }
        };

        $pool = new CommandPool($client, $commands(), [
            'concurrency' => $concurrency,
            'preserve_iterator_keys' => true,
            'fulfilled' => function ($result, $localPath) use (&$moved, $localDisk, $keepLocal, $bar, $startedAt) {
                $moved++;
                if ($localPath && ! $keepLocal) {
                    $localDisk->delete($localPath);
                }
                $elapsed = max(0.001, microtime(true) - $startedAt);
                $bar->setMessage(sprintf('%.1f', $moved / $elapsed), 'rate');
                $bar->advance();
            },
            'rejected' => function ($reason, $localPath) use (&$failed, $bar) {
                $failed++;
                $msg = $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason;
                $this->warn(PHP_EOL.'Upload failed for '.($localPath ?: '?').': '.$msg);
                $bar->advance();
            },
        ]);

        $pool->promise()->wait();

        $bar->finish();
        $this->newLine(2);

        return $this->finish($moved, $failed, $startedAt, $concurrency);
    }

    /**
     * Simple synchronous fallback (concurrency=0): upload one file at a time via
     * the cloud disk. Slower, but has no dependency on the S3 SDK internals.
     *
     * @param  list<string>  $files
     */
    protected function uploadSync(array $files, $localDisk): int
    {
        $cloudDisk = Storage::disk(config('filesystems.cloud'));
        $keepLocal = (bool) $this->option('keep-local');

        $moved = 0;
        $failed = 0;
        $startedAt = microtime(true);

        $bar = $this->output->createProgressBar(count($files));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %rate% up/s');
        $bar->setMessage('0.0', 'rate');
        $bar->start();

        foreach ($files as $localPath) {
            $mediaPath = Str::after($localPath, 'public/');

            try {
                $cloudDisk->put($mediaPath, $localDisk->get($localPath), 'public');
                if (! $keepLocal) {
                    $localDisk->delete($localPath);
                }
                $moved++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn(PHP_EOL.'Upload failed for '.$localPath.': '.$e->getMessage());
            }

            $elapsed = max(0.001, microtime(true) - $startedAt);
            $bar->setMessage(sprintf('%.1f', $moved / $elapsed), 'rate');
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        return $this->finish($moved, $failed, $startedAt, 0);
    }

    /**
     * Bust the emoji cache and print the run summary.
     */
    protected function finish(int $moved, int $failed, float $startedAt, int $concurrency): int
    {
        if ($moved > 0) {
            Cache::forget('pf:custom_emoji');
        }

        $elapsed = max(0.001, microtime(true) - $startedAt);
        $this->info(sprintf(
            'Done. moved=%d failed=%d in %.1fs, %.1f uploads/sec%s.',
            $moved,
            $failed,
            $elapsed,
            $moved / $elapsed,
            $concurrency > 0 ? " (concurrency={$concurrency})" : ''
        ));

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    protected function printDebug($localDisk): void
    {
        $this->line('--- debug: config ---');
        $this->line('  config(pixelfed.cloud_storage):       '.var_export(config('pixelfed.cloud_storage'), true));
        $this->line('  config_cache(pixelfed.cloud_storage): '.var_export(config_cache('pixelfed.cloud_storage'), true));
        $this->line('  filesystems.cloud:                    '.config('filesystems.cloud'));
        $this->line('  cloud host:                           '.($this->cloudHost() ?? 'null'));
        $this->line('  local disk root:                      '.$localDisk->path(''));

        $this->line('--- debug: custom_emoji table ---');
        $this->line('  total rows:            '.CustomEmoji::count());
        $this->line('  uri IS NULL:           '.CustomEmoji::whereNull('uri')->count());
        $this->line('  uri NOT NULL:          '.CustomEmoji::whereNotNull('uri')->count());
        $this->line('  media_path NOT NULL:   '.CustomEmoji::whereNotNull('media_path')->count());

        $this->line('--- debug: local emoji directory (public/emoji) ---');
        $this->line('  dir exists: '.var_export($localDisk->exists('public/emoji'), true));
        $this->line('  file count: '.count($localDisk->files('public/emoji')));
    }
}
