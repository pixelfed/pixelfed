<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use App\{
    Hashtag,
    Status,
    StatusHashtag
};

class FixHashtags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:hashtags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Hashtags';

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

        $this->info('       ____  _           ______         __  ');
        $this->info('      / __ \(_)  _____  / / __/__  ____/ /  ');
        $this->info('     / /_/ / / |/_/ _ \/ / /_/ _ \/ __  /   ');
        $this->info('    / ____/ />  </  __/ / __/  __/ /_/ /    ');
        $this->info('   /_/   /_/_/|_|\___/_/_/  \___/\__,_/     ');
        $this->info(' ');
        $this->info(' ');
        $this->info('Pixelfed version: ' . config('pixelfed.version'));
        $this->info(' ');
        $this->info('Running Fix Hashtags command');
        $this->info(' ');

        $missingCount = StatusHashtag::doesntHave('profile')->doesntHave('status')->count();
        if($missingCount > 0) {
            $this->info("Found {$missingCount} orphaned StatusHashtag records to delete ...");
            $this->info(' ');
            $bar = $this->output->createProgressBar($missingCount);
            $bar->start();
            foreach(StatusHashtag::doesntHave('profile')->doesntHave('status')->get() as $tag) {
                $tag->delete();
                $bar->advance();
            }
            $bar->finish();
            $this->info(' ');
        } else {
            $this->info(' ');
            $this->info('Found no orphaned hashtags to delete!');
        }
        

        $this->info(' ');

        $count = StatusHashtag::whereNull('status_visibility')->count();
        if($count > 0) {
            $this->info("Found {$count} hashtags to fix ...");
            $this->info(' ');
        } else {
            $this->info('Found no hashtags to fix!');
            $this->info(' ');
            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        StatusHashtag::with('status')
        ->whereNull('status_visibility')
        ->chunk(50, function($tags) use($bar) {
            foreach($tags as $tag) {
                if(!$tag->status || !$tag->status->scope) {
                    continue;
                }
                $tag->status_visibility = $tag->status->scope;
                $tag->save();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->info(' ');
        $this->info(' ');
    }
}
