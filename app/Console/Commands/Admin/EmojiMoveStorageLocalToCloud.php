<?php

namespace App\Console\Commands\Admin;

use App\Console\Commands\Concerns\ManagesMediaStorageEnv;
use App\Models\CustomEmoji;
use App\Util\Lexer\PrettyNumber;
use Aws\CommandPool;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

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
        {--workers=1 : Spawn N parallel worker processes to upload concurrently}
        {--concurrency=0 : Use the async S3 SDK with N concurrent in-flight uploads (0 = disabled, use sync path)}
        {--no-acl : Do not send an ACL header on async uploads (some S3-compatible stores reject it)}
        {--stride=1 : Internal: total worker count for strided sharding}
        {--shard=0 : Internal: this worker index (0-based) for strided sharding}
        {--skip-verify : Do not re-check the cloud copy size after upload (faster)}
        {--skip-cloud-check : Do not HEAD the cloud object first; always upload (faster, idempotent)}
        {--dry-run : Report what would happen without copying or writing}
        {--keep-local : Do not delete local files after verifying the cloud copy}
        {--debug : Print detailed diagnostics}
        {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate local custom emoji to cloud storage: copy up, verify by size, then delete the local copy.';

    protected int $movedBytes = 0;

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
            $cloudDisk = Storage::disk(config('filesystems.cloud'));
        } catch (\Throwable $e) {
            $this->error('Cloud disk ('.config('filesystems.cloud').') could not be resolved: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $this->cloudHost()) {
            $this->error('Cloud disk ('.config('filesystems.cloud').') is not configured (no resolvable URL).');
            $this->line('Set AWS_URL / AWS_* in your .env before migrating to cloud.');

            return self::FAILURE;
        }

        $debug = (bool) $this->option('debug');

        // Diagnostics: what does the environment/config look like?
        if ($debug) {
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
            $this->line('  media_path LIKE http%: '.CustomEmoji::where('media_path', 'like', 'http%')->count());

            $this->line('--- debug: local emoji directory (public/emoji) ---');
            $this->line('  dir exists: '.var_export($localDisk->exists('public/emoji'), true));
            $this->line('  file count: '.count($localDisk->files('public/emoji')));
        }

        $concurrency = max(0, (int) $this->option('concurrency'));

        // Fastest path: async S3 SDK with many concurrent in-flight PutObject
        // requests from a single process. This hides the high per-request
        // latency of the object store far better than sequential uploads or
        // process-level workers.
        if ($concurrency > 0) {
            return $this->runAsync($concurrency, $localDisk);
        }

        $workers = max(1, (int) $this->option('workers'));

        // When asked to parallelise, spawn N child processes that each handle a
        // strided slice of the file list (worker w processes indexes where
        // index % workers === w). This gives real concurrency for the
        // I/O-bound S3 uploads without any extra dependencies.
        if ($workers > 1) {
            return $this->runParallel($workers);
        }

        if (! $this->option('dry-run') && ! $this->option('force')) {
            if (! $this->confirm('Begin migrating local custom emoji to cloud?', true)) {
                $this->comment('Aborted.');

                return self::SUCCESS;
            }
        }

        $limit = (int) $this->option('limit');
        $offset = max(0, (int) $this->option('offset'));
        $stride = max(1, (int) $this->option('stride'));
        $shard = max(0, (int) $this->option('shard'));
        $moved = 0;
        $skipped = 0;
        $failed = 0;
        $startedAt = microtime(true);

        // Disk-driven: enumerate the actual emoji files on the local disk and
        // migrate each one. We intentionally do NOT filter by DB columns here —
        // the file existing locally is the source of truth for "needs moving".
        // Federated emoji have their media stored locally too (with a uri set),
        // so a DB filter on uri would wrongly exclude them.
        $files = $localDisk->exists('public/emoji') ? $localDisk->files('public/emoji') : [];

        if ($offset > 0) {
            $files = array_slice($files, $offset);
        }

        if ($limit > 0) {
            $files = array_slice($files, 0, $limit);
        }

        $showBar = $stride === 1 && ! $debug;
        $bar = null;
        if ($showBar) {
            $bar = $this->output->createProgressBar(count($files));
            $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%%  %rate% up/s\n  %message%");
            $bar->setMessage('');
            $bar->setMessage('0.0', 'rate');
            $bar->start();
        }

        foreach ($files as $i => $localPath) {
            // Strided sharding for parallel workers: only handle our slice.
            if ($stride > 1 && ($i % $stride) !== $shard) {
                continue;
            }

            $filename = basename($localPath);

            // Preserve dotfiles such as a directory .gitignore, and the
            // missing.png placeholder which the frontend hardcodes as a local
            // /storage/emoji/missing.png onerror fallback (must stay local).
            if (str_starts_with($filename, '.') || $filename === 'missing.png') {
                $skipped++;
                $bar?->advance();

                continue;
            }

            // media_path is the local path without the public/ disk prefix.
            $mediaPath = Str::after($localPath, 'public/');
            $result = $this->migrateFile($localPath, $mediaPath, $localDisk, $cloudDisk, $debug);
            match ($result) {
                'moved' => $moved++,
                'skipped' => $skipped++,
                default => $failed++,
            };

            if ($bar) {
                $liveElapsed = max(0.001, microtime(true) - $startedAt);
                $bar->setMessage(sprintf('%.1f', $moved / $liveElapsed), 'rate');
            }
            $bar?->advance();
        }

        $bar?->finish();
        $this->newLine(2);

        if ($moved > 0 && ! $this->option('dry-run')) {
            Cache::forget('pf:custom_emoji');
        }

        $elapsed = max(0.001, microtime(true) - $startedAt);
        $rate = $moved / $elapsed;

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '').'Done. moved='.$moved.' skipped='.$skipped.' failed='.$failed.'.');
        $this->info(sprintf('Elapsed %.1fs, %.1f uploads/sec.', $elapsed, $rate));
        if ($this->movedBytes) {
            $this->info('Transferred '.PrettyNumber::size($this->movedBytes).' to cloud storage.');
        }

        return self::SUCCESS;
    }

    /**
     * Spawn N child worker processes, each handling a strided slice of the
     * files, and wait for them all to finish.
     */
    protected function runParallel(int $workers): int
    {
        if (! $this->option('force') && ! $this->confirm("Begin migrating local custom emoji to cloud using {$workers} parallel workers?", true)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        $php = PHP_BINARY;
        $artisan = base_path('artisan');

        // Forwarded flags each worker should inherit.
        $forward = ['--force'];
        foreach (['dry-run', 'keep-local', 'skip-verify', 'skip-cloud-check', 'debug'] as $flag) {
            if ($this->option($flag)) {
                $forward[] = '--'.$flag;
            }
        }
        if ((int) $this->option('limit') > 0) {
            $forward[] = '--limit='.(int) $this->option('limit');
        }
        if ((int) $this->option('offset') > 0) {
            $forward[] = '--offset='.(int) $this->option('offset');
        }

        $this->info("Launching {$workers} workers...");

        $startedAt = microtime(true);
        $totalMoved = 0;

        $procs = [];
        for ($w = 0; $w < $workers; $w++) {
            $cmd = array_merge(
                [$php, $artisan, 'admin:EmojiMoveStorageLocalToCloud'],
                $forward,
                ['--stride='.$workers, '--shard='.$w]
            );

            $process = new Process($cmd);
            $process->setTimeout(null);
            $process->start();
            $procs[$w] = $process;
        }

        // Stream each worker's output prefixed with its shard id.
        while (array_filter($procs, fn ($p) => $p->isRunning())) {
            foreach ($procs as $w => $process) {
                if ($out = $process->getIncrementalOutput()) {
                    foreach (explode("\n", rtrim($out, "\n")) as $line) {
                        if ($line !== '') {
                            $this->line("[worker {$w}] ".$line);
                            if (preg_match('/moved=(\d+)/', $line, $m)) {
                                $totalMoved += (int) $m[1];
                            }
                        }
                    }
                }
            }
            usleep(200000);
        }

        $failed = 0;
        foreach ($procs as $w => $process) {
            if (! $process->isSuccessful()) {
                $failed++;
                $this->error("Worker {$w} exited with code ".$process->getExitCode());
            }
        }

        if (! $this->option('dry-run')) {
            Cache::forget('pf:custom_emoji');
        }

        $elapsed = max(0.001, microtime(true) - $startedAt);
        $rate = $totalMoved / $elapsed;

        $this->newLine();
        $this->info('All workers finished'.($failed ? " ({$failed} failed)" : '.'));
        $this->info(sprintf('Total moved=%d across %d workers in %.1fs, %.1f uploads/sec.', $totalMoved, $workers, $elapsed, $rate));

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Upload via the async S3 SDK with a fixed number of concurrent in-flight
     * PutObject requests. A successful PutObject response is the confirmation
     * (no separate HEAD verify), and the local copy is deleted on success.
     */
    protected function runAsync(int $concurrency, $localDisk): int
    {
        $conf = config('filesystems.disks.s3');
        $bucket = $conf['bucket'] ?? null;

        if (! $bucket) {
            $this->error('S3 bucket is not configured (filesystems.disks.s3.bucket).');

            return self::FAILURE;
        }

        // Build an S3 client from the same disk config Flysystem uses.
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

        $limit = (int) $this->option('limit');
        $offset = max(0, (int) $this->option('offset'));
        $keepLocal = (bool) $this->option('keep-local');
        $dryRun = (bool) $this->option('dry-run');

        $files = $localDisk->exists('public/emoji') ? $localDisk->files('public/emoji') : [];
        if ($offset > 0) {
            $files = array_slice($files, $offset);
        }
        if ($limit > 0) {
            $files = array_slice($files, 0, $limit);
        }

        // Filter out placeholders/dotfiles up front.
        $files = array_values(array_filter($files, function ($p) {
            $name = basename($p);

            return ! str_starts_with($name, '.') && $name !== 'missing.png';
        }));

        $total = count($files);

        if ($total === 0) {
            $this->info('No emoji files to migrate.');

            return self::SUCCESS;
        }

        if (! $dryRun && ! $this->option('force')) {
            if (! $this->confirm("Upload {$total} emoji to cloud using async S3 (concurrency={$concurrency})?", true)) {
                $this->comment('Aborted.');

                return self::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->info("[dry-run] Would upload {$total} files via async S3 (concurrency={$concurrency}).");

            return self::SUCCESS;
        }

        $moved = 0;
        $failed = 0;
        $startedAt = microtime(true);
        $visibility = ($conf['visibility'] ?? 'public') === 'public' ? 'public-read' : 'private';

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  %rate% up/s');
        $bar->setMessage('0.0', 'rate');
        $bar->start();

        // Lazily yield a PutObject command per file so CommandPool pulls work
        // as concurrency slots free up (keeps memory flat over large runs).
        $sendAcl = ! $this->option('no-acl');
        $commands = function () use ($client, $files, $bucket, $localDisk, $visibility, $sendAcl) {
            foreach ($files as $localPath) {
                $mediaPath = Str::after($localPath, 'public/');
                $params = [
                    'Bucket' => $bucket,
                    'Key' => $mediaPath,
                    'SourceFile' => $localDisk->path($localPath),
                ];
                if ($sendAcl) {
                    $params['ACL'] = $visibility;
                }
                yield $client->getCommand('PutObject', $params);
            }
        };

        $pool = new CommandPool($client, $commands(), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($result, $iterKey) use (&$moved, $files, $localDisk, $keepLocal, $bar, $startedAt) {
                $moved++;
                $localPath = $files[$iterKey] ?? null;
                if ($localPath && ! $keepLocal) {
                    $localDisk->delete($localPath);
                }
                $elapsed = max(0.001, microtime(true) - $startedAt);
                $bar->setMessage(sprintf('%.1f', $moved / $elapsed), 'rate');
                $bar->advance();
            },
            'rejected' => function ($reason, $iterKey) use (&$failed, $files, $bar) {
                $failed++;
                $localPath = $files[$iterKey] ?? '?';
                $msg = $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason;
                $this->warn(PHP_EOL.'Upload failed for '.$localPath.': '.$msg);
                $bar->advance();
            },
        ]);

        // Block until all queued uploads settle.
        $pool->promise()->wait();

        $bar->finish();
        $this->newLine(2);

        if ($moved > 0) {
            Cache::forget('pf:custom_emoji');
        }

        $elapsed = max(0.001, microtime(true) - $startedAt);
        $this->info(sprintf('Done. moved=%d failed=%d in %.1fs, %.1f uploads/sec (concurrency=%d).', $moved, $failed, $elapsed, $moved / $elapsed, $concurrency));

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return string one of moved|skipped|failed
     */
    protected function migrateFile(string $localPath, string $mediaPath, $localDisk, $cloudDisk, bool $debug = false): string
    {
        // The upfront cloud HEAD is an extra S3 round-trip per file. Skip it
        // with --skip-cloud-check for a faster, always-upload (idempotent) run.
        if (! $this->option('skip-cloud-check') && $cloudDisk->exists($mediaPath)) {
            if ($debug) {
                $this->newLine();
                $this->line('  [skip] already on cloud: '.$mediaPath);
            }

            // Present on cloud already; remove the local copy unless asked not to.
            if (! $this->option('dry-run') && ! $this->option('keep-local')) {
                $localDisk->delete($localPath);
            }

            return 'skipped';
        }

        if ($this->option('dry-run')) {
            if ($debug) {
                $this->newLine();
                $this->line('  [dry-run] would move: '.$localPath.' -> '.$mediaPath);
            }

            return 'moved';
        }

        try {
            $size = (int) $localDisk->size($localPath);
            $cloudDisk->put($mediaPath, $localDisk->get($localPath), 'public');

            // Verify is another S3 round-trip; skippable with --skip-verify.
            if (! $this->option('skip-verify') && ! $this->verify($localPath, $mediaPath, $localDisk, $cloudDisk)) {
                $this->warn(PHP_EOL.'Verify failed for '.$mediaPath.'; left local copy intact.');

                return 'failed';
            }

            if (! $this->option('keep-local')) {
                $localDisk->delete($localPath);
            }

            $this->movedBytes += $size;

            return 'moved';
        } catch (\Throwable $e) {
            $this->warn(PHP_EOL.'Error migrating '.$mediaPath.': '.$e->getMessage());

            return 'failed';
        }
    }

    /**
     * Verify the cloud copy matches the local source by size. Fails closed.
     */
    protected function verify(string $localPath, string $cloudPath, $localDisk, $cloudDisk): bool
    {
        if (! $cloudDisk->exists($cloudPath)) {
            return false;
        }

        $localSize = $localDisk->size($localPath);
        $cloudSize = $cloudDisk->size($cloudPath);

        if ($localSize === false || $cloudSize === false || $localSize !== $cloudSize) {
            return false;
        }

        return true;
    }
}
