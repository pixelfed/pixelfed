<?php

namespace App\Console\Commands\Internal;

use App\Models\ImportPost;
use App\Services\ImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:import-upload-garbage-collection')]
#[Description('Command description')]
class GarbageCollectorImportUpload extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! config('import.instagram.enabled')) {
            return;
        }

        $ips = ImportPost::whereNull('status_id')->where('skip_missing_media', true)->take(100)->get();

        if (! $ips->count()) {
            return;
        }

        foreach ($ips as $ip) {
            $pid = $ip->profile_id;
            $ip->delete();
            ImportService::getPostCount($pid, true);
            ImportService::clearAttempts($pid);
            ImportService::getImportedFiles($pid, true);
        }
    }
}
