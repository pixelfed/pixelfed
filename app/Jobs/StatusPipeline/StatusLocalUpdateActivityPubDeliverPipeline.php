<?php

namespace App\Jobs\StatusPipeline;

use App\Services\ActivityPubDeliveryService;
use App\Status;
use App\Transformer\ActivityPub\Verb\UpdateNote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class StatusLocalUpdateActivityPubDeliverPipeline implements ShouldQueue
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

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($status, $activitypubObject);
        $activity = $fractal->createData($resource)->toArray();

        ActivityPubDeliveryService::pool($profile, $audience, $activity);
    }
}
