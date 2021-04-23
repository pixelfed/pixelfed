<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Jobs\MentionPipeline;

use App\Mention;
use App\Notification;
use App\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MentionPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;
    protected $mention;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

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
                  ->whereIn('action', ['mention', 'comment'])
                  ->whereItemId($status->id)
                  ->whereItemType('App\Status')
                  ->count();

        if ($actor->id === $target || $exists !== 0) {
            return true;
        }

        try {
            $notification = new Notification();
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
