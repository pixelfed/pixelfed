<?php

namespace App\Jobs\StatusPipeline;

use App\Profile;
use App\Services\FractalService;
use App\Status;
use App\Transformer\ActivityPub\Verb\CreateNote;
use App\Transformer\ActivityPub\Verb\CreateQuestion;
use App\Util\ActivityPub\HttpSignature;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
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

        $audience = $status->profile->getAudienceInbox();

        $parentInbox = [];

        $mentions = $status->mentions
            ->filter(function ($f) {
                return $f->domain !== null;
            })
            ->values()
            ->map(function ($m) {
                return $m->sharedInbox ?? $m->inbox_url;
            })
            ->toArray();

        if ($status->in_reply_to_profile_id) {
            $parent = Profile::find($status->in_reply_to_profile_id);
            if ($parent && $parent->domain !== null) {
                $parentInbox = [
                    $parent->sharedInbox ?? $parent->inbox_url,
                ];
            }
        }

        $audience = array_values(array_unique(array_merge($audience, $mentions, $parentInbox)));

        if (empty($audience) || ! in_array($status->scope, ['public', 'unlisted', 'private'])) {
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
                        ],
                    ]);
                };
            }
        };

        $pool = new Pool($client, $requests($audience), [
            'concurrency' => config('federation.activitypub.delivery.concurrency'),
            'fulfilled' => function ($response, $index) {},
            'rejected' => function ($reason, $index) {},
        ]);

        $promise = $pool->promise();

        $promise->wait();
    }
}
