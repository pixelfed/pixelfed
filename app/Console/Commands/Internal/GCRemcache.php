<?php

namespace App\Console\Commands\Internal;

use Illuminate\Console\Command;

class GCRemcache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gc:remcache {--hours=24 : Delete remcache files older than this many hours} {--dry-run : Report what would be deleted without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete stale temporary files left in storage/app/remcache/';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');

        if ($hours < 1) {
            $this->error('The --hours value must be at least 1.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $dir = storage_path('app/remcache');

        if (! is_dir($dir)) {
            $this->info('remcache directory does not exist, nothing to do.');

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

            if ($dryRun) {
                $this->line('[dry-run] would delete: '.$file->getFilename());
                $deleted++;
                $reclaimed += $size;

                continue;
            }

            if (@unlink($path)) {
                $deleted++;
                $reclaimed += $size;
            }
        }

        $verb = $dryRun ? 'Would delete' : 'Deleted';
        $this->info(sprintf('%s %d file(s), %s.', $verb, $deleted, $this->humanBytes($reclaimed)));

        return self::SUCCESS;
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
