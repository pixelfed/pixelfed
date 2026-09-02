<?php

namespace App\Jobs\ProfilePipeline;

use App\Services\ActivityPubDeliveryService;
use App\Services\FractalService;
use App\Transformer\ActivityPub\Verb\Move;
use Illuminate\Bus\Batchable;
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
use Illuminate\Support\Facades\Log;

#[Timeout(1400)]
#[Tries(3)]
#[MaxExceptions(1)]
#[FailOnTimeout]
#[UniqueFor(3600)]
class ProfileMigrationDeliverMoveActivityPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $migration;

    public $oldAccount;

    public $newAccount;

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'profile:migration:deliver-move-followers:id:'.$this->migration->id;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('profile:migration:deliver-move-followers:id:'.$this->migration->id))->shared()->dontRelease()];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($migration, $oldAccount, $newAccount)
    {
        $this->migration = $migration;
        $this->oldAccount = $oldAccount;
        $this->newAccount = $newAccount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $migration = $this->migration;
        $profile = $this->oldAccount;

        if ($profile->domain || ! $profile->private_key) {
            return;
        }

        $audience = $profile->getAudienceInbox();

        $activity = FractalService::item($migration, new Move);

        ActivityPubDeliveryService::pool($profile, $audience, $activity, function ($reason, $index) {
            Log::error($reason);
        });
    }
}
