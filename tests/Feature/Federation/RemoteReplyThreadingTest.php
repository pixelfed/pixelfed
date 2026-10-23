<?php

use App\Jobs\StatusPipeline\RemoteReplyResolvePipeline;
use App\Jobs\StatusPipeline\StatusReplyPipeline;
use App\Models\DirectMessage;
use App\Models\Poll;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Models\UserDomainBlock;
use App\Transformer\ActivityPub\Verb\CreateNote;
use App\Transformer\ActivityPub\Verb\Note;
use App\Util\ActivityPub\Helpers;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use League\Fractal;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Remote reply threading
|--------------------------------------------------------------------------
|
| Invariant: a remote object that declares inReplyTo is never stored without
| in_reply_to_id. Every timeline and profile query reads "in_reply_to_id is
| null" as "top-level post", so an unlinked reply breaks the thread and leaks
| a comment into feeds.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();
    Http::fake();

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);
});

function replyLocalUser(): User
{
    $user = User::factory()->create();
    $user->refresh();

    return $user;
}

function replyRemoteProfile(string $domain = 'remote.example', string $username = 'bob'): Profile
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

/**
 * Seed the DNS and banned-domain caches so URL validation passes without a
 * network lookup. Call after factories, the lazy refresh can flush the cache.
 */
