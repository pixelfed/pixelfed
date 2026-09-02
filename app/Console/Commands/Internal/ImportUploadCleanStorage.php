<?php

namespace App\Console\Commands\Internal;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:import-upload-clean-storage')]
#[Description('Command description')]
class ImportUploadCleanStorage extends Command
{
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
