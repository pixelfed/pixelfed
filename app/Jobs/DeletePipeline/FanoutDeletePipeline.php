<?php

namespace App\Jobs\DeletePipeline;

use App\Models\Profile;
use App\Services\ActivityPubDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[Timeout(300)]
#[Tries(1)]
class FanoutDeletePipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $profile;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($profile)
    {
        $this->profile = $profile;
    }

    public function handle()
    {
        $profile = $this->profile;

        // Verify profile exists
        if (! $profile) {
            Log::info('FanoutDeletePipeline: Profile no longer exists, skipping job');

            return;
        }

        // Verify profile has required fields for ActivityPub
        if (! $profile->permalink() || ! $profile->private_key) {
            Log::info("FanoutDeletePipeline: Profile {$profile->id} missing required fields for ActivityPub, skipping job");

            return;
        }

        try {
            $audience = Cache::remember('pf:ap:known_instances', now()->addHours(6), function () {
                return Profile::whereNotNull('sharedInbox')->groupBy('sharedInbox')->pluck('sharedInbox')->toArray();
            });

            $activity = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $profile->permalink('#delete'),
                'type' => 'Delete',
                'actor' => $profile->permalink(),
                'object' => [
                    'type' => 'Person',
                    'id' => $profile->permalink(),
                ],
            ];

            ActivityPubDeliveryService::pool($profile, $audience, $activity);
        } catch (\Exception $e) {
            Log::warning("FanoutDeletePipeline: Failed to fanout delete for profile {$profile->id}: ".$e->getMessage());
            throw $e;
        }

        return 1;
    }
}
