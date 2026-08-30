<?php

namespace App\Console\Commands\Admin;

use App\Console\Commands\Concerns\ManagesMediaStorageEnv;
use App\Models\CustomEmoji;
use App\Util\Lexer\PrettyNumber;
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

        if (! $this->option('dry-run') && ! $this->option('force')) {
            if (! $this->confirm('Begin migrating local custom emoji to cloud?', true)) {
                $this->comment('Aborted.');

                return self::SUCCESS;
            }
        }

        $limit = (int) $this->option('limit');
        $moved = 0;
        $skipped = 0;
        $failed = 0;

        // Disk-driven: enumerate the actual emoji files on the local disk and
        // migrate each one. We intentionally do NOT filter by DB columns here —
        // the file existing locally is the source of truth for "needs moving".
        // Federated emoji have their media stored locally too (with a uri set),
        // so a DB filter on uri would wrongly exclude them.
        $files = $localDisk->exists('public/emoji') ? $localDisk->files('public/emoji') : [];

        if ($limit > 0) {
            $files = array_slice($files, 0, $limit);
        }

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $localPath) {
            // Preserve dotfiles such as a directory .gitignore.
            $filename = basename($localPath);
            if (str_starts_with($filename, '.')) {
                $skipped++;
                $bar->advance();

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
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($moved > 0 && ! $this->option('dry-run')) {
            Cache::forget('pf:custom_emoji');
        }

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '').'Done. moved='.$moved.' skipped='.$skipped.' failed='.$failed.'.');
        if ($this->movedBytes) {
            $this->info('Transferred '.PrettyNumber::size($this->movedBytes).' to cloud storage.');
        }

        return self::SUCCESS;
    }

    /**
     * @return string one of moved|skipped|failed
     */
    protected function migrateFile(string $localPath, string $mediaPath, $localDisk, $cloudDisk, bool $debug = false): string
    {
        if ($cloudDisk->exists($mediaPath)) {
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

            if (! $this->verify($localPath, $mediaPath, $localDisk, $cloudDisk)) {
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
