<?php

use App\Jobs\InboxPipeline\ActivityHandler;
use App\Jobs\InboxPipeline\InboxValidator;
use App\Jobs\InboxPipeline\InboxWorker;
use App\Jobs\QuotePipeline\DeliverQuoteActivityPipeline;
use App\Models\Profile;
use App\Models\QuoteAuthorization;
use App\Models\Status;
use App\Models\User;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| First contact with a remote actor
|--------------------------------------------------------------------------
|
| The inbox endpoints answer 2xx before the signature is verified, so the
| sender never redelivers. When the signing actor is unknown and cannot be
| fetched for a temporary reason, the inbox job has to try again itself.
|
| A FEP-044f QuoteRequest is used as the activity because it is the type
| where a dropped first contact hurts most (the quote stays pending on the
| other server forever), but the retry applies to every activity type.
|
*/

beforeEach(function () {
    Redis::spy();
    Queue::fake();

    config([
        'instance.enable_cc' => false,
        'federation.activitypub.enabled' => true,
    ]);
});

function fcLocalUser(): User
{
    $user = User::factory()->create();
    $user->refresh();

    return $user;
}

function fcLocalStatus(Profile $profile): Status
{
    return Status::factory()->photo()->create(['profile_id' => $profile->id]);
}

/**
 * Seed the DNS and banned-domain caches so URL validation passes without a
 * network lookup. Call after factories, the lazy refresh can flush the cache.
 */
function fcSeedHosts(array $hosts = ['remote.example']): void
{
    $hosts[] = config('pixelfed.domain.app');

    foreach ($hosts as $host) {
        Cache::put(
            'helpers:url:public-ips:v2:'.hash('xxh128', $host),
            ['state' => Helpers::URL_OK, 'ips' => ['203.0.113.40']],
            3600
        );
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

function fcInProduction(callable $fn): mixed
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

function fcSentOfType(string $type): array
{
    return Queue::pushed(DeliverQuoteActivityPipeline::class)
        ->map(fn (DeliverQuoteActivityPipeline $job) => $job->activity())
        ->filter(fn ($activity) => $activity['type'] === $type)
        ->values()
        ->all();
}

/**
 * A remote actor Pixelfed has never seen, with a real key pair so requests
 * can be signed and verified end to end.
 */
function fcStranger(string $domain = 'remote.example', string $username = 'alice'): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $actor = "https://{$domain}/users/{$username}";

    return [
        'key' => $key,
        'actor' => $actor,
        'document' => [
            '@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1'],
            'id' => $actor,
            'type' => 'Person',
            'following' => $actor.'/following',
            'followers' => $actor.'/followers',
            'inbox' => $actor.'/inbox',
            'outbox' => $actor.'/outbox',
            'preferredUsername' => $username,
            'name' => ucfirst($username),
            'summary' => '',
            'url' => "https://{$domain}/@{$username}",
            'manuallyApprovesFollowers' => false,
            'publicKey' => [
                'id' => $actor.'#main-key',
                'owner' => $actor,
                'publicKeyPem' => openssl_pkey_get_details($key)['key'],
            ],
            'endpoints' => ['sharedInbox' => "https://{$domain}/inbox"],
        ],
    ];
}

/**
 * Fake the stranger's actor endpoint. $failures is a list of responses to
 * serve before the real document, 'connection' simulates a network error.
 */
function fcFakeStranger(array $stranger, array $failures = []): object
{
    $state = new stdClass;
    $state->fetches = 0;

    Http::fake([
        parse_url($stranger['actor'], PHP_URL_HOST).'/users/*' => function () use ($state, $stranger, &$failures) {
            $state->fetches++;

            $failure = array_shift($failures);

            if ($failure === 'connection') {
                throw new ConnectionException('timed out');
            }

            if (is_int($failure)) {
                return Http::response('nope', $failure);
            }

            // A string body, Http::response() forces application/json onto arrays
            return Http::response(json_encode($stranger['document']), 200, [
                'Content-Type' => 'application/activity+json',
            ]);
        },
        '*' => Http::response('', 202),
    ]);

    return $state;
}

/**
 * What Mastodon sends: the quote post inlined as `instrument`, delivered
 * to the quoted account's own inbox.
 */
