<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Profile;

class FixStatusCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:statuscount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fix profile status count';

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
     * @return int
     */
    public function handle()
    {
        Profile::whereNull('domain')
        ->chunk(50, function($profiles) {
            foreach($profiles as $profile) {
                $profile->status_count = $profile->statuses()
                ->getQuery()
                ->whereIn('type', ['photo', 'photo:album', 'video', 'video:album', 'photo:video:album'])
                ->whereNull('in_reply_to_id')
                ->whereNull('reblog_of_id')
                ->count();
                $profile->save();
            }
        });

        return 0;
    }
}
