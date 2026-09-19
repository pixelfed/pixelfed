<?php

use App\Jobs\QuotePipeline\RevokeQuoteAuthorizationPipeline;
use App\Models\Profile;
use App\Models\QuoteAuthorization;
use App\Models\Status;
use App\Models\User;
use App\Models\UserFilter;
use App\Services\QuoteService;
use App\Transformer\ActivityPub\Verb\CreateNote;
use App\Transformer\ActivityPub\Verb\Note;
use App\Util\ActivityPub\Inbox;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;
use League\Fractal;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| FEP-044f: Consent-respecting quote posts (target side)
|--------------------------------------------------------------------------
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

function quoteLocalUser(string $canQuote = 'everyone'): User
{
    $user = User::factory()->create();
    $user->refresh();

    if ($canQuote !== 'everyone') {
        $settings = $user->settings;
        $settings->can_quote = $canQuote;
        $settings->save();
    }

    return $user;
}

function quoteLocalStatus(Profile $profile, array $attributes = []): Status
{
    return Status::factory()->photo()->create(array_merge([
        'profile_id' => $profile->id,
    ], $attributes));
}

function quoteRemoteProfile(string $domain = 'remote.example', string $username = 'bob', array $attributes = []): Profile
{
    $actor = "https://{$domain}/users/{$username}";

    return Profile::factory()->remote()->create(array_merge([
        'domain' => $domain,
        'username' => "@{$username}@{$domain}",
        'remote_url' => $actor,
        'key_id' => "{$actor}#main-key",
        'inbox_url' => "{$actor}/inbox",
        'sharedInbox' => "https://{$domain}/inbox",
        'last_fetched_at' => now(),
    ], $attributes));
}

/**
 * Seed the DNS and banned-domain caches so URL validation passes without a
 * network lookup. Call after factories, the lazy refresh can flush the cache.
 */
function quoteSeedHosts(array $hosts = ['remote.example', 'other.example']): void
{
    $hosts[] = config('pixelfed.domain.app');

    foreach ($hosts as $host) {
        Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.40'], 3600);
    }

    Cache::put('instances:banned:domains', [], 1209600);
}

function quoteInProduction(callable $fn): mixed
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

function quoteRequestPayload(Profile $actor, Status $status, array $overrides = []): array
{
    $quoteUrl = $actor->remote_url.'/statuses/1';

    return array_merge([
        '@context' => [
            'https://www.w3.org/ns/activitystreams',
            ['QuoteRequest' => 'https://w3id.org/fep/044f#QuoteRequest'],
        ],
        'id' => $quoteUrl.'/quote',
        'type' => 'QuoteRequest',
        'actor' => $actor->remote_url,
        'object' => $status->url(),
        'instrument' => $quoteUrl,
    ], $overrides);
}

function quoteDeliver(Profile $actor, array $payload, ?Profile $signer = null): void
{
    $keyId = ($signer ?? $actor)->key_id;

    $headers = [
        'signature' => ['keyId="'.$keyId.'",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="dGVzdA=="'],
        'date' => [now()->toRfc7231String()],
    ];

    quoteInProduction(fn () => (new Inbox($headers, null, $payload))->handle());
}

function quoteSentActivities(): array
{
    return collect(Http::recorded())
        ->filter(fn ($pair) => $pair[0]->method() === 'POST')
        ->map(fn ($pair) => json_decode($pair[0]->body(), true))
        ->filter()
        ->values()
        ->all();
}

function quoteNoteObject(Status $status): array
{
    $fractal = new Fractal\Manager;

    return $fractal->createData(new Fractal\Resource\Item($status, new Note))->toArray()['data'];
}

