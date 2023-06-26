<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportPost;
use Storage;
use App\Services\ImportService;
use App\User;

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

        foreach($dirs as $dir) {
            $uid = last(explode('/', $dir));
            $skip = User::whereNull('status')->find($uid);
            if(!$skip) {
                Storage::deleteDirectory($dir);
            }
        }
    }
}
