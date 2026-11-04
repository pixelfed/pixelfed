<?php

namespace App\Jobs\StatusPipeline;

use App\Models\Status;
use App\Services\ActivityPubFanoutService;
use App\Services\FractalService;
use App\Services\Status\StatusAudienceService;
use App\Transformer\ActivityPub\Verb\CreateNote;
use App\Transformer\ActivityPub\Verb\CreateQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StatusActivityPubDeliver implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

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
    public function __construct(Status $status)
    {
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $status = $this->status;

        // Verify status exists
        if (! $status) {
            Log::info('StatusActivityPubDeliver: Status no longer exists, skipping job');

            return;
        }

        $profile = $status->profile;

        // Verify profile exists
        if (! $profile) {
            Log::info("StatusActivityPubDeliver: Profile no longer exists for status {$status->id}, skipping job");

            return;
        }

        // ignore group posts
        // if($status->group_id != null) {
        //     return;
        // }

        if ($status->local == false || $status->url || $status->uri) {
            return;
        }

        $audience = StatusAudienceService::inboxes($status, $profile);

        if ($audience === [] || ! in_array($status->scope, ['public', 'unlisted', 'private'])) {
            // Return on profiles with no remote followers
            return;
        }

        switch ($status->type) {
            case 'poll':
                $activitypubObject = new CreateQuestion;
                break;

            default:
                $activitypubObject = new CreateNote;
                break;
        }

        $activity = FractalService::item($status, $activitypubObject);

        /*
         * FEP-8fcf: followers-only posts rely on the receiving server's copy
         * of our followers collection for access control, so they carry a
         * Collection-Synchronization header that lets it detect drift.
         */
        ActivityPubFanoutService::dispatch(
            $profile,
            $activity,
            $audience,
            $status->scope === 'private',
            (int) $status->id
        );
    }
}
