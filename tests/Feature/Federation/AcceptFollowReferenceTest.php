<?php

use App\Jobs\FollowPipeline\FollowPipeline;
use App\Models\Follower;
use App\Models\FollowRequest;
use App\Models\Profile;
use App\Models\User;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    config(['federation.activitypub.enabled' => true]);
});

function acceptRefLocal(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function acceptRefRemote(string $domain = 'remote.example', string $username = 'foo'): Profile
{
    $actor = "https://{$domain}/user/{$username}";

    Cache::put(
        'helpers:url:public-ips:v2:'.hash('xxh128', $domain),
        ['state' => 'ok', 'ips' => ['203.0.113.40']],
        3600
    );

    return Profile::factory()->remote()->create([
        'domain' => $domain,
        'username' => "@{$username}@{$domain}",
        'remote_url' => $actor,
        'inbox_url' => "{$actor}/inbox",
        'sharedInbox' => "https://{$domain}/inbox",
        'key_id' => "{$actor}#main-key",
        'last_fetched_at' => now(),
    ]);
}

function acceptRefPending(Profile $local, Profile $remote): FollowRequest
{
    return FollowRequest::create([
        'follower_id' => $local->id,
        'following_id' => $remote->id,
    ]);
}

function acceptRefActivity(Profile $acceptor, mixed $object): array
{
    return [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $acceptor->remote_url.'/accept/1234',
        'type' => 'Accept',
        'actor' => $acceptor->remote_url,
        'object' => $object,
    ];
}

function acceptRefFollowId(Profile $local, Profile $remote): string
{
    return $local->permalink('#follows/'.$remote->id);
}

function acceptRefFollows(Profile $local, Profile $remote): bool
{
    return Follower::whereProfileId($local->id)->whereFollowingId($remote->id)->exists();
}

it('accepts a follow when the object is the Follow id', function () {
    $local = acceptRefLocal();
    $remote = acceptRefRemote();
    acceptRefPending($local, $remote);

    (new Inbox([], $local, acceptRefActivity($remote, acceptRefFollowId($local, $remote))))->handle();

    expect(acceptRefFollows($local, $remote))->toBeTrue();
    expect(FollowRequest::whereFollowerId($local->id)->whereFollowingId($remote->id)->exists())->toBeFalse();

    Queue::assertPushed(FollowPipeline::class);
});

it('accepts a follow when the object is an embedded Follow with only an id', function () {
    $local = acceptRefLocal();
    $remote = acceptRefRemote();
    acceptRefPending($local, $remote);

    (new Inbox([], $local, acceptRefActivity($remote, [
        'id' => acceptRefFollowId($local, $remote),
        'type' => 'Follow',
    ])))->handle();

    expect(acceptRefFollows($local, $remote))->toBeTrue();
});

it('still accepts a fully embedded Follow', function () {
    $local = acceptRefLocal();
    $remote = acceptRefRemote();
    acceptRefPending($local, $remote);

    (new Inbox([], $local, acceptRefActivity($remote, [
        'id' => acceptRefFollowId($local, $remote),
        'type' => 'Follow',
        'actor' => $local->permalink(),
        'object' => $remote->remote_url,
    ])))->handle();

    expect(acceptRefFollows($local, $remote))->toBeTrue();
});

it('ignores an Accept from someone other than the followed account', function () {
    $local = acceptRefLocal();
    $remote = acceptRefRemote();
    $other = acceptRefRemote('other.example', 'mallory');
    acceptRefPending($local, $remote);

    (new Inbox([], $local, acceptRefActivity($other, acceptRefFollowId($local, $remote))))->handle();

    expect(acceptRefFollows($local, $remote))->toBeFalse();
    expect(FollowRequest::whereFollowerId($local->id)->whereFollowingId($remote->id)->exists())->toBeTrue();
});

it('ignores a Follow id that is not on this server', function () {
    $local = acceptRefLocal();
    $remote = acceptRefRemote();
    acceptRefPending($local, $remote);

    Cache::put(
        'helpers:url:public-ips:v2:'.hash('xxh128', 'elsewhere.example'),
        ['state' => 'ok', 'ips' => ['203.0.113.41']],
        3600
    );

    (new Inbox([], $local, acceptRefActivity(
        $remote,
        'https://elsewhere.example/users/'.$local->username.'#follows/'.$remote->id
    )))->handle();

    expect(acceptRefFollows($local, $remote))->toBeFalse();
});

it('ignores a Follow id that does not match the Pixelfed shape', function (string $suffix) {
    $local = acceptRefLocal();
    $remote = acceptRefRemote();
    acceptRefPending($local, $remote);

    (new Inbox([], $local, acceptRefActivity($remote, $local->permalink($suffix))))->handle();

    expect(acceptRefFollows($local, $remote))->toBeFalse();
})->with([
    'no fragment' => [''],
    'other fragment' => ['#likes/1'],
    'non numeric target' => ['#follows/abc'],
]);

it('does not create a follow without a pending request', function () {
    $local = acceptRefLocal();
    $remote = acceptRefRemote();

    (new Inbox([], $local, acceptRefActivity($remote, acceptRefFollowId($local, $remote))))->handle();

    expect(acceptRefFollows($local, $remote))->toBeFalse();

    Queue::assertNotPushed(FollowPipeline::class);
});
