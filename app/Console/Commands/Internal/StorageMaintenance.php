<?php

namespace App\Console\Commands\Internal;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class StorageMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:maintenance
        {--hours=24 : Delete remcache files older than this many hours}
        {--only= : Comma-separated tasks to run (remcache,empty-dirs). Default: all}
        {--except= : Comma-separated tasks to skip}
        {--dry-run : Report what would be removed without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reclaim leftover storage: sweep stale remcache temp files and prune empty directories left behind by the media, story, avatar and import flows.';

    /**
     * The empty-directory trees swept by the "empty-dirs" task, keyed by the
     * disk-relative root that is itself preserved. All live on the local disk.
     *
     * @var list<string>
     */
    protected array $emptyDirRoots = [
        'public/m/_v2',
        'public/_esm.t3',
        'public/avatars',
        'story_archives',
        'imports',
    ];

    protected bool $dryRun = false;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        $tasks = $this->resolveTasks();

        if (empty($tasks)) {
            $this->error('No tasks to run. Valid tasks: remcache, empty-dirs.');

            return self::FAILURE;
        }

        if (in_array('remcache', $tasks, true)) {
            $result = $this->sweepRemcache();
            if ($result !== self::SUCCESS) {
                return $result;
            }
        }

        if (in_array('empty-dirs', $tasks, true)) {
            $this->pruneEmptyDirectories();
        }

        return self::SUCCESS;
    }

    /**
     * Determine which tasks to run from --only / --except.
     *
     * @return list<string>
     */
    protected function resolveTasks(): array
    {
        $all = ['remcache', 'empty-dirs'];

        $only = $this->parseList($this->option('only'));
        $except = $this->parseList($this->option('except'));

        $tasks = $only ? array_values(array_intersect($all, $only)) : $all;

        return array_values(array_diff($tasks, $except));
    }

    /**
     * @return list<string>
     */
    protected function parseList(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Delete stale temporary files left in storage/app/remcache/. The media
     * fetchers now clean up their own temp files (try/finally in
     * MediaStorageService); this is the backstop for any writer that misses it.
     */
    protected function sweepRemcache(): int
    {
        $hours = (int) $this->option('hours');

        if ($hours < 1) {
            $this->error('The --hours value must be at least 1.');

            return self::FAILURE;
        }

        $dir = storage_path('app/remcache');

        if (! is_dir($dir)) {
            $this->info('remcache: directory does not exist, nothing to do.');

            return self::SUCCESS;
        }

        $cutoff = now()->subHours($hours)->getTimestamp();
        $deleted = 0;
        $reclaimed = 0;

        foreach (new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            // Preserve dotfiles such as the directory's .gitignore.
            if (str_starts_with($file->getFilename(), '.')) {
                continue;
            }

            if ($file->getMTime() >= $cutoff) {
                continue;
            }

            $size = $file->getSize();
            $path = $file->getPathname();

            if ($this->dryRun) {
                $this->line('[dry-run] remcache: would delete '.$file->getFilename());
                $deleted++;
                $reclaimed += $size;

                continue;
            }

            if (@unlink($path)) {
                $deleted++;
                $reclaimed += $size;
            }
        }

        $verb = $this->dryRun ? 'would delete' : 'deleted';
        $this->info(sprintf('remcache: %s %d file(s), %s.', $verb, $deleted, $this->humanBytes($reclaimed)));

        return self::SUCCESS;
    }

    /**
     * Sweep each managed local tree and remove the random empty directories
     * accumulated over time. This is the safety net: the per-flow jobs clean
     * up their own directories, but older data (and any missed edge case) can
     * still leave empty folders behind, so we reclaim them here.
     */
    protected function pruneEmptyDirectories(): void
    {
        $disk = Storage::disk('local');
        $total = 0;

        foreach ($this->emptyDirRoots as $root) {
            if (! $disk->directoryExists($root)) {
                continue;
            }

            $removed = $this->pruneEmptyDirectoriesUnder($disk, $root);

            $verb = $this->dryRun ? 'would remove' : 'removed';
            $this->info(sprintf('empty-dirs: %s %s %d empty dir(s).', $root, $verb, $removed));
            $total += $removed;
        }

        $verb = $this->dryRun ? 'would remove' : 'removed';
        $this->info(sprintf('empty-dirs: %s %d empty dir(s) in total.', $verb, $total));
    }

    /**
     * Recursively remove every empty directory under $root (bottom-up), leaving
     * $root itself in place. A directory is treated as empty when it holds no
     * files at any depth, so a branch of only-empty subdirectories collapses.
     */
    protected function pruneEmptyDirectoriesUnder(Filesystem $disk, string $root): int
    {
        $directories = $disk->allDirectories($root);

        // Deepest first so children are evaluated before their parents.
        usort($directories, fn ($a, $b) => substr_count($b, '/') <=> substr_count($a, '/'));

        $removed = 0;

        foreach ($directories as $directory) {
            if (count($disk->allFiles($directory)) !== 0) {
                continue;
            }

            if ($this->dryRun) {
                $this->line('[dry-run] empty-dirs: would remove '.$directory);
                $removed++;

                continue;
            }

            $disk->deleteDirectory($directory);
            $removed++;
        }

        return $removed;
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;
        $i = 0;

        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return sprintf('%.2f %s', $value, $units[$i]);
    }
}
