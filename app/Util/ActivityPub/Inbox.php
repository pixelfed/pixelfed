<?php

namespace App\Util\ActivityPub;

use App\Like;
use App\Profile;

class Inbox
{
    protected $request;
    protected $profile;
    protected $payload;

    public function __construct($request, Profile $profile, $payload)
    {
        $this->request = $request;
        $this->profile = $profile;
        $this->payload = $payload;
    }

    public function handle()
    {
        $this->authenticatePayload();
    }

    public function authenticatePayload()
    {
        // todo

        $this->handleVerb();
    }

    public function handleVerb()
    {
        $verb = $this->payload['type'];

        switch ($verb) {
            case 'Create':
                $this->handleCreateActivity();
                break;

            case 'Follow':
                $this->handleFollowActivity();
                break;

            default:
                // TODO: decide how to handle invalid verbs.
                break;
        }
    }

    public function handleCreateActivity()
    {
        // todo
    }

    public function handleFollowActivity()
    {
        $actor = $this->payload['object'];
        $target = $this->profile;
    }

    public function actorFirstOrCreate($actorUrl)
    {
        if (Profile::whereRemoteUrl($actorUrl)->count() !== 0) {
            return Profile::whereRemoteUrl($actorUrl)->firstOrFail();
        }

        $res = (new DiscoverActor($url))->discover();

        $domain = parse_url($res['url'], PHP_URL_HOST);
        $username = $res['preferredUsername'];
        $remoteUsername = "@{$username}@{$domain}";

        $profile = new Profile();
        $profile->user_id = null;
        $profile->domain = $domain;
        $profile->username = $remoteUsername;
        $profile->name = $res['name'];
        $profile->bio = str_limit($res['summary'], 125);
        $profile->sharedInbox = $res['endpoints']['sharedInbox'];
        $profile->remote_url = $res['url'];
        $profile->save();
    }
}
