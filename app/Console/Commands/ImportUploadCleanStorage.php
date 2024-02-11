<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Storage;

class ImportUploadCleanStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-upload-clean-storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dirs = Storage::allDirectories('imports');

        foreach ($dirs as $dir) {
            $uid = last(explode('/', $dir));
            $skip = User::whereNull('status')->find($uid);
            if (! $skip) {
                Storage::deleteDirectory($dir);
            }
        }
    }
}