function replySeedHosts(array $hosts = ['remote.example', 'other.example']): void
{
    $hosts[] = config('pixelfed.domain.app');

    foreach ($hosts as $host) {
        Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.40'], 3600);
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

/**
 * Make $url dereference to $object, or to a failed fetch when $object is
 * false, by seeding the cache fetchFromUrl() reads.
 */
function replySeedFetch(string $url, array|false $object): void
{
    Cache::put(Helpers::fetchCacheKey($url), $object, 600);
}

function replyNote(Profile $author, string $path, ?string $inReplyTo, array $overrides = []): array
{
    $id = $author->remote_url.'/statuses/'.$path;

    return array_merge([
        'id' => $id,
        'type' => 'Note',
        'attributedTo' => $author->remote_url,
        'url' => "https://{$author->domain}/@user/{$path}",
        'content' => '<p>hello</p>',
        'published' => now()->subMinute()->toAtomString(),
        'inReplyTo' => $inReplyTo,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
        'cc' => [$author->remote_url.'/followers'],
    ], $overrides);
}

function replyDeliver(Profile $actor, array $object, ?Profile $signer = null): void
{
    $payload = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $object['id'].'/activity',
        'type' => 'Create',
        'actor' => $actor->remote_url,
        'object' => $object,
    ];

    $headers = [
        'signature' => ['keyId="'.($signer ?? $actor)->key_id.'",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="dGVzdA=="'],
        'date' => [now()->toRfc7231String()],
    ];

    (new Inbox($headers, null, $payload))->handle();
}

describe('storing', function () {
    it('links a delivered reply to a local parent and records the parent author', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create(['profile_id' => $local->profile_id]);
        $bob = replyRemoteProfile();
        replySeedHosts();

        replyDeliver($bob, replyNote($bob, '1', $parent->url()));

        $reply = Status::whereObjectUrl($bob->remote_url.'/statuses/1')->first();

        expect($reply)->not->toBeNull();
        expect((int) $reply->in_reply_to_id)->toBe((int) $parent->id);
        expect((int) $reply->in_reply_to_profile_id)->toBe((int) $local->profile_id);

        Queue::assertPushed(StatusReplyPipeline::class, 1);
        Queue::assertNotPushed(RemoteReplyResolvePipeline::class);
    });

    it('stores the delivered object without fetching it back', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create(['profile_id' => $local->profile_id]);
        $bob = replyRemoteProfile();
        replySeedHosts();

        // A followers-only reply: the origin would 404 our instance actor.
        $note = replyNote($bob, '2', $parent->url(), [
            'to' => [$bob->remote_url.'/followers'],
            'cc' => [$local->profile->permalink()],
        ]);
        replySeedFetch($note['id'], false);
        replySeedFetch($note['url'], false);

        replyDeliver($bob, $note);

        $reply = Status::whereObjectUrl($note['id'])->first();

        expect($reply)->not->toBeNull();
        expect($reply->scope)->toBe('private');
        expect((int) $reply->in_reply_to_id)->toBe((int) $parent->id);
        Http::assertNothingSent();
    });

    it('never stores a reply as a top-level status when the parent is unavailable', function () {
        $bob = replyRemoteProfile();
        replySeedHosts();

        $missing = 'https://other.example/users/alice/statuses/gone';
        replySeedFetch($missing, false);

        $note = replyNote($bob, '3', $missing, [
            'attachment' => [[
                'type' => 'Document',
                'mediaType' => 'image/jpeg',
                'url' => 'https://remote.example/media/1.jpg',
            ]],
        ]);

        expect(Helpers::storeStatus($note['id'], $bob, $note))->toBeNull();
        expect(Status::whereObjectUrl($note['id'])->exists())->toBeFalse();
    });

    it('stores nothing from a chain that runs past the depth limit', function () {
        $alice = replyRemoteProfile('other.example', 'alice');
        $bob = replyRemoteProfile();
        replySeedHosts();

        // c1 <- c2 <- ... <- c8, none of them known locally, c1 replies to
        // something that never resolves. The old code stored the ancestor at
        // the limit as a root with in_reply_to_id null.
        $previous = 'https://other.example/users/alice/statuses/c0';
        replySeedFetch($previous, false);

        foreach (range(1, 8) as $i) {
            $note = replyNote($alice, "c{$i}", $previous);
            replySeedFetch($note['id'], ['@context' => 'https://www.w3.org/ns/activitystreams'] + $note);
            $previous = $note['id'];
        }

        replyDeliver($bob, replyNote($bob, '4', $previous));

        expect(Status::count())->toBe(0);
        Queue::assertPushed(RemoteReplyResolvePipeline::class, 1);
    });

    it('fetches and links an unknown remote parent', function () {
        $alice = replyRemoteProfile('other.example', 'alice');
        $bob = replyRemoteProfile();
        replySeedHosts();

        $root = replyNote($alice, 'root', null);
        replySeedFetch($root['id'], ['@context' => 'https://www.w3.org/ns/activitystreams'] + $root);

        replyDeliver($bob, replyNote($bob, '5', $root['id']));

        $parent = Status::whereObjectUrl($root['id'])->first();
        $reply = Status::whereObjectUrl($bob->remote_url.'/statuses/5')->first();

        expect($parent)->not->toBeNull();
        expect($parent->in_reply_to_id)->toBeNull();
        expect((int) $reply->in_reply_to_id)->toBe((int) $parent->id);
        expect((int) $reply->in_reply_to_profile_id)->toBe((int) $alice->id);
    });

    it('does not count or notify twice when the same reply is stored again', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create(['profile_id' => $local->profile_id]);
        $bob = replyRemoteProfile();
        replySeedHosts();

        $note = replyNote($bob, '6', $parent->url());

        $first = Helpers::storeStatus($note['id'], $bob, $note);
        $second = Helpers::storeStatus($note['id'], $bob, $note);

        expect((int) $second->id)->toBe((int) $first->id);
        Queue::assertPushed(StatusReplyPipeline::class, 1);
    });

    it('returns the existing row when it loses an insert race', function () {
        $bob = replyRemoteProfile();
        replySeedHosts();

        $note = replyNote($bob, '7', null);

        // Same object_url under a different uri: updateOrCreate keys on uri,
        // misses, inserts, and hits the unique index on object_url. This is
        // what a second worker sees when it loses the race.
        $winner = Status::factory()->create([
            'profile_id' => $bob->id,
            'uri' => $note['id'],
            'url' => $note['id'],
            'object_url' => $note['id'],
            'local' => false,
        ]);

        $status = Helpers::createOrUpdateStatus(
            $note['url'], $bob, $note['id'], $note, $note['published'],
            null, false, 'public', false
        );

        expect((int) $status->id)->toBe((int) $winner->id);
        expect(Status::whereObjectUrl($note['id'])->count())->toBe(1);
    });
});