function fcMastodonRequest(array $stranger, Status $status): string
{
    $quoteUrl = $stranger['actor'].'/statuses/115221849202938471';

    return json_encode([
        '@context' => [
            'https://www.w3.org/ns/activitystreams',
            ['QuoteRequest' => 'https://w3id.org/fep/044f#QuoteRequest'],
        ],
        'id' => $stranger['actor'].'/quote_requests/0c0f6c1e-5f0b-4c55-9a53-0d6c6f0b7a11',
        'type' => 'QuoteRequest',
        'actor' => $stranger['actor'],
        'object' => $status->url(),
        'instrument' => [
            'id' => $quoteUrl,
            'type' => 'Note',
            'summary' => null,
            'inReplyTo' => null,
            'published' => now()->toIso8601String(),
            'url' => str_replace('/users/', '/@', $stranger['actor']).'/115221849202938471',
            'attributedTo' => $stranger['actor'],
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'cc' => [$stranger['actor'].'/followers'],
            'sensitive' => false,
            'content' => '<p>nice shot</p>',
            'contentMap' => ['en' => '<p>nice shot</p>'],
            'attachment' => [],
            'tag' => [],
            'replies' => [
                'id' => $quoteUrl.'/replies',
                'type' => 'Collection',
                'first' => ['type' => 'CollectionPage', 'partOf' => $quoteUrl.'/replies', 'items' => []],
            ],
            'quote' => $status->url(),
            '_misskey_quote' => $status->url(),
            'quoteUri' => $status->url(),
            'interactionPolicy' => ['canQuote' => ['automaticApproval' => ['https://www.w3.org/ns/activitystreams#Public']]],
        ],
    ], JSON_UNESCAPED_SLASHES);
}

function fcSignedHeaders(array $stranger, string $path, string $body): array
{
    $host = config('pixelfed.domain.app');
    $date = now()->toRfc7231String();
    $digest = 'SHA-256='.base64_encode(hash('sha256', $body, true));

    openssl_sign(
        "(request-target): post {$path}\nhost: {$host}\ndate: {$date}\ndigest: {$digest}",
        $signature,
        $stranger['key'],
        OPENSSL_ALGO_SHA256
    );

    return [
        'host' => [$host],
        'date' => [$date],
        'digest' => [$digest],
        'content-type' => ['application/activity+json'],
        'signature' => ['keyId="'.$stranger['actor'].'#main-key",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="'.base64_encode($signature).'"'],
    ];
}

/**
 * Run whatever the inbox job handed to ActivityHandler, the queue is faked.
 */
function fcRunHandlers(): void
{
    Queue::pushed(ActivityHandler::class)->each(fn ($job) => fcInProduction(fn () => $job->handle()));
}

