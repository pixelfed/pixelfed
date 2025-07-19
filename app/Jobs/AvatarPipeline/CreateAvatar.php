<?php

namespace App\Jobs\AvatarPipeline;

use App\Avatar;
use App\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class CreateAvatar implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $profile;

    public $tries = 3;

    public $maxExceptions = 3;

    public $timeout = 900;

    public $failOnTimeout = true;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'avatar:create:'.$this->profile->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("avatar-create:{$this->profile->id}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Profile $profile)
    {
        $this->profile = $profile->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $profile = $this->profile;
        $isRemote = (bool) $profile->private_key == null;
        $path = 'public/avatars/default.jpg';
        Avatar::updateOrCreate(
            [
                'profile_id' => $profile->id,
            ],
            [
                'media_path' => $path,
                'change_count' => 0,
                'is_remote' => $isRemote,
                'last_processed_at' => now(),
            ]
        );
    }
}
