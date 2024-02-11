<?php

namespace App\Jobs\DirectPipeline;

use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DirectDeletePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;

    public $tries = 3;

    public $maxExceptions = 1;

    protected $profile;

    protected $url;

    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct($profile, $url, $payload)
    {
        $this->profile = $profile;
        $this->url = $url;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Helpers::sendSignedObject($this->profile, $this->url, $this->payload);
    }
}
