<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Console\Commands;

use Schema;
use Illuminate\Console\Command;
use App\Jobs\ImageOptimizePipeline\ImageThumbnail;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run pixelfed schema updates between versions.';

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
        $this->update();
    }

    public function update()
    {
        $v = $this->getVersionFile();
        if($v && isset($v['commit_hash']) && $v['commit_hash'] == exec('git rev-parse HEAD') && \App\StatusHashtag::whereNull('profile_id')->count() == 0) {
            $this->info('No updates found.');
            return;
        }
        $bar = $this->output->createProgressBar(\App\StatusHashtag::whereNull('profile_id')->count());
        \App\StatusHashtag::whereNull('profile_id')->with('status')->chunk(50, function($sh) use ($bar) {
            foreach($sh as $status_hashtag) {
                if(!$status_hashtag->status) {
                    $status_hashtag->delete();
                } else {
                    $status_hashtag->profile_id = $status_hashtag->status->profile_id;
                    $status_hashtag->save();
                }
                $bar->advance();
            }
        });
        $this->updateVersionFile();
        $bar->finish();
    }

    protected function getVersionFile()
    {
        $path = storage_path('app/version.json');
        return is_file($path) ? 
            json_decode(file_get_contents($path), true) :
            false;
    }

    protected function updateVersionFile() {
        $path = storage_path('app/version.json');
        $contents = [
            'commit_hash' => exec('git rev-parse HEAD'),
            'version' => config('pixelfed.version'),
            'timestamp' => date('c')
        ];
        $json = json_encode($contents, JSON_PRETTY_PRINT);
        file_put_contents($path, $json);
    }
}
