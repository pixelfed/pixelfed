<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Status;
use DB;
use App\Jobs\StatusPipeline\StatusDelete;

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

        if(config('database.default') == 'pgsql') {
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
            ->chunk(50, function($statuses) {
                foreach($statuses as $status) {
                    $this->info("Found duplicate: $status->uri");
                    Status::whereUri($status->uri)
                        ->where('id', '!=', $status->id)
                        ->get()
                        ->map(function($status) {
                            $this->info("Deleting Duplicate ID: $status->id");
                            StatusDelete::dispatch($status);
                        });
                }
            });
    }
}
