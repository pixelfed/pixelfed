<?php

namespace App\Jobs\ProfilePipeline;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use App\Notification;
use DB;
use App\Services\NotificationService;

class ProfilePurgeNotificationsByDomain implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pid;
    protected $domain;

    public $timeout = 900;
    public $tries = 3;
    public $maxExceptions = 1;
    public $failOnTimeout = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct($pid, $domain)
    {
        $this->pid = $pid;
        $this->domain = $domain;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $pid = $this->pid;
        $domain = $this->domain;

        $query = 'SELECT notifications.*
            FROM profiles
            JOIN notifications on profiles.id = notifications.actor_id
            WHERE notifications.profile_id = ?
            AND profiles.domain = ?';
        $params = [$pid, $domain];

        foreach(DB::cursor($query, $params) as $n) {
            if(!$n || !$n->id) {
                continue;
            }
            Notification::where('id', $n->id)->delete();
            NotificationService::del($pid, $n->id);
        }
    }
}
