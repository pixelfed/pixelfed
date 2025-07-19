<?php

namespace App\Jobs\GroupPipeline;

use App\Models\GroupMember;
use App\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class JoinRejected implements ShouldQueue
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

        $n = new Notification;
        $n->profile_id = $member->profile_id;
        $n->actor_id = $member->profile_id;
        $n->item_id = $member->group_id;
        $n->item_type = 'App\Models\Group';
        $n->action = 'group.join.rejected';
        $n->save();
    }
}
