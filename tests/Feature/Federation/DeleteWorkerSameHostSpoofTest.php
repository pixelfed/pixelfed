<?php

use App\Jobs\DeletePipeline\DeleteRemoteProfilePipeline;
use App\Jobs\InboxPipeline\ActivityHandler;
use App\Jobs\InboxPipeline\DeleteWorker;
use App\Jobs\StoryPipeline\StoryExpire;
use App\Models\Profile;
use App\Models\Story;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);
});

function dwsActor(string $username): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $url = "https://remote.example/users/{$username}";

    $profile = Profile::factory()->remote()->create([
        'domain' => 'remote.example',
        'username' => "@{$username}@remote.example",
        'remote_url' => $url,
        'inbox_url' => $url.'/inbox',
        'sharedInbox' => 'https://remote.example/inbox',
        'key_id' => $url.'#main-key',
        'public_key' => openssl_pkey_get_details($key)['key'],
        'last_fetched_at' => now(),
    ]);

    return ['key' => $key, 'url' => $url, 'profile' => $profile];
}

function dwsSeedHosts(): void
{
    foreach (['remote.example', config('pixelfed.domain.app')] as $host) {
        Cache::put(
            'helpers:url:public-ips:v2:'.hash('xxh128', $host),
            ['state' => Helpers::URL_OK, 'ips' => ['203.0.113.40']],
            3600
        );
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

function dwsInProduction(callable $fn): mixed
{
    $app = app();
    $previous = $app['env'];
    $app['env'] = 'production';

    try {
        return $fn();
    } finally {
        $app['env'] = $previous;
    }
}

function dwsSignedHeaders(array $signer, string $body): array
{
    $host = config('pixelfed.domain.app');
    $date = now()->toRfc7231String();
    $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));

    openssl_sign(
        "(request-target): post /f/inbox\nhost: {$host}\ndate: {$date}\ndigest: {$digest}",
        $signature,
        $signer['key'],
        OPENSSL_ALGO_SHA256
    );

    return [
        'host' => [$host],
        'date' => [$date],
        'digest' => [$digest],
        'content-type' => ['application/activity+json'],
        'signature' => ['keyId="'.$signer['url'].'#main-key",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="'.base64_encode($signature).'"'],
    ];
}

function dwsDelete(string $actor, mixed $object): string
{
    return json_encode([
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $actor.'#delete/'.uniqid(),
        'type' => 'Delete',
        'actor' => $actor,
        'object' => $object,
    ], JSON_UNESCAPED_SLASHES);
}

function dwsRun(array $signer, string $body): void
{
    dwsSeedHosts();
    $headers = dwsSignedHeaders($signer, $body);

    dwsInProduction(fn () => (new DeleteWorker($headers, $body))->handle());
}

function dwsDeletedProfileIds(): array
{
    return Queue::pushed(DeleteRemoteProfilePipeline::class)
        ->map(fn ($job) => (fn () => $this->profile->id)->call($job))
        ->values()
        ->all();
}

function dwsStory(Profile $owner, string $objectId): Story
{
    $story = new Story;
    $story->profile_id = $owner->id;
    $story->type = 'photo';
    $story->duration = 3;
    $story->path = 'https://remote.example/media/story.jpg';
    $story->remote_url = 'https://remote.example/media/story.jpg';
    $story->media_url = 'https://remote.example/media/story.jpg';
    $story->object_id = $objectId;
    $story->local = false;
    $story->expires_at = now()->addHours(12);
    $story->save();

    return $story;
}

function dwsInboxDelete(string $actor, string $objectId): void
{
    $payload = json_decode(dwsDelete($actor, ['id' => $objectId, 'type' => 'Story']), true);

    (new Inbox([], null, $payload))->handle();
}

describe('account deletes', function () {
    it('deletes an account that signs its own Delete', function () {
        $mallory = dwsActor('mallory');

        dwsRun($mallory, dwsDelete($mallory['url'], $mallory['url']));

        expect(dwsDeletedProfileIds())->toBe([$mallory['profile']->id]);
    });

    it('rejects a Delete for another same-host account signed with a valid key', function () {
        $mallory = dwsActor('mallory');
        $victim = dwsActor('victim');

        dwsRun($mallory, dwsDelete($victim['url'], $victim['url']));

        expect(dwsDeletedProfileIds())->toBe([]);
    });

    it('rejects the same forgery with a Person object', function () {
        $mallory = dwsActor('mallory');
        $victim = dwsActor('victim');

        dwsRun($mallory, dwsDelete($victim['url'], ['id' => $victim['url'], 'type' => 'Person']));

        expect(dwsDeletedProfileIds())->toBe([]);
    });

    it('rejects a Delete whose actor is given as an object for someone else', function () {
        $mallory = dwsActor('mallory');
        $victim = dwsActor('victim');

        dwsRun($mallory, json_encode([
            'id' => $mallory['url'].'#delete/obj',
            'type' => 'Delete',
            'actor' => ['id' => $victim['url']],
            'object' => ['id' => $victim['url'], 'type' => 'Person'],
        ], JSON_UNESCAPED_SLASHES));

        expect(dwsDeletedProfileIds())->toBe([]);
        expect(Queue::pushed(ActivityHandler::class))->toBeEmpty();
    });
});

describe('object deletes', function () {
    it('rejects a Tombstone Delete claiming another same-host actor', function () {
        $mallory = dwsActor('mallory');
        $victim = dwsActor('victim');

        dwsRun($mallory, dwsDelete($victim['url'], [
            'id' => $victim['url'].'/statuses/1',
            'type' => 'Tombstone',
        ]));

        expect(Queue::pushed(ActivityHandler::class))->toBeEmpty();
    });

    it('passes a Tombstone Delete signed by its own actor to the handler', function () {
        $victim = dwsActor('victim');

        dwsRun($victim, dwsDelete($victim['url'], [
            'id' => $victim['url'].'/statuses/1',
            'type' => 'Tombstone',
        ]));

        expect(Queue::pushed(ActivityHandler::class))->toHaveCount(1);
    });
});

describe('story deletes', function () {
    it('only expires a story deleted by its owner', function () {
        $mallory = dwsActor('mallory');
        $victim = dwsActor('victim');
        $objectId = $victim['url'].'/stories/1';
        dwsStory($victim['profile'], $objectId);

        dwsInboxDelete($mallory['url'], $objectId);
        expect(Queue::pushed(StoryExpire::class))->toBeEmpty();

        dwsInboxDelete($victim['url'], $objectId);
        expect(Queue::pushed(StoryExpire::class))->toHaveCount(1);
    });
});
