<?php

namespace App\Jobs\InboxPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Util\ActivityPub\Inbox;

class ActivityHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $username;
    protected $headers;
    protected $payload;

    public $timeout = 300;
    public $tries = 1;
    public $maxExceptions = 1;

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
        $headers = $this->headers;
        $username = $this->username;
        $payload = $this->payload;

        // Verify required data exists
        if (!$headers) {
            Log::info("ActivityHandler: No headers provided, skipping job");
            return;
        }

        if (!$username) {
            Log::info("ActivityHandler: No username provided, skipping job");
            return;
        }

        if (!$payload) {
            Log::info("ActivityHandler: No payload provided, skipping job");
            return;
        }

        try {
            (new Inbox($headers, $username, $payload))->handle();
        } catch (\Exception $e) {
            Log::warning("ActivityHandler: Failed to handle activity for username {$username}: " . $e->getMessage());
            throw $e;
        }

        return;
    }
}
