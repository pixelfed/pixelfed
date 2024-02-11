<?php

namespace App\Jobs\FollowPipeline;

use App\FollowRequest;
use App\Transformer\ActivityPub\Verb\RejectFollow;
use App\Util\ActivityPub\Helpers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

class FollowRejectPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $followRequest;

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
    public function __construct(FollowRequest $followRequest)
    {
        $this->followRequest = $followRequest;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $follow = $this->followRequest;
        $actor = $follow->actor;
        $target = $follow->target;

        if ($actor->domain == null || $actor->inbox_url == null || ! $target->private_key) {
            return;
        }

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($follow, new RejectFollow());
        $activity = $fractal->createData($resource)->toArray();
        $url = $actor->sharedInbox ?? $actor->inbox_url;

        Helpers::sendSignedObject($target, $url, $activity);

        $follow->delete();

    }
}
