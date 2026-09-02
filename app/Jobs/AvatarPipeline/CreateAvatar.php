<?php

namespace App\Jobs\AvatarPipeline;

use App\Models\Avatar;
use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

#[Tries(3)]
#[MaxExceptions(3)]
#[Timeout(900)]
#[FailOnTimeout]
#[DeleteWhenMissingModels]
#[UniqueFor(3600)]
class CreateAvatar implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $profile;

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
