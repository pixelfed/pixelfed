<?php

namespace App\Console\Commands\Admin;

use App\Console\Commands\Concerns\ManagesMediaStorageEnv;
use App\Models\Story;
use App\Services\ResilientMediaStorageService;
use App\Services\StoryService;
use App\Util\Lexer\PrettyNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoryMoveStorageLocalToCloud extends Command
{
    use ManagesMediaStorageEnv;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:StoryMoveStorageLocalToCloud
        {--limit=500 : Max story rows to process this run}
        {--dry-run : Report what would happen without copying or writing}
        {--keep-local : Do not delete local files after verifying the cloud copy}
        {--orphans : Also migrate untracked files under story_archives/ that no story row references}
        {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate local story media (active stories and story_archives) to cloud storage: copy up, verify by size, then delete the local copy.';

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
            if (! $this->confirm('Begin migrating local story media to cloud?', true)) {
                $this->comment('Aborted.');

                return self::SUCCESS;
            }
        }

        $limit = (int) $this->option('limit');
        $moved = 0;
        $skipped = 0;
        $failed = 0;

        // Local stories whose media still lives on the local disk. Covers both
        // active stories and archived ones (story_archives/*). Remote stories
        // are excluded; their media is deleted on expiry, not archived.
        $query = Story::whereLocal(true)
            ->whereNotNull('path')
            ->orderByDesc('id')
            ->limit($limit);

        $bar = $this->output->createProgressBar($query->count());
        $bar->start();

        foreach ($query->get() as $story) {
            $result = $this->migrateOne($story, $localDisk, $cloudDisk);
            match ($result) {
                'moved' => $moved++,
                'skipped' => $skipped++,
                default => $failed++,
            };
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(($this->option('dry-run') ? '[dry-run] ' : '').'Tracked stories: moved='.$moved.' skipped='.$skipped.' failed='.$failed.'.');

        if ($this->option('orphans')) {
            $this->migrateOrphans($localDisk, $cloudDisk, $limit);
        }

        if ($this->movedBytes) {
            $this->info('Transferred '.PrettyNumber::size($this->movedBytes).' to cloud storage.');
        }

        return self::SUCCESS;
    }

    /**
     * Migrate files under story_archives/ that are not referenced by any story
     * row (e.g. left behind after a story was deleted, or legacy files). These
     * are relocated to cloud with the same copy -> verify -> delete flow, so no
     * media is discarded, only moved.
     */
    protected function migrateOrphans($localDisk, $cloudDisk, int $limit): void
    {
        if (! $localDisk->exists('story_archives')) {
            $this->info('No local story_archives directory; nothing to scan for orphans.');

            return;
        }

        $moved = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($localDisk->allFiles('story_archives') as $path) {
            if ($moved + $failed >= $limit) {
                break;
            }

            // Referenced by a story row? Then it was handled above, not an orphan.
            if (Story::wherePath($path)->exists()) {
                $skipped++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line('[dry-run] would migrate orphan: '.$path);
                $moved++;

                continue;
            }

            try {
                $size = (int) $localDisk->size($path);
                $this->copyToCloud($path, $localDisk);

                if (! $this->verify($path, $localDisk, $cloudDisk)) {
                    $this->warn(PHP_EOL.'Verify failed for orphan ('.$path.'); left local copy intact.');
                    $failed++;

                    continue;
                }

                if (! $this->option('keep-local')) {
                    $localDisk->delete($path);
                }

                $this->movedBytes += $size;
                $moved++;
            } catch (\Throwable $e) {
                $this->warn(PHP_EOL.'Error migrating orphan '.$path.': '.$e->getMessage());
                $failed++;
            }
        }

        $this->info(($this->option('dry-run') ? '[dry-run] ' : '').'Orphan files: moved='.$moved.' skipped='.$skipped.' failed='.$failed.'.');
    }

    /**
     * @return string one of moved|skipped|failed
     */
    protected function migrateOne(Story $story, $localDisk, $cloudDisk): string
    {
        $path = $story->path;

        if (! $path || Str::startsWith($path, 'http')) {
            return 'skipped';
        }

        // Nothing to do if the local file is gone (already on cloud only).
        if (! $localDisk->exists($path)) {
            return 'skipped';
        }

        if ($this->option('dry-run')) {
            return 'moved';
        }

        try {
            $this->copyToCloud($path, $localDisk);

            if (! $this->verify($path, $localDisk, $cloudDisk)) {
                $this->warn(PHP_EOL.'Verify failed for story '.$story->id.' ('.$path.'); left local copy intact.');

                return 'failed';
            }

            if (! $this->option('keep-local')) {
                $localDisk->delete($path);
            }

            $this->movedBytes += (int) $story->size;

            StoryService::delById($story->id);
            StoryService::delLatest($story->profile_id);

            return 'moved';
        } catch (\Throwable $e) {
            $this->warn(PHP_EOL.'Error migrating story '.$story->id.': '.$e->getMessage());

            return 'failed';
        }
    }

    protected function copyToCloud(string $path, $localDisk): void
    {
        $p = explode('/', $path);
        $name = array_pop($p);
        $storagePath = implode('/', $p);

        // Reuse the resilient uploader (handles alt disks + retries).
        ResilientMediaStorageService::store($storagePath, $localDisk->path($path), $name);
    }

    /**
     * Verify the cloud copy matches the local source by size. Fails closed.
     */
    protected function verify(string $path, $localDisk, $cloudDisk): bool
    {
        if (! $cloudDisk->exists($path)) {
            return false;
        }

        $localSize = $localDisk->size($path);
        $cloudSize = $cloudDisk->size($path);

        if ($localSize === false || $cloudSize === false || $localSize !== $cloudSize) {
            return false;
        }

        return true;
    }
}
