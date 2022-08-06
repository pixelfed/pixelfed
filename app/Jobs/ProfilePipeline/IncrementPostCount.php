<?php

namespace App\Jobs\ProfilePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Profile;
use App\Status;
use App\Services\AccountService;

class IncrementPostCount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $id = $this->id;

        $profile = Profile::find($id);

        if(!$profile) {
            return 1;
        }

        if($profile->updated_at && $profile->updated_at->lt(now()->subDays(30))) {
            $profile->status_count = Status::whereProfileId($id)->whereNull(['in_reply_to_id', 'reblog_of_id'])->count();
            $profile->save();
            AccountService::del($id);
        } else {
            $profile->status_count = $profile->status_count + 1;
            $profile->save();
            AccountService::del($id);
        }

        return 1;
    }
}
