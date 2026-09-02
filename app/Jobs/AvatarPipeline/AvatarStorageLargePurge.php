<?php

namespace App\Jobs\AvatarPipeline;

use App\Models\Avatar;
use App\Services\AvatarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

#[Tries(3)]
#[MaxExceptions(3)]
#[Timeout(900)]
#[FailOnTimeout]
#[UniqueFor(3600)]
class AvatarStorageLargePurge implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $avatar;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'avatar:storage:lg-purge:'.$this->avatar->profile_id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("avatar-storage-purge:{$this->avatar->profile_id}"))->shared()->dontRelease()];
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
        $avatar = $this->avatar;

        $disk = AvatarService::disk();

        $files = collect(AvatarService::storage($avatar));

        $curFile = Str::of($avatar->cdn_url)->explode('/')->last();

        $files = $files->filter(function ($f) use ($curFile) {
            return ! $curFile || ! str_ends_with($f, $curFile);
        })->each(function ($name) use ($disk) {
            $disk->delete($name);
        });

    }
}
