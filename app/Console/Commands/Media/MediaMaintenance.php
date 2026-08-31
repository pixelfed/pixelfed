<?php

namespace App\Console\Commands\Media;

use App\Models\Media;
use App\Services\MediaStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MediaMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:maintenance
        {--scope= : The maintenance routine to run. Supported: orphanedMedia}
        {--limit=1000 : Max media rows to process this run}
        {--dry-run : Report what would happen without detaching or deleting}
        {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run media maintenance routines (e.g. clean up orphaned media whose status no longer exists).';

    /**
     * Supported --scope values mapped to their handler methods.
     *
     * @var array<string, string>
     */
    protected array $scopes = [
        'orphanedMedia' => 'handleOrphanedMedia',
    ];

    public function handle(): int
    {
        $scope = $this->option('scope');

        if (! $scope) {
            $this->error('A --scope is required. Supported: '.implode(', ', array_keys($this->scopes)).'.');

            return self::FAILURE;
        }

        if (! isset($this->scopes[$scope])) {
            $this->error('Unknown scope "'.$scope.'". Supported: '.implode(', ', array_keys($this->scopes)).'.');

            return self::FAILURE;
        }

        return $this->{$this->scopes[$scope]}();
    }

    /**
     * Clean up media rows whose status_id references a status that no longer
     * exists (hard-deleted) or is soft-deleted. status_id has no FK/cascade, so
     * these dangling references were never cleared and MediaDeletePipeline's
     * "attached to a status" guard would otherwise refuse to delete them,
     * leaking the files. Detach (status_id = null) first, then dispatch the
     * delete so the guard sees a genuinely orphaned row.
     */
    protected function handleOrphanedMedia(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');

        // Media whose status_id is set but has no matching live (non-trashed)
        // status row.
        $query = Media::whereNotNull('status_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('statuses')
                    ->whereColumn('statuses.id', 'media.status_id')
                    ->whereNull('statuses.deleted_at');
            });

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No orphaned media found. Nothing to do.');

            return self::SUCCESS;
        }

        $media = $query->limit($limit)->get();

        $this->info('Found '.$total.' orphaned media row(s); processing '.$media->count().' this run (limit '.$limit.').');

        if ($dryRun) {
            $this->table(
                ['media_id', 'status_id', 'remote_media', 'media_path'],
                $media->map(fn ($m) => [
                    $m->id,
                    $m->status_id,
                    (bool) $m->remote_media ? 'true' : 'false',
                    $m->media_path,
                ])->all()
            );
            $this->comment('[dry-run] Would detach and delete the '.$media->count().' row(s) above.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Detach and delete '.$media->count().' orphaned media row(s)?', true)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        $processed = 0;
        $bar = $this->output->createProgressBar($media->count());
        $bar->start();

        foreach ($media as $m) {
            // Detach in the DB before dispatching so the delete job's guard
            // sees an orphaned row (the job re-fetches the model on unserialize).
            Media::whereId($m->id)->update(['status_id' => null]);
            $m->status_id = null;
            MediaStorageService::delete($m, true);
            $processed++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Done. Detached and dispatched deletion for '.$processed.' orphaned media row(s).');

        if ($total > $processed) {
            $this->comment('There are '.($total - $processed).' more orphaned row(s). Re-run to continue.');
        }

        return self::SUCCESS;
    }
}