describe('refusals', function () {
    it('drops a reply from a profile the parent author blocks', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create(['profile_id' => $local->profile_id]);
        $bob = replyRemoteProfile();
        replySeedHosts();

        // Redis hands ids back as strings, model keys are integers.
        Redis::shouldReceive('zrevrange')->andReturn([(string) $bob->id]);

        replyDeliver($bob, replyNote($bob, '8', $parent->url()));

        expect(Status::whereObjectUrl($bob->remote_url.'/statuses/8')->exists())->toBeFalse();
        Queue::assertNotPushed(RemoteReplyResolvePipeline::class);
    });

    it('drops a reply from a domain the parent author blocks', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create(['profile_id' => $local->profile_id]);
        $bob = replyRemoteProfile();
        replySeedHosts();

        UserDomainBlock::create(['profile_id' => $local->profile_id, 'domain' => 'remote.example']);

        replyDeliver($bob, replyNote($bob, '9', $parent->url()));

        expect(Status::whereObjectUrl($bob->remote_url.'/statuses/9')->exists())->toBeFalse();
        Queue::assertNotPushed(RemoteReplyResolvePipeline::class);
    });

    it('drops a reply to a local status with comments disabled', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create([
            'profile_id' => $local->profile_id,
            'comments_disabled' => true,
        ]);
        $bob = replyRemoteProfile();
        replySeedHosts();

        replyDeliver($bob, replyNote($bob, '10', $parent->url()));

        expect(Status::whereObjectUrl($bob->remote_url.'/statuses/10')->exists())->toBeFalse();
    });

    it('does not self-fetch a local parent that no longer exists', function () {
        $bob = replyRemoteProfile();
        replySeedHosts();

        $gone = config('app.url').'/p/someone/999999999';

        $result = Helpers::resolveReplyParent(replyNote($bob, '11', $gone), $bob);

        expect($result['state'])->toBe(Helpers::REPLY_PARENT_UNRESOLVED);
        Http::assertNothingSent();
    });

    it('rejects a reply whose inReplyTo cannot be read as a URL', function () {
        $bob = replyRemoteProfile();
        replySeedHosts();

        $result = Helpers::resolveReplyParent(
            replyNote($bob, '12', null, ['inReplyTo' => ['type' => 'Note']]),
            $bob
        );

        expect($result['state'])->toBe(Helpers::REPLY_PARENT_REJECTED);
    });

    it('reads inReplyTo given as a list or an embedded object', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create(['profile_id' => $local->profile_id]);
        $bob = replyRemoteProfile();
        replySeedHosts();

        foreach ([[$parent->url()], ['type' => 'Note', 'id' => $parent->url()]] as $shape) {
            $result = Helpers::resolveReplyParent(
                replyNote($bob, '13', null, ['inReplyTo' => $shape]),
                $bob
            );

            expect($result['state'])->toBe(Helpers::REPLY_PARENT_RESOLVED);
            expect((int) $result['status']->id)->toBe((int) $parent->id);
        }
    });
});

describe('delivery trust', function () {
    it('does not store a delivered object attributed to someone other than the sender', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create(['profile_id' => $local->profile_id]);
        $bob = replyRemoteProfile();
        $mallory = replyRemoteProfile('remote.example', 'mallory');
        replySeedHosts();

        $note = replyNote($bob, '14', $parent->url());
        replySeedFetch($note['id'], false);

        replyDeliver($mallory, $note);

        expect(Status::whereObjectUrl($note['id'])->exists())->toBeFalse();
    });

    it('does not store a delivered object whose id is on another host', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create(['profile_id' => $local->profile_id]);
        $bob = replyRemoteProfile();
        replySeedHosts();

        $note = replyNote($bob, '15', $parent->url(), [
            'id' => 'https://other.example/users/alice/statuses/squat',
            'url' => 'https://other.example/@alice/squat',
        ]);
        replySeedFetch($note['id'], false);

        replyDeliver($bob, $note);

        expect(Status::whereObjectUrl($note['id'])->exists())->toBeFalse();
    });

    it('never stores a poll vote as a comment', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->create(['profile_id' => $local->profile_id, 'type' => 'poll']);
        $bob = replyRemoteProfile();
        replySeedHosts();

        $vote = replyNote($bob, '16', $parent->url(), ['name' => 'Option A']);
        unset($vote['content']);

        replyDeliver($bob, $vote);

        expect(Status::whereObjectUrl($vote['id'])->exists())->toBeFalse();
    });
});

