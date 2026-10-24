<?php

namespace App\Jobs\FollowPipeline;

use App\Models\Profile;
use App\Services\FollowersSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * FEP-8fcf: reconcile our local copy of a remote actor's followers with the
 * partial followers collection served by the authoritative server.
 */
class FollowersSyncPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profileId;

    protected $collectionId;

    protected $url;

    protected $digest;

    public $timeout = 300;

    public $tries = 1;

    public $maxExceptions = 1;

    /**
     * Create a new job instance.
     *
     * @param  int|string  $profileId  Remote profile that sent the Collection-Synchronization header
     * @param  string  $collectionId  `collectionId` header parameter
     * @param  string  $url  `url` header parameter
     * @param  string  $digest  `digest` header parameter
     */
    public function __construct($profileId, string $collectionId, string $url, string $digest)
    {
        $this->profileId = $profileId;
        $this->collectionId = $collectionId;
        $this->url = $url;
        $this->digest = $digest;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("ap:followers-sync:pid:{$this->profileId}"))->shared()->dontRelease()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sender = Profile::whereNotNull('domain')
            ->whereNull('status')
            ->find($this->profileId);

        if (! $sender) {
            return;
        }

        try {
            FollowersSyncService::synchronize(
                $sender,
                $this->collectionId,
                $this->url,
                $this->digest
            );
        } catch (Throwable $e) {
            Log::warning('FollowersSync: synchronization failed', [
                'profile_id' => $sender->id,
                'url' => $this->url,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
