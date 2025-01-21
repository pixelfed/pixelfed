<?php

namespace App\Jobs\FollowPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AccountService;
use App\Services\FollowerService;
use Cache;
use DB;
use Storage;
use App\Follower;
use App\Profile;

class FollowServiceWarmCacheLargeIngestPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $profileId;
    public $followType;
    public $tries = 5;
    public $timeout = 5000;
    public $failOnTimeout = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($profileId, $followType = 'following')
    {
        $this->profileId = $profileId;
        $this->followType = $followType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pid = $this->profileId;
        $type = $this->followType;

        if($type === 'followers') {
            $key = 'follow-warm-cache/' . $pid . '/followers.json';
            if(!Storage::exists($key)) {
                return;
            }
            $file = Storage::get($key);
            $json = json_decode($file, true);

            foreach($json as $id) {
                FollowerService::add($id, $pid, false);
                usleep(random_int(500, 3000));
            }
            sleep(5);
            Storage::delete($key);
        }

        if($type === 'following') {
            $key = 'follow-warm-cache/' . $pid . '/following.json';
            if(!Storage::exists($key)) {
                return;
            }
            $file = Storage::get($key);
            $json = json_decode($file, true);

            foreach($json as $id) {
                FollowerService::add($pid, $id, false);
                usleep(random_int(500, 3000));
            }
            sleep(5);
            Storage::delete($key);
        }

        sleep(random_int(2, 5));
        $files = Storage::files('follow-warm-cache/' . $pid);
        if(empty($files)) {
            Storage::deleteDirectory('follow-warm-cache/' . $pid);
        }
    }
}