describe('advertised policy', function () {
    it('advertises public automatic approval by default', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);

        $note = quoteNoteObject($status);

        expect($note['interactionPolicy']['canQuote']['automaticApproval'])
            ->toBe(['https://www.w3.org/ns/activitystreams#Public']);

        $terms = $note['@context'][2];

        expect($terms['canQuote']['@id'])->toBe('gts:canQuote')
            ->and($terms['interactionPolicy']['@id'])->toBe('gts:interactionPolicy')
            ->and($terms['gts'])->toBe('https://gotosocial.org/ns#');
    });

    it('advertises the followers collection for the followers policy', function () {
        $user = quoteLocalUser('followers');
        $status = quoteLocalStatus($user->profile);

        expect(quoteNoteObject($status)['interactionPolicy']['canQuote']['automaticApproval'])
            ->toBe([$user->profile->permalink('/followers')]);
    });

    it('advertises the author alone for the nobody policy', function () {
        $user = quoteLocalUser('nobody');
        $status = quoteLocalStatus($user->profile);

        expect(quoteNoteObject($status)['interactionPolicy']['canQuote']['automaticApproval'])
            ->toBe([$user->profile->permalink()]);
    });

    it('lets a per-post override win over the account default', function () {
        $user = quoteLocalUser('nobody');
        $status = quoteLocalStatus($user->profile, ['quote_policy' => QuoteService::FLAG_PUBLIC]);

        expect(QuoteService::statusPolicy($status))->toBe('everyone');
    });

    it('never lets followers-only posts be quoted, whatever the override says', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile, [
            'scope' => 'private',
            'visibility' => 'private',
            'quote_policy' => QuoteService::FLAG_PUBLIC,
        ]);

        expect(QuoteService::statusPolicy($status))->toBe('nobody')
            ->and(quoteNoteObject($status)['interactionPolicy']['canQuote']['automaticApproval'])
            ->toBe([$user->profile->permalink()]);
    });

    it('keeps an override of nobody apart from having no override', function () {
        $user = quoteLocalUser();

        $inherits = quoteLocalStatus($user->profile);
        $nobody = quoteLocalStatus($user->profile, ['quote_policy' => QuoteService::FLAGS_NOBODY]);

        expect($inherits->fresh()->quote_policy)->toBeNull()
            ->and($nobody->fresh()->quote_policy)->toBe(0)
            ->and(QuoteService::statusPolicy($inherits->fresh()))->toBe('everyone')
            ->and(QuoteService::statusPolicy($nobody->fresh()))->toBe('nobody')
            ->and(QuoteService::fromApiPolicy('nobody'))->toBe(0)
            ->and(QuoteService::fromApiPolicy(null))->toBeNull()
            ->and(QuoteService::fromApiPolicy('bogus'))->toBeNull();
    });

    it('advertises every audience in a combined bitmask', function () {
        $user = quoteLocalUser('nobody');
        $profile = $user->profile;

        $status = quoteLocalStatus($profile, [
            'quote_policy' => QuoteService::FLAG_FOLLOWERS
                | QuoteService::FLAG_FOLLOWING
                | (QuoteService::FLAG_PUBLIC << QuoteService::MANUAL_SHIFT),
        ]);

        $policy = quoteNoteObject($status)['interactionPolicy']['canQuote'];

        expect($policy['automaticApproval'])->toBe([
            $profile->permalink('/followers'),
            $profile->permalink('/following'),
        ])->and($policy['manualApproval'])->toBe([
            'https://www.w3.org/ns/activitystreams#Public',
        ])->and(QuoteService::statusPolicy($status))->toBe('followers');
    });

    it('leaves manualApproval out when nothing needs review', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);

        expect(quoteNoteObject($status)['interactionPolicy']['canQuote'])
            ->not->toHaveKey('manualApproval');
    });

    it('puts the policy on the object of a Create', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);

        $fractal = new Fractal\Manager;
        $create = $fractal->createData(new Fractal\Resource\Item($status, new CreateNote))->toArray()['data'];

        expect($create['object']['interactionPolicy']['canQuote']['automaticApproval'])
            ->toBe(['https://www.w3.org/ns/activitystreams#Public'])
            ->and($create['@context'][2])->toHaveKey('canQuote');
    });
});

