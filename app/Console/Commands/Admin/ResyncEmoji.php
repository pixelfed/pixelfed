<?php

namespace App\Console\Commands\Admin;

use App\Models\CustomEmoji;
use App\Services\CustomEmojiService;
use Illuminate\Console\Command;

class ResyncEmoji extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:resyncemoji
        {--missingonly : Only resync emoji whose stored media file is missing on the active disk}
        {--limit=0 : Max emoji to process this run (0 = no limit)}
        {--dry-run : Report what would happen without downloading or writing}
        {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-download remote custom emoji media from their origin (image_remote_url) and store on the active disk.';

    public function handle(): int
    {
        if (! (bool) config_cache('federation.custom_emoji.enabled')) {
            $this->error('Custom emoji federation is not enabled (federation.custom_emoji.enabled is false).');

            return self::FAILURE;
        }

        $missingOnly = (bool) $this->option('missingonly');
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));

        // Only remote emoji can be re-fetched (they carry an origin URL).
        $query = CustomEmoji::whereNotNull('image_remote_url')
            ->orderBy('id')
            ->when($limit > 0, fn ($q) => $q->limit($limit));

        $candidates = $query->get();
        $totalCandidates = $candidates->count();

        if ($totalCandidates === 0) {
            $this->info('No remote emoji found to resync.');

            return self::SUCCESS;
        }

        // With --missingonly, keep only those whose file is absent on the
        // active disk (checks cloud when cloud storage is enabled).
        if ($missingOnly) {
            $this->info("Checking {$totalCandidates} remote emoji for missing media...");
            $bar = $this->output->createProgressBar($totalCandidates);
            $bar->start();
            $candidates = $candidates->reject(function (CustomEmoji $emoji) use ($bar) {
                $exists = CustomEmoji::mediaExists($emoji->media_path);
                $bar->advance();

                return $exists; // reject those that already exist
            })->values();
            $bar->finish();
            $this->newLine(2);
        }

        $total = $candidates->count();

        if ($total === 0) {
            $this->info('Nothing to resync'.($missingOnly ? '; all remote emoji media present.' : '.'));

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[dry-run] Would resync {$total} emoji from origin.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Resync {$total} emoji from their origin servers?", true)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        $resynced = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($candidates as $emoji) {
            $result = CustomEmojiService::resync($emoji);
            match ($result) {
                'resynced' => $resynced++,
                'skipped' => $skipped++,
                default => $failed++,
            };
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. resynced={$resynced} skipped={$skipped} failed={$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
