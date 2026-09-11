<?php

namespace App\Console\Commands\Deprecated;

use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Status;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StatusDedupe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'status:dedup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes duplicate statuses from before unique uri migration';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        if (config('database.default') == 'pgsql') {
            $this->info('This command is not compatible with Postgres, we are working on a fix.');

            return;
        }
        // Deterministically keep the earliest-fetched status per uri via
        // MIN(id). Selecting a non-aggregated id under GROUP BY is
        // nondeterministic and cannot be influenced by ORDER BY, so the
        // previous query could keep an arbitrary duplicate.
        DB::table('statuses')
            ->selectRaw('MIN(id) as id, uri, count(uri) as occurences')
            ->whereNull('deleted_at')
            ->whereNotNull('uri')
            ->groupBy('uri')
            ->having('occurences', '>', 1)
            ->orderBy('uri')
            ->chunk(50, function ($statuses) {
                foreach ($statuses as $status) {
                    $this->info("Found duplicate: $status->uri");
                    Status::whereUri($status->uri)
                        ->where('id', '!=', $status->id)
                        ->get()
                        ->map(function ($status) {
                            $this->info("Deleting Duplicate ID: $status->id");
                            StatusDelete::dispatch($status);
                        });
                }
            });
    }
}
