<?php

namespace App\Jobs\RemoteFollowPipeline;

use App\Jobs\AvatarPipeline\CreateAvatar;
use App\{Profile};
use GuzzleHttp\Client;
use HttpSignatures\Context;
use HttpSignatures\GuzzleHttpSignatures;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Zttp\Zttp;

class RemoteFollowPipeline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $url;
    protected $follower;
    protected $response;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($follower, $url)
    {
        $this->follower = $follower;
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $follower = $this->follower;
        $url = $this->url;

        if (Profile::whereRemoteUrl($url)->count() !== 0) {
            return true;
        }

        $this->discover($url);

        return true;
    }

    public function discover($url)
    {
        $context = new Context([
            'keys'      => ['examplekey' => 'secret-key-here'],
            'algorithm' => 'hmac-sha256',
            'headers'   => ['(request-target)', 'date'],
        ]);

        $handlerStack = GuzzleHttpSignatures::defaultHandlerFromContext($context);
        $client = new Client(['handler' => $handlerStack]);
        $response = Zttp::withHeaders([
            'Accept'     => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            'User-Agent' => 'PixelfedBot v0.1 - https://pixelfed.org',
        ])->get($url);
        $this->response = $response->json();

        $this->storeProfile();
    }

    public function storeProfile()
    {
        $res = $this->response;
        $domain = parse_url($res['url'], PHP_URL_HOST);
        $username = $res['preferredUsername'];
        $remoteUsername = "@{$username}@{$domain}";

        $profile = new Profile();
        $profile->user_id = null;
        $profile->domain = $domain;
        $profile->username = $remoteUsername;
        $profile->name = $res['name'];
        $profile->bio = Purify::clean($res['summary']);
        $profile->sharedInbox = $res['endpoints']['sharedInbox'];
        $profile->remote_url = $res['url'];
        $profile->save();

        RemoteFollowImportRecent::dispatch($this->response, $profile);
        CreateAvatar::dispatch($profile);
    }

    public function sendActivity()
    {
        $res = $this->response;
        $url = $res['inbox'];

        $activity = Zttp::withHeaders(['Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'])->post($url, [
            'type'   => 'Follow',
            'object' => $this->follower->url(),
        ]);
    }
}
