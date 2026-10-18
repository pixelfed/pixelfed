<?php

use App\Jobs\Federation\DeliverDirectMessageActivity;
use App\Models\Instance;
use App\Models\Profile;
use App\Models\User;
use App\Services\ActivityPubDeliveryService;
use App\Services\DeliveryHostService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/**
 * ActivityPubDeliveryService::pool()/queueDelivery() only perform HTTP delivery
 * in the production environment; outside production they log and return. Run
 * the delivery in a forged production environment so these tests exercise the
 * real send path, and restore the environment afterwards.
 */
function deliverInProduction(callable $fn): mixed
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

/**
 * In production, validateDestination() resolves each host to a public IP and
 * checks the banned-domains list. Seed both caches so validation passes without
 * a real DNS lookup or DB query. Seed after any User/Profile factory creation,
 * since the lazy DB refresh can flush the cache store.
 *
 * @param  array<int, string>  $hosts
 */
function seedDeliveryHosts(array $hosts): void
{
    foreach ($hosts as $host) {
        Cache::put('helpers:url:public-ips:'.hash('xxh128', $host), ['203.0.113.40'], 3600);
    }
    Cache::put('instances:banned:domains', [], 1209600);
}

/**
 * Current recorded delivery failure count for a domain, read straight from
 * the instances row (0 when no row exists).
 */
function deliveryFailures(string $domain): int
{
    return (int) (Instance::whereDomain($domain)->value('delivery_failures') ?? 0);
}

/**
 * Minimal signed-deliverable activity for the given sender.
 *
 * @return array<string, mixed>
 */
function poolActivity(Profile $profile): array
{
    return [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $profile->permalink('#delete'),
        'type' => 'Delete',
        'actor' => $profile->permalink(),
    ];
}