describe('create hardening', function () {
    it('counts a vote addressed only to the poll author instead of treating it as a dm', function () {
        $local = replyLocalUser();
        $bob = replyRemoteProfile();
        replySeedHosts();

        $status = Status::factory()->create(['profile_id' => $local->profile_id, 'type' => 'poll']);

        $poll = new Poll;
        $poll->status_id = $status->id;
        $poll->profile_id = $local->profile_id;
        $poll->poll_options = ['Yes', 'No'];
        $poll->cached_tallies = [0, 0];
        $poll->votes_count = 0;
        $poll->expires_at = now()->addDay();
        $poll->save();

        // Mastodon's vote shape: to the poll author alone, no cc, no content.
        $vote = replyNote($bob, 'vote', $status->url(), [
            'name' => 'No',
            'to' => [$local->profile->permalink()],
            'cc' => [],
        ]);
        unset($vote['content']);

        replyDeliver($bob, $vote);

        expect($poll->fresh()->cached_tallies)->toBe([0, 1]);
        expect(DirectMessage::count())->toBe(0);
        expect(Status::whereObjectUrl($vote['id'])->exists())->toBeFalse();
    });

    it('does not store a delivered post whose id is on another host', function () {
        $bob = replyRemoteProfile();
        replySeedHosts();
        config(['federation.activitypub.ingest.store_notes_without_followers' => true]);

        $note = replyNote($bob, 'squat', null, [
            'id' => 'https://other.example/users/alice/statuses/real',
            'url' => 'https://other.example/@alice/real',
            'attachment' => [[
                'type' => 'Document',
                'mediaType' => 'image/jpeg',
                'url' => 'https://remote.example/media/2.jpg',
            ]],
        ]);
        replySeedFetch($note['id'], false);

        replyDeliver($bob, $note);

        expect(Status::whereObjectUrl($note['id'])->exists())->toBeFalse();
    });

    it('still stores a delivered post from its author', function () {
        $bob = replyRemoteProfile();
        replySeedHosts();
        config(['federation.activitypub.ingest.store_notes_without_followers' => true]);

        $note = replyNote($bob, 'photo', null, [
            'attachment' => [[
                'type' => 'Document',
                'mediaType' => 'image/jpeg',
                'url' => 'https://remote.example/media/3.jpg',
            ]],
        ]);

        replyDeliver($bob, $note);

        $status = Status::whereObjectUrl($note['id'])->first();

        expect($status)->not->toBeNull();
        expect((int) $status->profile_id)->toBe((int) $bob->id);
        expect($status->in_reply_to_id)->toBeNull();
    });

    it('does not hand a status held by another profile to the loser of an insert race', function () {
        $bob = replyRemoteProfile();
        $mallory = replyRemoteProfile('remote.example', 'mallory');
        replySeedHosts();

        $note = replyNote($bob, 'held', null);

        Status::factory()->create([
            'profile_id' => $mallory->id,
            'uri' => $note['id'],
            'url' => $note['id'],
            'object_url' => $note['id'],
            'local' => false,
        ]);

        $status = Helpers::createOrUpdateStatus(
            $note['url'], $bob, $note['id'], $note, $note['published'],
            null, false, 'public', false
        );

        expect($status)->toBeNull();
    });
});

