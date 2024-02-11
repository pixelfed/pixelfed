<?php

namespace App\Jobs\ParentalControlsPipeline;

use App\Mail\ParentChildInvite;
use App\Models\ParentalControls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class DispatchChildInvitePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $pc;

    /**
     * Create a new job instance.
     */
    public function __construct(ParentalControls $pc)
    {
        $this->pc = $pc;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pc = $this->pc;

        Mail::to($pc->email)->send(new ParentChildInvite($pc));
    }
}
