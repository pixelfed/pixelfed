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
        {--limit=0 : Max emoji rows to process this run (0 = no limit, process all)}
        {--dry-run : Report what would happen without copying or writing}
        {--keep-local : Do not delete local files after verifying the cloud copy}
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
        if (! (bool) config_cache('pixelfed.cloud_storage')) {
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

        // Local (non-federated) emoji have no uri. Remote emoji already point
        // at their origin server and are not stored on our disks.
        $query = CustomEmoji::whereNull('uri')
            ->whereNotNull('media_path')
            ->orderByDesc('id')
            ->when($limit > 0, fn ($q) => $q->limit($limit));

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        foreach ($query->get() as $emoji) {
            $result = $this->migrateOne($emoji, $localDisk, $cloudDisk);
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
    protected function migrateOne(CustomEmoji $emoji, $localDisk, $cloudDisk): string
    {
        $mediaPath = $emoji->media_path;

        if (! $mediaPath || Str::startsWith($mediaPath, 'http')) {
            return 'skipped';
        }

        // Local emoji live under the public/ disk prefix; cloud objects live at
        // the bare media_path.
        $localPath = 'public/'.$mediaPath;

        if (! $localDisk->exists($localPath)) {
            // Already migrated (present on cloud, gone locally) or missing.
            return 'skipped';
        }

        if ($this->option('dry-run')) {
            return 'moved';
        }

        try {
            $size = (int) $localDisk->size($localPath);
            $cloudDisk->put($mediaPath, $localDisk->get($localPath), 'public');

            if (! $this->verify($localPath, $mediaPath, $localDisk, $cloudDisk)) {
                $this->warn(PHP_EOL.'Verify failed for emoji '.$emoji->id.' ('.$mediaPath.'); left local copy intact.');

                return 'failed';
            }

            if (! $this->option('keep-local')) {
                $localDisk->delete($localPath);
            }

            $this->movedBytes += $size;

            Cache::forget('pf:custom_emoji:'.str_replace(':', '', (string) $emoji->shortcode));

            return 'moved';
        } catch (\Throwable $e) {
            $this->warn(PHP_EOL.'Error migrating emoji '.$emoji->id.': '.$e->getMessage());

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
