<?php

namespace App\Console\Commands\Internal;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
        // Only the top-level imports/{user_id} directories are named after
        // user ids. allDirectories() recurses, so a valid user's nested folder
        // (e.g. imports/1/media) would be read as uid "media", match no user,
        // and be deleted — wiping that user's import data.
        $dirs = Storage::directories('imports');

        foreach ($dirs as $dir) {
            $uid = last(explode('/', $dir));
            $skip = User::whereNull('status')->find($uid);
            if (! $skip) {
                Storage::deleteDirectory($dir);
            }
        }
    }
}
