<?php

namespace App\Jobs\GroupPipeline;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\Profile;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GroupMemberInvite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invite;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(GroupInvitation $invite)
    {
        $this->invite = $invite;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $invite = $this->invite;
        $actor = Profile::find($invite->from_profile_id);
        $target = Profile::find($invite->to_profile_id);

        if (! $actor || ! $target) {
            return;
        }

        NotificationService::createNotification($target->id, $actor->id, 'group:invite', $invite->group_id, Group::class);
    }
}