describe('QuoteRequest', function () {
    it('accepts with a stamp when the policy allows it', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        $payload = quoteRequestPayload($bob, $status);
        quoteDeliver($bob, $payload);

        $auth = QuoteAuthorization::first();

        expect($auth)->not->toBeNull()
            ->and($auth->isApproved())->toBeTrue()
            ->and((int) $auth->status_id)->toBe((int) $status->id)
            ->and((int) $auth->actor_id)->toBe((int) $bob->id)
            ->and($auth->quote_url)->toBe($payload['instrument']);

        $sent = quoteSentActivities();

        expect($sent)->toHaveCount(1);

        $accept = $sent[0];

        expect($accept['type'])->toBe('Accept')
            ->and($accept['actor'])->toBe($user->profile->permalink())
            ->and($accept['to'])->toBe($bob->remote_url)
            ->and($accept['result'])->toBe($auth->permalink())
            ->and($accept['object']['type'])->toBe('QuoteRequest')
            ->and($accept['object']['id'])->toBe($payload['id'])
            ->and($accept['object']['actor'])->toBe($bob->remote_url)
            ->and($accept['object']['object'])->toBe($status->url())
            ->and($accept['object']['instrument'])->toBe($payload['instrument']);

        Http::assertSent(fn ($request) => $request->url() === 'https://remote.example/inbox');
    });

    it('rejects when the account policy is nobody', function () {
        $user = quoteLocalUser('nobody');
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status));

        $sent = quoteSentActivities();

        expect(QuoteAuthorization::count())->toBe(0)
            ->and($sent)->toHaveCount(1)
            ->and($sent[0]['type'])->toBe('Reject')
            ->and($sent[0]['object']['type'])->toBe('QuoteRequest')
            ->and($sent[0])->not->toHaveKey('result');
    });

    it('honours a per-post nobody override on an otherwise open account', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile, ['quote_policy' => QuoteService::FLAGS_NOBODY]);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status));

        expect(QuoteAuthorization::count())->toBe(0)
            ->and(quoteSentActivities()[0]['type'])->toBe('Reject');
    });

    it('only accepts followers under the followers policy', function () {
        $user = quoteLocalUser('followers');
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile('remote.example', 'bob');
        $carol = quoteRemoteProfile('remote.example', 'carol');

        DB::table('followers')->insert([
            'profile_id' => $carol->id,
            'following_id' => $user->profile->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status));
        quoteDeliver($carol, quoteRequestPayload($carol, $status));

        $sent = quoteSentActivities();

        expect($sent[0]['type'])->toBe('Reject')
            ->and($sent[1]['type'])->toBe('Accept')
            ->and(QuoteAuthorization::count())->toBe(1)
            ->and((int) QuoteAuthorization::first()->actor_id)->toBe((int) $carol->id);
    });

    it('accepts accounts the author follows under a following bitmask', function () {
        $user = quoteLocalUser('nobody');
        $status = quoteLocalStatus($user->profile, ['quote_policy' => QuoteService::FLAG_FOLLOWING]);
        $bob = quoteRemoteProfile('remote.example', 'bob');
        $carol = quoteRemoteProfile('remote.example', 'carol');

        DB::table('followers')->insert([
            'profile_id' => $user->profile->id,
            'following_id' => $carol->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status));
        quoteDeliver($carol, quoteRequestPayload($carol, $status));

        $sent = quoteSentActivities();

        expect($sent[0]['type'])->toBe('Reject')
            ->and($sent[1]['type'])->toBe('Accept');
    });

    it('does not act on audiences that only have manual approval', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile, [
            'quote_policy' => QuoteService::FLAG_PUBLIC << QuoteService::MANUAL_SHIFT,
        ]);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status));

        expect(QuoteAuthorization::count())->toBe(0)
            ->and(quoteSentActivities()[0]['type'])->toBe('Reject');
    });

    it('rejects an actor the author has blocked', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();

        UserFilter::create([
            'user_id' => $user->profile->id,
            'filterable_id' => $bob->id,
            'filterable_type' => Profile::class,
            'filter_type' => 'block',
        ]);

        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status));

        expect(QuoteAuthorization::count())->toBe(0)
            ->and(quoteSentActivities()[0]['type'])->toBe('Reject');
    });

    it('ignores requests for followers-only posts without answering', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile, ['scope' => 'private', 'visibility' => 'private']);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status));

        expect(QuoteAuthorization::count())->toBe(0);
        expect(quoteSentActivities())->toBeEmpty();
    });

    it('ignores a quote post hosted somewhere other than the requester', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status, [
            'instrument' => 'https://other.example/users/mallory/statuses/9',
        ]));

        expect(QuoteAuthorization::count())->toBe(0);
        expect(quoteSentActivities())->toBeEmpty();
    });

    it('ignores a request signed by a different actor than it claims', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile('remote.example', 'bob');
        $mallory = quoteRemoteProfile('remote.example', 'mallory');
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status), $mallory);

        expect(QuoteAuthorization::count())->toBe(0);
        expect(quoteSentActivities())->toBeEmpty();
    });

    it('accepts an inlined quote post whose id is not its first property', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        $quoteUrl = $bob->remote_url.'/statuses/1';

        quoteDeliver($bob, quoteRequestPayload($bob, $status, [
            'instrument' => [
                'type' => 'Note',
                'id' => $quoteUrl,
                'attributedTo' => $bob->remote_url,
                'content' => 'look at this',
                'quote' => $status->url(),
                'quoteUrl' => $status->url(),
                '_misskey_quote' => $status->url(),
            ],
        ]));

        expect(QuoteAuthorization::first()?->quote_url)->toBe($quoteUrl)
            ->and(quoteSentActivities()[0]['type'])->toBe('Accept');
    });

    it('rejects an inlined quote post written by someone else', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status, [
            'instrument' => [
                'type' => 'Note',
                'id' => $bob->remote_url.'/statuses/1',
                'attributedTo' => 'https://remote.example/users/carol',
                'quote' => $status->url(),
            ],
        ]));

        expect(QuoteAuthorization::count())->toBe(0)
            ->and(quoteSentActivities()[0]['type'])->toBe('Reject');
    });

    it('rejects an inlined quote post that quotes a different post', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $other = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status, [
            'instrument' => [
                'type' => 'Note',
                'id' => $bob->remote_url.'/statuses/1',
                'attributedTo' => $bob->remote_url,
                'quote' => $other->url(),
            ],
        ]));

        expect(QuoteAuthorization::count())->toBe(0)
            ->and(quoteSentActivities()[0]['type'])->toBe('Reject');
    });

    it('re-sends the same stamp for a repeated request', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status));
        quoteDeliver($bob, quoteRequestPayload($bob, $status, ['id' => $bob->remote_url.'/statuses/1/quote-again']));

        $sent = quoteSentActivities();

        expect(QuoteAuthorization::count())->toBe(1)
            ->and($sent)->toHaveCount(2)
            ->and($sent[1]['type'])->toBe('Accept')
            ->and($sent[1]['result'])->toBe($sent[0]['result']);
    });

    it('keeps rejecting a quote whose stamp was revoked', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        quoteDeliver($bob, quoteRequestPayload($bob, $status));
        QuoteService::revoke(QuoteAuthorization::first());
        quoteDeliver($bob, quoteRequestPayload($bob, $status));

        $sent = quoteSentActivities();

        expect($sent[1]['type'])->toBe('Reject')
            ->and(QuoteAuthorization::approved()->count())->toBe(0);
    });
});

