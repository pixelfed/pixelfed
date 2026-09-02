<?php

namespace App\Console\Commands\Deprecated;

use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\Status;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('status:dedup')]
#[Description('Removes duplicate statuses from before unique uri migration')]
class StatusDedupe extends Command
{
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
        DB::table('statuses')
            ->selectRaw('id, uri, count(uri) as occurences')
            ->whereNull('deleted_at')
            ->whereNotNull('uri')
            ->groupBy('uri')
            ->orderBy('created_at')
            ->having('occurences', '>', 1)
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
