<?php

namespace App\Jobs\MovePipeline;

use App\Util\ActivityPub\HttpSignature;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class MoveSendUndoFollowPipeline implements ShouldQueue
{
    use Queueable;

    public $follower;

    public $targetInbox;

    public $targetPid;

    public $actor;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('move-send-unfollow:'.$this->follower->id.':actor:'.$this->actor),
            (new ThrottlesExceptions(2, 5 * 60))->backoff(5),
        ];
    }

    /**
     * Create a new job instance.
     */
    public function __construct($follower, $targetInbox, $targetPid, $actor)
    {
        $this->follower = $follower;
        $this->targetInbox = $targetInbox;
        $this->targetPid = $targetPid;
        $this->actor = $actor;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $follower = $this->follower;
        $targetPid = $this->targetPid;
        $targetInbox = $this->targetInbox;
        $actor = $this->actor;

        if (! $follower->username || ! $follower->private_key) {
            return;
        }

        $permalink = 'https://'.config('pixelfed.domain.app').'/users/'.$follower->username;
        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";
        $addlHeaders = [
            'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            'User-Agent' => $userAgent,
        ];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Undo',
            'id' => $permalink.'#follow/'.$targetPid.'/undo',
            'actor' => $permalink,
            'object' => [
                'type' => 'Follow',
                'id' => $permalink.'#follows/'.$targetPid,
                'object' => $actor,
                'actor' => $permalink,
            ],
        ];

        $keyId = $permalink.'#main-key';
        $payload = json_encode($activity);
        $headers = HttpSignature::signRaw($follower->private_key, $keyId, $targetInbox, $activity, $addlHeaders);

        $client = new Client([
            'timeout' => config('federation.activitypub.delivery.timeout'),
        ]);

        try {
            $client->post($targetInbox, [
                'curl' => [
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_HEADER => true,
                ],
            ]);
        } catch (ClientException $e) {

        }
    }
}
