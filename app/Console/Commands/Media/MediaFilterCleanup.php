<?php

namespace App\Console\Commands\Media;

use App\Models\Media;
use App\Models\Profile;
use App\Models\Status;
use App\Services\MediaStorageService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MediaFilterCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:filtercleanup
        {--scope= : The maintenance routine to run. Supported: orphanedMedia}
        {--server=both : Which media to target by origin: remote, local, or both}
        {--status= : Filter by referenced status state: soft or hard (deleted)}
        {--profile= : Filter by referenced profile state: live, soft, or hard (deleted)}
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

    /**
     * Supported --server values.
     *
     * @var array<int, string>
     */
    protected array $servers = ['remote', 'local', 'both'];

    /**
     * Supported state filter values per option.
     *
     * Orphaned media never has a live status, so --status only accepts the
     * deleted states. A profile can still be live while its status is deleted,
     * so --profile accepts all three.
     *
     * @var array<string, array<int, string>>
     */
    protected array $stateOptions = [
        'status' => ['soft', 'hard'],
        'profile' => ['live', 'soft', 'hard'],
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

        $server = (string) $this->option('server');
        if (! in_array($server, $this->servers, true)) {
            $this->error('Invalid --server "'.$server.'". Supported: '.implode(', ', $this->servers).'.');

            return self::FAILURE;
        }

        foreach ($this->stateOptions as $stateOpt => $allowed) {
            $value = $this->option($stateOpt);
            if ($value !== null && ! in_array($value, $allowed, true)) {
                $this->error('Invalid --'.$stateOpt.' "'.$value.'". Supported: '.implode(', ', $allowed).'.');

                return self::FAILURE;
            }
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
        $server = (string) $this->option('server');
        $statusFilter = $this->option('status');
        $profileFilter = $this->option('profile');

        // Media whose status_id is set but has no matching live (non-trashed)
        // status row, filtered by origin (remote/local/both) and optionally by
        // the state of the referenced status/profile.
        $query = Media::whereNotNull('status_id')
            ->when($server === 'remote', fn ($q) => $q->where('remote_media', true))
            ->when($server === 'local', fn ($q) => $q->where('remote_media', false))
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('statuses')
                    ->whereColumn('statuses.id', 'media.status_id')
                    ->whereNull('statuses.deleted_at');
            });

        $this->applyStateFilter($query, $statusFilter, 'statuses', 'media.status_id');
        $this->applyStateFilter($query, $profileFilter, 'profiles', 'media.profile_id');

        $filterDesc = '--server='.$server
            .($statusFilter ? ' --status='.$statusFilter : '')
            .($profileFilter ? ' --profile='.$profileFilter : '');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No orphaned media found for ['.$filterDesc.']. Nothing to do.');

            return self::SUCCESS;
        }

        $media = $query->limit($limit)->get();

        // Resolve status + profile state for the rows in this batch (batched,
        // trashed-aware) so we can annotate live vs soft/hard-deleted.
        $stateMap = $this->resolveStates($media);

        $this->info('Found '.$total.' orphaned media row(s) ['.$filterDesc.']; processing '.$media->count().' this run (limit '.$limit.').');

        if ($dryRun) {
            $verbose = $this->output->isVerbose();
            $columns = $verbose
                ? ['media_id', 'status_id', 'status_state', 'profile_id', 'profile_state', 'remote_media', 'mime', 'size', 'created_at', 'media_path']
                : ['media_id', 'status_id', 'status_state', 'profile_id', 'profile_state', 'remote_media', 'media_path'];

            $this->table(
                $columns,
                $media->map(function ($m) use ($stateMap, $verbose) {
                    $row = [
                        'media_id' => $m->id,
                        'status_id' => $m->status_id,
                        'status_state' => $stateMap['status'][$m->status_id] ?? 'unknown',
                        'profile_id' => $m->profile_id ?? 'null',
                        'profile_state' => $m->profile_id ? ($stateMap['profile'][$m->profile_id] ?? 'unknown') : 'n/a',
                        'remote_media' => (bool) $m->remote_media ? 'true' : 'false',
                        'mime' => $m->mime,
                        'size' => $m->size,
                        'created_at' => $m->created_at?->toDateTimeString(),
                        'media_path' => $m->media_path,
                    ];

                    if (! $verbose) {
                        unset($row['mime'], $row['size'], $row['created_at']);
                    }

                    return array_values($row);
                })->all()
            );
            $this->comment('[dry-run] Would detach and delete the '.$media->count().' row(s) above.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Detach and delete '.$media->count().' orphaned media row(s)?', true)) {
            $this->comment('Aborted.');

            return self::SUCCESS;
        }

        $verbose = $this->output->isVerbose();

        $processed = 0;
        $bar = $verbose ? null : $this->output->createProgressBar($media->count());
        $bar?->start();

        foreach ($media as $m) {
            $originalStatusId = $m->status_id;
            // Detach in the DB before dispatching so the delete job's guard
            // sees an orphaned row (the job re-fetches the model on unserialize).
            Media::whereId($m->id)->update(['status_id' => null]);
            $m->status_id = null;
            MediaStorageService::delete($m, true);
            $processed++;

            if ($verbose) {
                $this->line(sprintf(
                    '  [%d/%d] detached + dispatched delete: media_id=%d status_id=%s (%s) profile_id=%s (%s) remote_media=%s mime=%s size=%s path=%s',
                    $processed,
                    $media->count(),
                    $m->id,
                    $originalStatusId ?? 'null',
                    $stateMap['status'][$originalStatusId] ?? 'unknown',
                    $m->profile_id ?? 'null',
                    $m->profile_id ? ($stateMap['profile'][$m->profile_id] ?? 'unknown') : 'n/a',
                    (bool) $m->remote_media ? 'true' : 'false',
                    $m->mime ?? 'null',
                    $m->size ?? 'null',
                    $m->media_path ?? 'null'
                ));
            } else {
                $bar->advance();
            }
        }

        $bar?->finish();
        if (! $verbose) {
            $this->newLine(2);
        }
        $this->info('Done. Detached and dispatched deletion for '.$processed.' orphaned media row(s).');

        if ($total > $processed) {
            $this->comment('There are '.($total - $processed).' more orphaned row(s). Re-run to continue.');
        }

        return self::SUCCESS;
    }

    /**
     * Constrain the query to media whose referenced row (in $table, matched by
     * $mediaColumn = {$table}.id) is in the requested lifecycle state.
     *
     *   live → referenced row exists and is not soft-deleted
     *   soft → referenced row exists with deleted_at set
     *   hard → referenced row does not exist at all
     *
     * Applied at the SQL level so it composes correctly with --limit.
     *
     * @param  Builder<Media>  $query
     */
    protected function applyStateFilter($query, ?string $state, string $table, string $mediaColumn): void
    {
        if ($state === null) {
            return;
        }

        $matchRow = function ($q) use ($table, $mediaColumn) {
            $q->select(DB::raw(1))
                ->from($table)
                ->whereColumn($table.'.id', $mediaColumn);
        };

        if ($state === 'hard') {
            $query->whereNotExists($matchRow);

            return;
        }

        // live or soft: a row must exist, with the appropriate deleted_at state.
        $query->whereExists(function ($q) use ($matchRow, $table, $state) {
            $matchRow($q);
            $state === 'soft'
                ? $q->whereNotNull($table.'.deleted_at')
                : $q->whereNull($table.'.deleted_at');
        });
    }

    /**
     * Resolve the lifecycle state of the statuses and profiles referenced by a
     * batch of media rows.
     *
     * A referenced id can be:
     *   - live         row exists and is not soft-deleted
     *   - soft-deleted row exists with deleted_at set
     *   - hard-deleted row does not exist at all (only meaningful for statuses)
     *
     * @param  Collection<int, Media>  $media
     * @return array{status: array<int, string>, profile: array<int, string>}
     */
    protected function resolveStates($media): array
    {
        $statusIds = $media->pluck('status_id')->filter()->unique()->values();
        $profileIds = $media->pluck('profile_id')->filter()->unique()->values();

        return [
            'status' => $this->stateFor(Status::class, $statusIds),
            'profile' => $this->stateFor(Profile::class, $profileIds),
        ];
    }

    /**
     * Build an id => state map for a soft-deletable model over the given ids.
     *
     * @param  class-string<Model>  $model
     * @param  Collection<int, int|string>  $ids
     * @return array<int, string>
     */
    protected function stateFor(string $model, $ids): array
    {
        if ($ids->isEmpty()) {
            return [];
        }

        $rows = $model::withTrashed()
            ->whereIn('id', $ids->all())
            ->pluck('deleted_at', 'id');

        $map = [];
        foreach ($ids as $id) {
            if (! $rows->has($id)) {
                $map[$id] = 'hard-deleted';
            } elseif ($rows->get($id) !== null) {
                $map[$id] = 'soft-deleted';
            } else {
                $map[$id] = 'live';
            }
        }

        return $map;
    }
}
