<?php

namespace App\Jobs\InboxPipeline;

use App\Profile;
use App\Util\ActivityPub\Inbox;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SharedInboxWorker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $request;
    protected $profile;
    protected $payload;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request, $payload)
    {
        $this->request = $request;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        (new Inbox($this->request, null, $this->payload))->handleSharedInbox();
    }
}
