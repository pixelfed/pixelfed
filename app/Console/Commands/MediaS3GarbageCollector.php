<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Media;
use App\Status;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaS3GarbageCollector extends Command
{
    /**
    * The name and signature of the console command.
    *
    * @var string
    */
    protected $signature = 'media:s3gc {--limit=200}';

    /**
    * The console command description.
    *
    * @var string
    */
    protected $description = 'Delete (local) media uploads that exist on S3';

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
        $enabled = config('pixelfed.cloud_storage');
        if(!$enabled) {
            $this->error('Cloud storage not enabled. Exiting...');
            return;
        }

        $deleteEnabled = config('media.delete_local_after_cloud');
        if(!$deleteEnabled) {
            $this->error('Delete local storage after cloud upload is not enabled');
            return;
        }

        $limit = $this->option('limit');
        $minId = Media::orderByDesc('id')->where('created_at', '<', now()->subHours(12))->first()->id;

        $gc = Media::whereNotNull(['status_id', 'cdn_url', 'replicated_at'])
        ->whereNot('version', '4')
        ->where('id', '<', $minId)
        ->inRandomOrder()
        ->take($limit)
        ->get();

        $totalSize = 0;
        $bar = $this->output->createProgressBar($gc->count());
        $bar->start();
        $cloudDisk = Storage::disk(config('filesystems.cloud'));
        $localDisk = Storage::disk('local');

        foreach($gc as $media) {
            if(
                $cloudDisk->exists($media->media_path)
            ) {
                if( $localDisk->exists($media->media_path)) {
                    $localDisk->delete($media->media_path);
                    $media->version = 4;
                    $media->save();
                    $totalSize = $totalSize + $media->size;
                } else {
                    $media->version = 4;
                    $media->save();
                }
            } else {
                Log::channel('media')->info('[GC] Local media not properly persisted to cloud storage', ['media_id' => $media->id]);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->line(' ');
        $this->info('Finished!');
        if($totalSize) {
            $this->info('Cleared ' . $totalSize . ' bytes of media from local disk!');
        }
        return 0;
    }
}
