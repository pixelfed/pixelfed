<?php

namespace App\Jobs\StatusPipeline;

use Cache, Redis;
use App\{Media, Status};
use App\Jobs\ImageOptimizePipeline\ImageOptimize;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class NewStatusPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;
    protected $media;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status, $media = false)
    {
        $this->status = $status;
        $this->media = $media;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;
        $media = $this->media;

        StatusEntityLexer::dispatch($status);
        StatusActivityPubDeliver::dispatch($status);
        if($media) {
            ImageOptimize::dispatch($media);
        }
        Cache::forever('post.' . $status->id, $status);
        
        $redis = Redis::connection();
        $redis->lpush(config('cache.prefix').':user.' . $status->profile_id . '.posts', $status->id);
    }
}