describe('first contact', function () {
    it('answers a signed Mastodon request from an account it has never seen, via the user inbox', function () {
        $user = fcLocalUser();
        $status = fcLocalStatus($user->profile);
        $alice = fcStranger();
        fcFakeStranger($alice);
        fcSeedHosts();

        $body = fcMastodonRequest($alice, $status);
        $headers = fcSignedHeaders($alice, "/users/{$user->profile->username}/inbox", $body);

        expect(Profile::whereRemoteUrl($alice['actor'])->exists())->toBeFalse();

        fcInProduction(fn () => (new InboxValidator($user->profile->username, $headers, $body))->handle());
        fcRunHandlers();

        $accept = fcSentOfType('Accept');

        expect(Profile::whereRemoteUrl($alice['actor'])->exists())->toBeTrue()
            ->and(QuoteAuthorization::approved()->count())->toBe(1)
            ->and($accept)->toHaveCount(1)
            ->and($accept[0]['object']['id'])->toBe(json_decode($body, true)['id'])
            ->and($accept[0]['result'])->toBe(QuoteAuthorization::first()->permalink());
    });

    it('answers the same request via the shared inbox', function () {
        $user = fcLocalUser();
        $status = fcLocalStatus($user->profile);
        $alice = fcStranger();
        fcFakeStranger($alice);
        fcSeedHosts();

        $body = fcMastodonRequest($alice, $status);
        $headers = fcSignedHeaders($alice, '/f/inbox', $body);

        fcInProduction(fn () => (new InboxWorker($headers, $body))->handle());
        fcRunHandlers();

        expect(QuoteAuthorization::approved()->count())->toBe(1)
            ->and(fcSentOfType('Accept'))->toHaveCount(1);
    });

    it('releases the job instead of dropping it when the actor is temporarily unreachable', function (mixed $failure) {
        $user = fcLocalUser();
        $status = fcLocalStatus($user->profile);
        $alice = fcStranger();
        // retry(2) inside the fetch service means one fetch is two requests
        $remote = fcFakeStranger($alice, [$failure, $failure]);
        fcSeedHosts();

        $body = fcMastodonRequest($alice, $status);
        $headers = fcSignedHeaders($alice, "/users/{$user->profile->username}/inbox", $body);

        $job = (new InboxValidator($user->profile->username, $headers, $body))->withFakeQueueInteractions();
        fcInProduction(fn () => $job->handle());

        $job->assertReleased(delay: 180);

        expect(QuoteAuthorization::count())->toBe(0)
            ->and(Queue::pushed(DeliverQuoteActivityPipeline::class))->toBeEmpty();

        // The retry runs once the cached failure has lapsed
        $this->travel(Helpers::FETCH_NEGATIVE_TTL + 1)->seconds();

        $retry = (new InboxValidator($user->profile->username, $headers, $body))->withFakeQueueInteractions();
        fcInProduction(fn () => $retry->handle());
        fcRunHandlers();

        $retry->assertNotReleased();

        expect($remote->fetches)->toBe(3)
            ->and(QuoteAuthorization::approved()->count())->toBe(1)
            ->and(fcSentOfType('Accept'))->toHaveCount(1);
    })->with([
        '503' => [503],
        '429' => [429],
        'connection error' => ['connection'],
    ]);

    it('also releases from the shared inbox', function () {
        $user = fcLocalUser();
        $status = fcLocalStatus($user->profile);
        $alice = fcStranger();
        fcFakeStranger($alice, [503, 503]);
        fcSeedHosts();

        $body = fcMastodonRequest($alice, $status);

        $job = (new InboxWorker(fcSignedHeaders($alice, '/f/inbox', $body), $body))->withFakeQueueInteractions();
        fcInProduction(fn () => $job->handle());

        $job->assertReleased(delay: 180);
    });

    it('still drops at once when the actor is a definite no', function (int $status) {
        $user = fcLocalUser();
        $post = fcLocalStatus($user->profile);
        $alice = fcStranger();
        $remote = fcFakeStranger($alice, [$status, $status, $status, $status]);
        fcSeedHosts();

        $body = fcMastodonRequest($alice, $post);
        $headers = fcSignedHeaders($alice, "/users/{$user->profile->username}/inbox", $body);

        $job = (new InboxValidator($user->profile->username, $headers, $body))->withFakeQueueInteractions();
        fcInProduction(fn () => $job->handle());

        $job->assertNotReleased();

        $fetches = $remote->fetches;

        // A burst from the same actor is absorbed by the cached failure
        $again = (new InboxValidator($user->profile->username, $headers, $body))->withFakeQueueInteractions();
        fcInProduction(fn () => $again->handle());

        $again->assertNotReleased();

        expect($remote->fetches)->toBe($fetches);
    })->with([401, 403, 404, 410]);

    it('does not release for a bad signature', function () {
        $user = fcLocalUser();
        $status = fcLocalStatus($user->profile);
        $alice = fcStranger();
        $mallory = fcStranger('remote.example', 'mallory');
        fcFakeStranger($alice);
        fcSeedHosts();

        $body = fcMastodonRequest($alice, $status);
        $headers = fcSignedHeaders($alice, "/users/{$user->profile->username}/inbox", $body);
        // Signed with someone else's key
        $forged = fcSignedHeaders(['key' => $mallory['key'], 'actor' => $alice['actor']], "/users/{$user->profile->username}/inbox", $body);

        $job = (new InboxValidator($user->profile->username, $forged, $body))->withFakeQueueInteractions();
        fcInProduction(fn () => $job->handle());

        $job->assertNotReleased();

        expect(Queue::pushed(ActivityHandler::class))->toBeEmpty()
            ->and($headers)->not->toBe($forged);
    });

    it('stops retrying once the delays are used up', function () {
        $user = fcLocalUser();
        $status = fcLocalStatus($user->profile);
        $alice = fcStranger();
        fcFakeStranger($alice, array_fill(0, 20, 503));
        fcSeedHosts();

        $body = fcMastodonRequest($alice, $status);
        $headers = fcSignedHeaders($alice, "/users/{$user->profile->username}/inbox", $body);

        expect((new InboxValidator($user->profile->username, $headers, $body))->tries)
            ->toBe(count(InboxValidator::ACTOR_RETRY_DELAYS) + 1)
            ->and((new InboxWorker($headers, $body))->tries)
            ->toBe(count(InboxWorker::ACTOR_RETRY_DELAYS) + 1)
            ->and(min(InboxValidator::ACTOR_RETRY_DELAYS))
            ->toBeGreaterThan(Helpers::FETCH_NEGATIVE_TTL);
    });
});
