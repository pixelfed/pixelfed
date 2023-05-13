<?php

namespace App\Jobs\StatusPipeline;

use App\Media;
use App\Status;
use Cache;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class NewStatusPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;
    
    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    public $timeout = 5;
    public $tries = 1;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $deleted_count = Media::whereStatusId($this->status->id)
        ->whereNotNull('deleted_at')
        ->count();
        if ($deleted_count > 0) {
            // The status has already been deleted by the time the job is running
            // Don't publish the status, and just no-op
            return;
        }
        if (config_cache('pixelfed.cloud_storage') && !config('pixelfed.media_fast_process')) {
            // N.B. In this configuration, the NewStatusPipeline is called multiple times per status
            // Once when the media is finished, and again when the status is posted
            // This may lead to the status being published to ActivityPub twice, with the same content.
            // (only in a race condition, not the default flow)
            // The race was allowed deliberately. It prevents the need for synchronization inside of the workers
            // It is expected that ActivityPub clients handle this properly, but the solution may need
            // to be revisited in the future.
            $still_processing_count = Media::whereStatusId($this->status->id)
                ->whereNull('cdn_url')
                ->count();
            if ($still_processing_count > 0) {
                // The media items in the status are still being processed.
                // We can't publish the status to ActivityPub because the final remote URL is not
                // yet known. Instead, do nothing here. The media pipeline will re-call the NewStatusPipeline
                // once all media is finished processing
                // When the 
                return;
            }
        }
        StatusEntityLexer::dispatch($this->status);
    }
}
