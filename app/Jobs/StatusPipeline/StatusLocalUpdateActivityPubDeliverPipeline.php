<?php

namespace App\Jobs\StatusPipeline;

use App\Models\Status;
use App\Services\ActivityPubDeliveryService;
use App\Services\FractalService;
use App\Transformer\ActivityPub\Verb\UpdateNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[DeleteWhenMissingModels]
class StatusLocalUpdateActivityPubDeliverPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $status;

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

        if (! $status) {
            Log::info('StatusLocalUpdateActivityPubDeliverPipeline: Status no longer exists, skipping job');

            return;
        }

        $profile = $status->profile;

        if (! $profile) {
            Log::info("StatusLocalUpdateActivityPubDeliverPipeline: Profile no longer exists for status {$status->id}, skipping job");

            return;
        }

        if ($status->local == false || $status->url || $status->uri) {
            return;
        }

        $audience = $status->profile->getAudienceInbox();

        if (empty($audience) || ! in_array($status->scope, ['public', 'unlisted', 'private'])) {
            return;
        }

        switch ($status->type) {
            case 'poll':
                return;

            default:
                $activitypubObject = new UpdateNote;
                break;
        }

        $activity = FractalService::item($status, $activitypubObject);

        ActivityPubDeliveryService::pool($profile, $audience, $activity);
    }
}
