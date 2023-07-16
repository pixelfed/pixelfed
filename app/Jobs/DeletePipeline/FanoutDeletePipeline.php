<?php

namespace App\Jobs\DeletePipeline;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Cache;
use DB;
use Illuminate\Support\Str;
use App\Profile;
use App\Util\ActivityPub\Helpers;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Util\ActivityPub\HttpSignature;

class FanoutDeletePipeline implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $profile;

	public $timeout = 300;
	public $tries = 1;

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

		$client = new Client([
            'timeout'  => config('federation.activitypub.delivery.timeout')
        ]);

        $audience = Cache::remember('pf:ap:known_instances', now()->addHours(6), function() {
        	return Profile::whereNotNull('sharedInbox')->groupBy('sharedInbox')->pluck('sharedInbox')->toArray();
        });

        $activity = [
        	"@context" => "https://www.w3.org/ns/activitystreams",
        	"id" => $profile->permalink('#delete'),
        	"type" => "Delete",
        	"actor" => $profile->permalink(),
        	"object" => [
        		"type" => "Person",
				"id" => $profile->permalink()
        	],
        ];

        $payload = json_encode($activity);

        $requests = function($audience) use ($client, $activity, $profile, $payload) {
            foreach($audience as $url) {
				$version = config('pixelfed.version');
				$appUrl = config('app.url');
				$headers = HttpSignature::sign($profile, $url, $activity, [
					'Content-Type'	=> 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
					'User-Agent'	=> "(Pixelfed/{$version}; +{$appUrl})",
				]);
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

        return 1;
	}
}
