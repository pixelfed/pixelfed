<?php

namespace App\Jobs\AvatarPipeline;

use App\Avatar;
use App\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateAvatar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

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
    public function __construct(Profile $profile)
    {
        $this->profile = $profile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $profile = $this->profile;
        $path = 'public/avatars/default.jpg';
        $avatar = new Avatar();
        $avatar->profile_id = $profile->id;
        $avatar->media_path = $path;
        $avatar->change_count = 0;
        $avatar->last_processed_at = \Carbon\Carbon::now();
        $avatar->save();
    }
}