describe('ActivityPubDeliveryService::pool()', function () {
    it('delivers activity to all audience inboxes via POST', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote1.example', 'remote2.example', 'remote3.example']);

        $audience = [
            'https://remote1.example/inbox',
            'https://remote2.example/inbox',
            'https://remote3.example/inbox',
        ];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $profile->permalink('#delete'),
            'type' => 'Delete',
            'actor' => $profile->permalink(),
            'object' => [
                'type' => 'Person',
                'id' => $profile->permalink(),
            ],
        ];

        deliverInProduction(fn () => ActivityPubDeliveryService::pool($profile, $audience, $activity));

        Http::assertSentCount(3);

        Http::assertSent(fn ($request) => $request->url() === 'https://remote1.example/inbox'
            && $request->method() === 'POST'
        );
        Http::assertSent(fn ($request) => $request->url() === 'https://remote2.example/inbox'
            && $request->method() === 'POST'
        );
        Http::assertSent(fn ($request) => $request->url() === 'https://remote3.example/inbox'
            && $request->method() === 'POST'
        );
    });

    it('includes correct content type and signature headers', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        $audience = ['https://remote.example/inbox'];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $profile->permalink('#test'),
            'type' => 'Create',
            'actor' => $profile->permalink(),
        ];

        deliverInProduction(fn () => ActivityPubDeliveryService::pool($profile, $audience, $activity));

        Http::assertSent(function ($request) {
            $contentType = $request->header('Content-Type')[0] ?? '';
            $hasSignature = ! empty($request->header('Signature')[0] ?? '');

            return str_contains($contentType, 'application/ld+json')
                && $hasSignature;
        });
    });

    it('sends JSON-encoded activity as the request body', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        $audience = ['https://remote.example/inbox'];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $profile->permalink('#test'),
            'type' => 'Create',
            'actor' => $profile->permalink(),
        ];

        deliverInProduction(fn () => ActivityPubDeliveryService::pool($profile, $audience, $activity));

        Http::assertSent(function ($request) use ($activity) {
            $body = json_decode($request->body(), true);

            return $body === $activity;
        });
    });

    it('does nothing when audience is empty', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Delete',
        ];

        ActivityPubDeliveryService::pool($profile, [], $activity);

        Http::assertNothingSent();
    });

    it('invokes onError callback for failed responses', function () {
        Http::fake([
            'https://good.example/inbox' => Http::response('', 202),
            'https://bad.example/inbox' => Http::response('', 500),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['good.example', 'bad.example']);

        $audience = [
            'https://good.example/inbox',
            'https://bad.example/inbox',
        ];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Delete',
            'actor' => $profile->permalink(),
        ];

        $errors = [];
        deliverInProduction(function () use ($profile, $audience, $activity, &$errors) {
            ActivityPubDeliveryService::pool($profile, $audience, $activity, function ($reason, $index) use (&$errors) {
                $errors[] = $index;
            });
        });

        expect($errors)->toHaveCount(1);
    });

    it('does not invoke onError callback when all requests succeed', function () {
        Http::fake([
            'https://remote1.example/inbox' => Http::response('', 202),
            'https://remote2.example/inbox' => Http::response('', 200),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote1.example', 'remote2.example']);

        $audience = [
            'https://remote1.example/inbox',
            'https://remote2.example/inbox',
        ];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'type' => 'Create',
            'actor' => $profile->permalink(),
        ];

        $errors = [];
        deliverInProduction(function () use ($profile, $audience, $activity, &$errors) {
            ActivityPubDeliveryService::pool($profile, $audience, $activity, function ($reason, $index) use (&$errors) {
                $errors[] = $index;
            });
        });

        expect($errors)->toBeEmpty();
    });

    it('counts a 2xx as delivered and clears a prior host failure', function () {
        Http::fake([
            'https://remote.example/inbox' => Http::response('', 202),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        DeliveryHostService::recordFailure('remote.example');
        expect(deliveryFailures('remote.example'))->toBe(1);

        $audience = ['https://remote.example/inbox'];
        $activity = poolActivity($profile);

        $result = deliverInProduction(fn () => ActivityPubDeliveryService::pool($profile, $audience, $activity));

        expect($result['delivered'])->toBe(1);
        expect($result['rejected'])->toBe(0);
        expect(deliveryFailures('remote.example'))->toBe(0);
    });

    it('counts a 5xx as rejected and marks the host failed', function () {
        Http::fake([
            'https://remote.example/inbox' => Http::response('', 503),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        $audience = ['https://remote.example/inbox'];
        $activity = poolActivity($profile);

        $result = deliverInProduction(fn () => ActivityPubDeliveryService::pool($profile, $audience, $activity));

        expect($result['delivered'])->toBe(0);
        expect($result['rejected'])->toBe(1);
        expect(deliveryFailures('remote.example'))->toBe(1);
    });

    it('counts a 4xx as rejected without marking the host failed', function () {
        Http::fake([
            'https://remote.example/inbox' => Http::response('', 422),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        DeliveryHostService::recordFailure('remote.example');
        expect(deliveryFailures('remote.example'))->toBe(1);

        $audience = ['https://remote.example/inbox'];
        $activity = poolActivity($profile);

        $result = deliverInProduction(fn () => ActivityPubDeliveryService::pool($profile, $audience, $activity));

        expect($result['delivered'])->toBe(0);
        expect($result['rejected'])->toBe(1);
        // A 4xx answered, so the host is reachable: the existing failure is
        // cleared, but it is not counted as a delivery and not incremented.
        expect(deliveryFailures('remote.example'))->toBe(0);
    });

    it('treats redirect responses as not delivered', function (int $status) {
        Http::fake([
            'https://remote.example/inbox' => Http::response('', $status, [
                'Location' => 'https://remote.example/new-inbox',
            ]),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        DeliveryHostService::recordFailure('remote.example');
        expect(deliveryFailures('remote.example'))->toBe(1);

        $audience = ['https://remote.example/inbox'];
        $activity = poolActivity($profile);

        $result = deliverInProduction(fn () => ActivityPubDeliveryService::pool($profile, $audience, $activity));

        expect($result['delivered'])->toBe(0);
        expect($result['rejected'])->toBe(1);
        // A redirect answered so the host is reachable (prior failure cleared),
        // but the signed body never reached an inbox: not a delivery.
        expect(deliveryFailures('remote.example'))->toBe(0);
    })->with([301, 302, 307, 308]);
});

describe('ActivityPubDeliveryService::deliver()', function () {
    it('records success and surfaces a 2xx to the caller', function () {
        Http::fake([
            'https://remote.example/inbox' => Http::response('', 202),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        DeliveryHostService::recordFailure('remote.example');
        expect(deliveryFailures('remote.example'))->toBe(1);

        $response = deliverInProduction(fn () => ActivityPubDeliveryService::queue()
            ->from($profile)
            ->to('https://remote.example/inbox')
            ->payload(poolActivity($profile))
            ->deliver()
        );

        expect($response->status())->toBe(202);
        expect(deliveryFailures('remote.example'))->toBe(0);
    });

    it('records failure for a 5xx', function () {
        Http::fake([
            'https://remote.example/inbox' => Http::response('', 503),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        $response = deliverInProduction(fn () => ActivityPubDeliveryService::queue()
            ->from($profile)
            ->to('https://remote.example/inbox')
            ->payload(poolActivity($profile))
            ->deliver()
        );

        expect($response->status())->toBe(503);
        expect(deliveryFailures('remote.example'))->toBe(1);
    });

    it('does not record success for a 4xx', function () {
        Http::fake([
            'https://remote.example/inbox' => Http::response('', 422),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        DeliveryHostService::recordFailure('remote.example');
        expect(deliveryFailures('remote.example'))->toBe(1);

        $response = deliverInProduction(fn () => ActivityPubDeliveryService::queue()
            ->from($profile)
            ->to('https://remote.example/inbox')
            ->payload(poolActivity($profile))
            ->deliver()
        );

        expect($response->status())->toBe(422);
        // A 4xx must not clear the host's failure state.
        expect(deliveryFailures('remote.example'))->toBe(1);
    });

    it('does not record success for a redirect and surfaces it as retryable', function (int $status) {
        Http::fake([
            'https://remote.example/inbox' => Http::response('', $status, [
                'Location' => 'https://remote.example/new-inbox',
            ]),
        ]);

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        seedDeliveryHosts(['remote.example']);

        DeliveryHostService::recordFailure('remote.example');
        expect(deliveryFailures('remote.example'))->toBe(1);

        $response = deliverInProduction(fn () => ActivityPubDeliveryService::queue()
            ->from($profile)
            ->to('https://remote.example/inbox')
            ->payload(poolActivity($profile))
            ->deliver()
        );

        expect($response->status())->toBe($status);
        // A redirect is not a delivery: the host failure must not be cleared,
        // and a shouldRetry-style check must treat it as not-delivered.
        expect(deliveryFailures('remote.example'))->toBe(1);
        expect(DeliverDirectMessageActivity::shouldRetry($response->status()))->toBeTrue();
    })->with([301, 302, 307, 308]);
});
