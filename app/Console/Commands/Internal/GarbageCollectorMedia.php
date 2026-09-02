<?php

namespace App\Console\Commands\Internal;

use App\Models\Media;
use App\Services\MediaStorageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('media:gc')]
#[Description('Delete media uploads not attached to any active statuses')]
class GarbageCollectorMedia extends Command
{
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
        $limit = 500;

        $gc = Media::whereNull('status_id')
            ->where('created_at', '<', now()->subHours(2)->toDateTimeString())
            ->take($limit)
            ->get();

        $bar = $this->output->createProgressBar($gc->count());
        $bar->start();
        foreach ($gc as $media) {
            MediaStorageService::delete($media, true);
            $bar->advance();
        }
        $bar->finish();
        $this->line('');
    }
}
