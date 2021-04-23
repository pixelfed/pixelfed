<?php

/*
 * SPDX-FileCopyrightText: 2018 Daniel Supernault 
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace App\Jobs\StatusPipeline;

use Cache, Log;
use App\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Fractal;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformer\ActivityPub\Verb\CreateNote;
use App\Util\ActivityPub\Helpers;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Util\ActivityPub\HttpSignature;

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
        $profile = $status->profile;

        if($status->local == false || $status->url || $status->uri) {
            return;
        }

        $audience = $status->profile->getAudienceInbox();

        if(empty($audience) || !in_array($status->scope, ['public', 'unlisted', 'private'])) {
            // Return on profiles with no remote followers
            return;
        }


        $fractal = new Fractal\Manager();
        $fractal->setSerializer(new ArraySerializer());
        $resource = new Fractal\Resource\Item($status, new CreateNote());
        $activity = $fractal->createData($resource)->toArray();

        $payload = json_encode($activity);
        
        $client = new Client([
            'timeout'  => config('federation.activitypub.delivery.timeout')
        ]);

        $requests = function($audience) use ($client, $activity, $profile, $payload) {
            foreach($audience as $url) {
                $headers = HttpSignature::sign($profile, $url, $activity);
                yield function() use ($client, $url, $headers, $payload) {
                    return $client->postAsync($url, [
                        'curl' => [
                            CURLOPT_HTTPHEADER => $headers, 
                            CURLOPT_POSTFIELDS => $payload,
                            CURLOPT_HEADER => true
                        ]
                    ]);
                };
            }
        };

        $pool = new Pool($client, $requests($audience), [
            'concurrency' => config('federation.activitypub.delivery.concurrency'),
            'fulfilled' => function ($response, $index) {
            },
            'rejected' => function ($reason, $index) {
            }
        ]);
        
        $promise = $pool->promise();

        $promise->wait();
    }
}
