<?php

namespace App\Jobs\AvatarPipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Services\AvatarService;
use App\Avatar;

class AvatarStorageCleanup implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $avatar;
    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 900;
    public $failOnTimeout = true;

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
        return 'avatar:storage:cleanup:' . $this->avatar->profile_id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("avatar-storage-cleanup:{$this->avatar->profile_id}"))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(Avatar $avatar)
    {
        $this->avatar = $avatar->withoutRelations();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        AvatarService::cleanup($this->avatar, true);

        return;
    }
}