describe('stamp', function () {
    it('serves a QuoteAuthorization that satisfies the FEP verification rules', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        $quoteUrl = $bob->remote_url.'/statuses/1';

        $auth = QuoteService::authorize($status, $bob, $quoteUrl);

        $this->get("/users/{$user->profile->username}/quote_authorizations/{$auth->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/activity+json')
            ->assertJson([
                'id' => $auth->permalink(),
                'type' => 'QuoteAuthorization',
                'attributedTo' => $user->profile->permalink(),
                'interactingObject' => $quoteUrl,
                'interactionTarget' => $status->url(),
            ]);

        expect(parse_url($auth->permalink(), PHP_URL_HOST))
            ->toBe(parse_url($user->profile->permalink(), PHP_URL_HOST));
    });

    it('is gone once revoked and unknown under another username', function () {
        $user = quoteLocalUser();
        $stranger = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();

        $auth = QuoteService::authorize($status, $bob, $bob->remote_url.'/statuses/1');

        $this->get("/users/{$stranger->profile->username}/quote_authorizations/{$auth->id}")
            ->assertNotFound();

        QuoteService::revoke($auth);

        $this->get("/users/{$user->profile->username}/quote_authorizations/{$auth->id}")
            ->assertStatus(410);
    });
});

describe('revocation', function () {
    it('queues a Delete that references the stamp without inlining either post', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();
        quoteSeedHosts();

        $auth = QuoteService::authorize($status, $bob, $bob->remote_url.'/statuses/1');

        QuoteService::revoke($auth);

        Queue::assertPushed(RevokeQuoteAuthorizationPipeline::class, 1);

        quoteInProduction(fn () => (new RevokeQuoteAuthorizationPipeline($auth->id))->handle());

        $delete = quoteSentActivities()[0];

        expect($delete['type'])->toBe('Delete')
            ->and($delete['actor'])->toBe($user->profile->permalink())
            ->and($delete['object']['id'])->toBe($auth->permalink())
            ->and($delete['object']['type'])->toBe('QuoteAuthorization')
            ->and($delete['object']['interactingObject'])->toBeString()
            ->and($delete['object']['interactionTarget'])->toBeString();
    });

    it('revokes every stamp issued to an actor when the author blocks them', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile('remote.example', 'bob');
        $carol = quoteRemoteProfile('remote.example', 'carol');

        QuoteService::authorize($status, $bob, $bob->remote_url.'/statuses/1');
        QuoteService::authorize($status, $bob, $bob->remote_url.'/statuses/2');
        QuoteService::authorize($status, $carol, $carol->remote_url.'/statuses/1');

        QuoteService::revokeForActor($user->profile->id, $bob->id);

        expect(QuoteAuthorization::approved()->count())->toBe(1)
            ->and((int) QuoteAuthorization::approved()->first()->actor_id)->toBe((int) $carol->id);

        Queue::assertPushed(RevokeQuoteAuthorizationPipeline::class, 2);
    });

    it('revokes every stamp issued to a domain when the author blocks it', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile('remote.example', 'bob');
        $dave = quoteRemoteProfile('other.example', 'dave');

        QuoteService::authorize($status, $bob, $bob->remote_url.'/statuses/1');
        QuoteService::authorize($status, $dave, $dave->remote_url.'/statuses/1');

        QuoteService::revokeForDomain($user->profile->id, 'REMOTE.example');

        expect(QuoteAuthorization::approved()->count())->toBe(1)
            ->and((int) QuoteAuthorization::approved()->first()->actor_id)->toBe((int) $dave->id);
    });
});

