<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImportPost;
use Storage;
use App\Services\ImportService;

class ImportUploadGarbageCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-upload-garbage-collection';

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
        if(!config('import.instagram.enabled')) {
            return;
        }

        $ips = ImportPost::whereNull('status_id')->where('skip_missing_media', true)->take(100)->get();

        if(!$ips->count()) {
            return;
        }

        foreach($ips as $ip) {
            $pid = $ip->profile_id;
            $ip->delete();
            ImportService::getPostCount($pid, true);
            ImportService::clearAttempts($pid);
            ImportService::getImportedFiles($pid, true);
        }
    }
}