describe('retry', function () {
    it('parks a delivered reply once when the parent is unavailable', function () {
        $bob = replyRemoteProfile();
        replySeedHosts();

        $missing = 'https://other.example/users/alice/statuses/slow';
        replySeedFetch($missing, false);
        $note = replyNote($bob, '17', $missing);

        replyDeliver($bob, $note);
        replyDeliver($bob, $note);

        expect(Status::whereObjectUrl($note['id'])->exists())->toBeFalse();

        Queue::assertPushed(RemoteReplyResolvePipeline::class, 1);
        Queue::assertPushed(
            RemoteReplyResolvePipeline::class,
            fn ($job) => $job->attempt === 0 && $job->queue === 'low' && $job->object['id'] === $note['id']
        );
    });

    it('stores the parked reply once the parent resolves', function () {
        $alice = replyRemoteProfile('other.example', 'alice');
        $bob = replyRemoteProfile();
        replySeedHosts();

        $root = replyNote($alice, 'late', null);
        $note = replyNote($bob, '18', $root['id']);

        replySeedFetch($root['id'], false);
        replyDeliver($bob, $note);
        expect(Status::count())->toBe(0);

        replySeedFetch($root['id'], ['@context' => 'https://www.w3.org/ns/activitystreams'] + $root);
        (new RemoteReplyResolvePipeline($note, (int) $bob->id, 0))->handle();

        $parent = Status::whereObjectUrl($root['id'])->first();
        $reply = Status::whereObjectUrl($note['id'])->first();

        expect($reply)->not->toBeNull();
        expect((int) $reply->in_reply_to_id)->toBe((int) $parent->id);
        expect(Cache::has(RemoteReplyResolvePipeline::pendingKey($note['id'])))->toBeFalse();
    });

    it('schedules the next attempt, then drops the reply after the last one', function () {
        $bob = replyRemoteProfile();
        replySeedHosts();

        $missing = 'https://other.example/users/alice/statuses/never';
        replySeedFetch($missing, false);
        $note = replyNote($bob, '19', $missing);

        (new RemoteReplyResolvePipeline($note, (int) $bob->id, 0))->handle();

        Queue::assertPushed(RemoteReplyResolvePipeline::class, fn ($job) => $job->attempt === 1);

        $last = count(RemoteReplyResolvePipeline::BACKOFF) - 1;
        (new RemoteReplyResolvePipeline($note, (int) $bob->id, $last))->handle();

        Queue::assertPushed(RemoteReplyResolvePipeline::class, 1);
        expect(Status::whereObjectUrl($note['id'])->exists())->toBeFalse();
    });

    it('waits longer than a failed fetch is cached before the first retry', function () {
        expect(RemoteReplyResolvePipeline::BACKOFF[0])->toBeGreaterThan(Helpers::FETCH_NEGATIVE_TTL);
    });

    it('forgets a failed fetch well before it forgets a successful one', function () {
        replySeedHosts();
        $url = 'https://other.example/users/alice/statuses/flaky';

        Http::fake(fn () => Http::response('', 503));
        expect(Helpers::fetchFromUrl($url))->toBeFalse();
        expect(Cache::get(Helpers::fetchCacheKey($url)))->toBeFalse();

        $this->travel(Helpers::FETCH_NEGATIVE_TTL + 1)->seconds();

        expect(Cache::get(Helpers::fetchCacheKey($url)))->toBeNull();
    });
});

describe('outbound inReplyTo', function () {
    it('references a remote parent by its ActivityPub id, not its permalink', function () {
        $local = replyLocalUser();
        $alice = replyRemoteProfile('other.example', 'alice');

        $parent = Status::factory()->create([
            'profile_id' => $alice->id,
            'uri' => 'https://other.example/@alice/111',
            'url' => 'https://other.example/@alice/111',
            'object_url' => 'https://other.example/users/alice/statuses/111',
            'local' => false,
        ]);

        $reply = Status::factory()->create([
            'profile_id' => $local->profile_id,
            'in_reply_to_id' => $parent->id,
            'in_reply_to_profile_id' => $alice->id,
        ]);

        $fractal = new Fractal\Manager;
        $note = $fractal->createData(new Fractal\Resource\Item($reply, new Note))->toArray()['data'];
        $create = $fractal->createData(new Fractal\Resource\Item($reply, new CreateNote))->toArray()['data'];

        expect($note['inReplyTo'])->toBe('https://other.example/users/alice/statuses/111');
        expect($create['object']['inReplyTo'])->toBe('https://other.example/users/alice/statuses/111');
    });

    it('references a local parent by its status url', function () {
        $local = replyLocalUser();
        $parent = Status::factory()->photo()->create(['profile_id' => $local->profile_id]);
        $reply = Status::factory()->create([
            'profile_id' => $local->profile_id,
            'in_reply_to_id' => $parent->id,
        ]);

        expect($reply->inReplyToUri())->toBe($parent->url());
        expect($parent->inReplyToUri())->toBeNull();
    });
});
