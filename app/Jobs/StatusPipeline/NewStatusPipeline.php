<?php

namespace App\Jobs\StatusPipeline;

use App\Media;
use App\Status;
use Cache;
use DB;
use Log;
use InvalidArgumentException;
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
        Log::info("aoeu NewStatusPipeline");
        Log::info(json_encode($this->status->toActivityPubObject()));
        if ($this->status->publish_delayed === null) {
            Log::info("aoeu dispatching status immediately");
            StatusEntityLexer::dispatch($this->status);
        } else if ($this->status->id) {
            $still_processing_count = Media::whereStatusId($this->status->id)
                ->whereNull('cdn_url')
                ->count();
            if ($still_processing_count == 0) {
                // The status is available to be published
                // To prevent concurrency issues, the NewStatusPipeline job is published
                // multiple times in this scenario (and may be running concurrently)
                // To avoid this race condition, atomically try to set publish_delayed to false
                // for this row. Even if two sql queries race each other, only one query will
                // win the race and update the database. The thread that wins is responsible
                // for updating the status
                $rows_updated = DB::table('statuses')
                    ->where('id', $this->status->id)
                    ->where('publish_delayed', true)
                    ->limit(1)
                    ->update(array('publish_delayed' => false));
                
                if ($rows_updated > 0) {
                    Log::info("aoeu Publishing the delayed status");
                    StatusEntityLexer::dispatch($this->status);
                } else {
                    Log::info("aoeu Did not win the publish race - skipping");
                }
            } else {
                Log::info("aoeu There are still outstanding media items - skipping");
            }
        } else {
            throw new InvalidArgumentException("A status was marked as publish_delayed, but the status_id was not passed in");
        }
    }
}
