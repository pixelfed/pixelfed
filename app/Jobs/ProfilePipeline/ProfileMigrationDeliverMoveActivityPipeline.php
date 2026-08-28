<?php

namespace App\Jobs\ProfilePipeline;

use App\Services\ActivityPubDeliveryService;
use App\Transformer\ActivityPub\Verb\Move;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class ProfileMigrationDeliverMoveActivityPipeline implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $migration;

    public $oldAccount;

    public $newAccount;

    public $timeout = 1400;

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

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($migration, new Move);
        $activity = $fractal->createData($resource)->toArray();

        ActivityPubDeliveryService::pool($profile, $audience, $activity, function ($reason, $index) {
            Log::error($reason);
        });
    }
}