describe('settings', function () {
    it('saves the account default from the privacy page', function () {
        $user = quoteLocalUser();

        $this->actingAs($user)
            ->get('/settings/privacy')
            ->assertOk()
            ->assertSee('Who can quote your posts');

        // A fresh model, the GET above decorates the cached settings
        // relation with view-only attributes that must not be saved.
        $this->actingAs($user->fresh())
            ->post('/settings/privacy', ['can_quote' => 'followers'])
            ->assertRedirect();

        expect($user->settings()->first()->can_quote)->toBe('followers')
            ->and(QuoteService::accountPolicy($user->profile))->toBe('followers');
    });

    it('lists approved quotes and revokes one', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();

        $auth = QuoteService::authorize($status, $bob, $bob->remote_url.'/statuses/1');

        $this->actingAs($user)
            ->get('/settings/privacy/quotes')
            ->assertOk()
            ->assertSee($bob->remote_url.'/statuses/1');

        $this->actingAs($user)
            ->post('/settings/privacy/quotes', ['id' => $auth->id])
            ->assertRedirect();

        expect($auth->fresh()->isRevoked())->toBeTrue();

        Queue::assertPushed(RevokeQuoteAuthorizationPipeline::class, 1);
    });

    it('does not let someone revoke a stamp that is not theirs', function () {
        $user = quoteLocalUser();
        $stranger = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);
        $bob = quoteRemoteProfile();

        $auth = QuoteService::authorize($status, $bob, $bob->remote_url.'/statuses/1');

        $this->actingAs($stranger)
            ->post('/settings/privacy/quotes', ['id' => $auth->id])
            ->assertNotFound();

        expect($auth->fresh()->isApproved())->toBeTrue();
    });
});

describe('api', function () {
    it('sets and clears the per-post override through interaction_policy', function () {
        $user = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);

        Passport::actingAs($user, ['read', 'write']);

        $this->putJson("/api/v1/statuses/{$status->id}/interaction_policy", [
            'quote_approval_policy' => 'nobody',
        ])->assertOk()->assertJsonPath('quote_approval_policy', 'nobody');

        expect($status->fresh()->quote_policy)->toBe(QuoteService::FLAGS_NOBODY);

        $this->putJson("/api/v1/statuses/{$status->id}/interaction_policy", [
            'quote_approval_policy' => null,
        ])->assertOk()->assertJsonPath('quote_approval_policy', 'public');

        expect($status->fresh()->quote_policy)->toBeNull();
    });

    it('does not let someone else change the policy of a post', function () {
        $user = quoteLocalUser();
        $stranger = quoteLocalUser();
        $status = quoteLocalStatus($user->profile);

        Passport::actingAs($stranger, ['read', 'write']);

        $this->putJson("/api/v1/statuses/{$status->id}/interaction_policy", [
            'quote_approval_policy' => 'nobody',
        ])->assertNotFound();

        expect($status->fresh()->quote_policy)->toBeNull();
    });
});
