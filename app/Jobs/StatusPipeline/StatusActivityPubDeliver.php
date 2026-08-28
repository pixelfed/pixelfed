<?php

namespace App\Jobs\StatusPipeline;

use App\Models\Profile;
use App\Models\Status;
use App\Transformer\ActivityPub\Verb\CreateNote;
use App\Transformer\ActivityPub\Verb\CreateQuestion;
use App\Util\ActivityPub\HttpSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;

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

        $fractal = new Fractal\Manager;
        $fractal->setSerializer(new ArraySerializer);
        $resource = new Fractal\Resource\Item($status, $activitypubObject);
        $activity = $fractal->createData($resource)->toArray();

        $payload = json_encode($activity);

        $version = config('pixelfed.version');
        $appUrl = config('app.url');
        $userAgent = "(Pixelfed/{$version}; +{$appUrl})";
        $timeout = config('federation.activitypub.delivery.timeout');

        Http::pool(function (Pool $pool) use ($audience, $activity, $profile, $payload, $userAgent, $timeout) {
            foreach ($audience as $url) {
                $curlHeaders = HttpSignature::sign($profile, $url, $activity, [
                    'Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                    'User-Agent' => $userAgent,
                ]);

                $headers = $this->parseCurlHeaders($curlHeaders);

                $pool->withHeaders($headers)
                    ->timeout($timeout)
                    ->withBody($payload, 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"')
                    ->post($url);
            }
        });
    }

    /**
     * Convert curl-format header array ("Header: value") to associative array.
     *
     * @param  array<int, string>  $curlHeaders
     * @return array<string, string>
     */
    private function parseCurlHeaders(array $curlHeaders): array
    {
        $headers = [];
        foreach ($curlHeaders as $header) {
            $parts = explode(': ', $header, 2);
            if (count($parts) === 2) {
                $headers[$parts[0]] = $parts[1];
            }
        }

        return $headers;
    }
}
