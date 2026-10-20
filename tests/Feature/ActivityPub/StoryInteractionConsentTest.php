<?php

use App\Models\DmMessage;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\Story;
use App\Models\User;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Story reply/react consent on the inbound federation path
|--------------------------------------------------------------------------
|
| A story carries two independent consent flags, can_reply and can_react.
| An inbound Story:Reply must be honoured by can_reply and an inbound
| Story:Reaction by can_react. The shared inbox handler used to gate both
| on can_react alone, so a reply landed as a DM even when replies were off.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    config([
        'federation.activitypub.enabled' => true,
        'instance.stories.enabled' => true,
    ]);
});

function consentLocalUser(): User
{
    $user = User::factory()->create();
    $user->refresh();

    return $user;
}

function consentRemoteProfile(string $domain = 'remote.example', string $username = 'bob'): Profile
{
    $actor = "https://{$domain}/users/{$username}";

    return Profile::factory()->remote()->create([
        'domain' => $domain,
        'username' => "@{$username}@{$domain}",
        'remote_url' => $actor,
        'key_id' => "{$actor}#main-key",
        'inbox_url' => "{$actor}/inbox",
        'sharedInbox' => "https://{$domain}/inbox",
        'last_fetched_at' => now(),
    ]);
}

function consentSeedHosts(array $hosts = ['remote.example']): void
{
    $hosts[] = config('pixelfed.domain.app');

    foreach ($hosts as $host) {
        Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.40'], 3600);
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

function consentStory(Profile $author, bool $canReply, bool $canReact): Story
{
    $story = new Story;
    $story->profile_id = $author->id;
    $story->type = 'photo';
    $story->duration = 3;
    $story->path = 'public/story/'.uniqid().'.jpg';
    $story->local = true;
    $story->active = true;
    $story->public = false;
    $story->view_count = 0;
    $story->can_reply = $canReply;
    $story->can_react = $canReact;
    $story->expires_at = now()->addHours(12);
    $story->save();

    return $story;
}

function consentFollow(Profile $actor, Profile $author): void
{
    Follower::create([
        'profile_id' => $actor->id,
        'following_id' => $author->id,
    ]);
}

function consentDeliver(Profile $actor, Profile $author, Story $story, string $type, string $path): string
{
    $id = $actor->remote_url.'/statuses/'.$path;

    $payload = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $id.'/activity',
        'type' => $type,
        'actor' => $actor->remote_url,
        'to' => $author->permalink(),
        'inReplyTo' => $story->url(),
        'content' => '<p>hi</p>',
    ];

    $headers = [
        'signature' => ['keyId="'.$actor->key_id.'",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="dGVzdA=="'],
        'date' => [now()->toRfc7231String()],
    ];

    (new Inbox($headers, null, $payload))->handle();

    return $id;
}

it('rejects a reply but accepts a reaction when replies are off', function () {
    $author = consentLocalUser();
    $bob = consentRemoteProfile();
    consentSeedHosts();
    consentFollow($bob, $author->profile);

    $story = consentStory($author->profile, canReply: false, canReact: true);

    $replyId = consentDeliver($bob, $author->profile, $story, 'Story:Reply', '1');
    expect(Status::whereObjectUrl($replyId)->where('type', 'story:reply')->exists())->toBeFalse();
    expect(DmMessage::count())->toBe(0);

    $reactId = consentDeliver($bob, $author->profile, $story, 'Story:Reaction', '2');
    expect(Status::whereObjectUrl($reactId)->where('type', 'story:reaction')->exists())->toBeTrue();
    expect(DmMessage::where('type', 'story:react')->count())->toBe(1);
});

it('rejects a reaction but accepts a reply when reactions are off', function () {
    $author = consentLocalUser();
    $bob = consentRemoteProfile();
    consentSeedHosts();
    consentFollow($bob, $author->profile);

    $story = consentStory($author->profile, canReply: true, canReact: false);

    $reactId = consentDeliver($bob, $author->profile, $story, 'Story:Reaction', '3');
    expect(Status::whereObjectUrl($reactId)->where('type', 'story:reaction')->exists())->toBeFalse();
    expect(DmMessage::count())->toBe(0);

    $replyId = consentDeliver($bob, $author->profile, $story, 'Story:Reply', '4');
    expect(Status::whereObjectUrl($replyId)->where('type', 'story:reply')->exists())->toBeTrue();
    expect(DmMessage::where('type', 'story:comment')->count())->toBe(1);
});

it('accepts both a reply and a reaction when both are allowed', function () {
    $author = consentLocalUser();
    $bob = consentRemoteProfile();
    consentSeedHosts();
    consentFollow($bob, $author->profile);

    $story = consentStory($author->profile, canReply: true, canReact: true);

    $replyId = consentDeliver($bob, $author->profile, $story, 'Story:Reply', '5');
    expect(Status::whereObjectUrl($replyId)->where('type', 'story:reply')->exists())->toBeTrue();

    $reactId = consentDeliver($bob, $author->profile, $story, 'Story:Reaction', '6');
    expect(Status::whereObjectUrl($reactId)->where('type', 'story:reaction')->exists())->toBeTrue();

    expect(DmMessage::where('type', 'story:comment')->count())->toBe(1);
    expect(DmMessage::where('type', 'story:react')->count())->toBe(1);
});
