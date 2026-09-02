<?php

namespace App\Console\Commands\Admin;

use App\Models\CustomEmoji;
use App\Services\CustomEmojiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('admin:resyncemoji {files : Comma-separated emoji filename(s) to resync, e.g. "26109.png,1234.gif"} {--missingonly : Only resync the given files whose local media file is missing} {--dry-run : Report what would happen without downloading or writing} {--force : Skip confirmation prompts}')]
#[Description('Re-download specific remote custom emoji from their origin (image_remote_url) and store them locally.')]
class ResyncEmoji extends Command
{
    public function handle(): int
    {
        if (! (bool) config_cache('federation.custom_emoji.enabled')) {
            $this->error('Custom emoji federation is not enabled (federation.custom_emoji.enabled is false).');

            return self::FAILURE;
        }

        // Parse the comma-separated filename list into distinct basenames.
        $names = collect(explode(',', (string) $this->argument('files')))
            ->map(fn ($n) => basename(trim($n)))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            $this->error('No emoji filenames provided. Example: admin:resyncemoji "26109.png,1234.gif"');

            return self::FAILURE;
        }

        $missingOnly = (bool) $this->option('missingonly');
        $dryRun = (bool) $this->option('dry-run');

        $resynced = 0;
        $skipped = 0;
        $failed = 0;
        $notFound = 0;

        foreach ($names as $name) {
            $mediaPath = 'emoji/'.$name;
            $emoji = CustomEmoji::whereMediaPath($mediaPath)->first();

            if (! $emoji) {
                $this->warn("  not found in DB: {$name} (media_path={$mediaPath})");
                $notFound++;

                continue;
            }

            if (empty($emoji->image_remote_url)) {
                $this->warn("  skip (no origin url, local emoji): {$name}");
                $skipped++;

                continue;
            }

            if ($missingOnly && Storage::exists('public/'.$emoji->media_path)) {
                $this->line("  skip (already present): {$name}");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("  [dry-run] would resync: {$name} <- {$emoji->image_remote_url}");
                $resynced++;

                continue;
            }

            if (! $this->option('force') && ! $this->confirm("Resync {$name} from {$emoji->image_remote_url}?", true)) {
                $this->line("  skipped: {$name}");
                $skipped++;

                continue;
            }

            $result = CustomEmojiService::resync($emoji);
            if ($result === 'resynced') {
                $resynced++;
                $this->info("  resynced: {$name}");
            } elseif ($result === 'skipped') {
                $skipped++;
                $this->warn("  skipped: {$name}");
            } else {
                $failed++;
                $this->error("  failed: {$name}");
            }
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '')."Done. resynced={$resynced} skipped={$skipped} failed={$failed} not_found={$notFound}.");

        return ($failed || $notFound) ? self::FAILURE : self::SUCCESS;
    }
}
