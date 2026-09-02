<?php

namespace App\Jobs\InboxPipeline;

use App\Util\ActivityPub\Inbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[Timeout(300)]
#[Tries(1)]
#[MaxExceptions(1)]
class ActivityHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $username;

    protected $headers;

    protected $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($headers, $username, $payload)
    {
        $this->username = $username;
        $this->headers = $headers;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new Inbox($this->headers, $this->username, $this->payload))->handle();

    }
}
