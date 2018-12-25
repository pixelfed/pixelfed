<?php

namespace App\Jobs\StatusPipeline;

use App\Status;
use Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Redis;

class NewStatusPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

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
        $status = $this->status;

        StatusEntityLexer::dispatch($status);
        StatusActivityPubDeliver::dispatch($status);
        
        // Cache::forever('post.'.$status->id, $status);
        // $redis = Redis::connection();
        // $redis->lpush(config('cache.prefix').':user.'.$status->profile_id.'.posts', $status->id);
    }
}
