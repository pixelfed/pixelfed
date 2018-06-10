<?php

namespace App\Jobs\MentionPipeline;

use Cache, Log, Redis;
use App\{Mention, Notification, Profile, Status};
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MentionPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;
    protected $mention;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Status $status, Mention $mention)
    {
        $this->status = $status;
        $this->mention = $mention;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        $status = $this->status;
        $mention = $this->mention;
        $actor = $this->status->profile;
        $target = $this->mention->profile_id;

        $exists = Notification::whereProfileId($target)
                  ->whereActorId($actor->id)
                  ->whereAction('mention')
                  ->whereItemId($status->id)
                  ->whereItemType('App\Status')
                  ->count();

        if($actor->id === $target || $exists !== 0) {
            return true;
        }

        try {

            $notification = new Notification;
            $notification->profile_id = $target;
            $notification->actor_id = $actor->id;
            $notification->action = 'mention';
            $notification->message = $mention->toText();
            $notification->rendered = $mention->toHtml();
            $notification->item_id = $status->id;
            $notification->item_type = "App\Status";
            $notification->save();

        } catch (Exception $e) {
            
        }

    }
}
