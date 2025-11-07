<?php

namespace App\Jobs\RemoteFollowPipeline;

use App\Jobs\AvatarPipeline\CreateAvatar;
use App\Profile;
use App\Services\SanitizeService;
use GuzzleHttp\Client;
use HttpSignatures\Context;
use HttpSignatures\GuzzleHttpSignatures;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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

        // Verify follower and url exists
        if (!$follower) {
            Log::info("RemoteFollowPipeline: Follower no longer exists, skipping job");
            return;
        }
        if (!$url) {
            Log::info("RemoteFollowPipeline: No URL provided, skipping job");
            return;
        }

        if (Profile::whereRemoteUrl($url)->count() !== 0) {
            return true;
        }

        try {
            $this->discover($url);
        } catch (\Exception $e) {
            Log::warning("RemoteFollowPipeline: Failed to discover profile at {$url}: " . $e->getMessage());
            return;
        }

        return true;
    }

    public function discover($url)
    {
        $context = new Context([
            'keys' => ['examplekey' => 'secret-key-here'],
            'algorithm' => 'hmac-sha256',
            'headers' => ['(request-target)', 'date'],
        ]);

        $handlerStack = GuzzleHttpSignatures::defaultHandlerFromContext($context);
        $client = new Client(['handler' => $handlerStack]);
        $response = Http::withHeaders([
            'Accept' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            'User-Agent' => 'PixelfedBot v0.1 - https://pixelfed.org',
        ])->get($url);
        $this->response = $response->json();

        $this->storeProfile();
    }

    public function storeProfile()
    {
        $res = $this->response;
        
        // Verify response has required fields
        if (!isset($res['url'])) {
            Log::warning("RemoteFollowPipeline: Invalid response, missing required field url");
            return;
        }
        if (!isset($res['preferredUsername'])) {
            Log::warning("RemoteFollowPipeline: Invalid response, missing required field preferredUsername");
            return;
        }

        $domain = parse_url($res['url'], PHP_URL_HOST);
        if (!$domain) {
            Log::warning("RemoteFollowPipeline: Could not parse domain from URL: " . $res['url']);
            return;
        }

        $username = $res['preferredUsername'];
        $remoteUsername = "@{$username}@{$domain}";

        try {
            $profile = new Profile;
            $profile->user_id = null;
            $profile->domain = $domain;
            $profile->username = $remoteUsername;
            $profile->name = $res['name'] ?? '';
            $profile->bio = isset($res['summary']) ? app(SanitizeService::class)->html($res['summary']) : '';
            $profile->sharedInbox = $res['endpoints']['sharedInbox'] ?? null;
            $profile->remote_url = $res['url'];
            $profile->save();

            RemoteFollowImportRecent::dispatch($this->response, $profile);
            CreateAvatar::dispatch($profile);
        } catch (\Exception $e) {
            Log::warning("RemoteFollowPipeline: Failed to store profile for {$remoteUsername}: " . $e->getMessage());
        }
    }

    public function sendActivity()
    {
        $res = $this->response;
        $url = $res['inbox'];

        $activity = Http::withHeaders(['Content-Type' => 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'])->post($url, [
            'type' => 'Follow',
            'object' => $this->follower->url(),
        ]);
    }
}
