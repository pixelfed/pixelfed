<?php

namespace App\Jobs\StatusPipeline;

use App\Status;
use App\Transformer\ActivityPub\Verb\UpdateNote;
use App\Util\ActivityPub\HttpSignature;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        $profile = $status->profile;

        // ignore group posts
        // if($status->group_id != null) {
        //     return;
        // }

        if ($status->local == false || $status->url || $status->uri) {
            return;
        }

        $audience = $status->profile->getAudienceInbox();

        if (empty($audience) || ! in_array($status->scope, ['public', 'unlisted', 'private'])) {
            // Return on profiles with no remote followers
            return;
        }

        switch ($status->type) {
            case 'poll':
                // Polls not yet supported
                return;
                break;

            default:
                $activitypubObject = new UpdateNote();
                break;
        }

        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($status, $activitypubObject);
        $activity = $fractal->createData($resource)->toArray();

        $payload = json_encode($activity);

        $client = new Client([
            'timeout' => config('federation.activitypub.delivery.timeout'),
        ]);

        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";

        $requests = function ($audience) use ($client, $activity, $profile, $payload, $userAgent) {
            foreach ($audience as $url) {
                $headers = HttpSignature::sign($profile, $url, $activity, [
                    'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                    'User-Agent' => $userAgent,
                ]);
                yield function () use ($client, $url, $headers, $payload) {
                    return $client->postAsync($url, [
                        'curl' => [
                            CURLOPT_HTTPHEADER => $headers,
                            CURLOPT_POSTFIELDS => $payload,
                            CURLOPT_HEADER => true,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false,
                        ],
                    ]);
                };
            }
        };

        $pool = new Pool($client, $requests($audience), [
            'concurrency' => config('federation.activitypub.delivery.concurrency'),
            'fulfilled' => function ($response, $index) {
            },
            'rejected' => function ($reason, $index) {
            },
        ]);

        $promise = $pool->promise();

        $promise->wait();
    }
}
