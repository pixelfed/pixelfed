<?php

namespace App\Jobs\InboxPipeline;

use App\Profile;
use App\Util\ActivityPub\Inbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InboxWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $headers;
    protected $profile;
    protected $payload;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($headers, $profile, $payload)
    {
        $this->headers = $headers;
        $this->profile = $profile;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new Inbox($this->headers, $this->profile, $this->payload))->handle();
    }
}
