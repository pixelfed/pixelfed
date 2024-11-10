<?php

namespace App\Jobs\GroupsPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\GroupMember;
use App\Notification;
use App\Services\GroupService;

class MemberJoinRejectedPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $member;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GroupMember $member)
    {
        $this->member = $member;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $member = $this->member;
        $member->rejected_at = now();
        $member->save();
    }
}
