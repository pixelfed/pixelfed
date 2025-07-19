<?php

namespace App\Jobs\GroupsPipeline;

use App\Models\GroupMember;
use App\Services\GroupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MemberJoinApprovedPipeline implements ShouldQueue
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
        $member->approved_at = now();
        $member->join_request = false;
        $member->role = 'member';
        $member->save();

        GroupService::del($member->group_id);
        GroupService::delSelf($member->group_id, $member->profile_id);
    }
}
