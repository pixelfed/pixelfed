<?php

use App\Exceptions\InvalidDeliveryDestinationException;
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

function breakerInProduction(callable $fn): mixed
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

function breakerSeedHosts(array $hosts): void
{
    foreach ($hosts as $host) {
        Cache::put(
            'helpers:url:public-ips:v2:'.hash('xxh128', $host),
            ['state' => 'ok', 'ips' => ['203.0.113.40']],
            86400 * 30
        );
    }

    Cache::put('instances:banned:domains', [], 86400 * 30);
}

function breakerFail(string $domain, int $times = 1): void
{
    for ($i = 0; $i < $times; $i++) {
        DeliveryHostService::recordFailure($domain);
    }
}

function breakerRow(string $domain): ?Instance
{
    return Instance::whereDomain($domain)->first();
}

function breakerMarkFailed($test, string $domain): void
{
    breakerFail($domain, 5);

    $test->travel(8)->days();

    breakerFail($domain);
}

function breakerSender(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    return $user->profile;
}

function breakerActivity(Profile $profile): array
{
    return [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $profile->permalink('#breaker'),
        'type' => 'Delete',
        'actor' => $profile->permalink(),
    ];
}

beforeEach(function () {
    config([
        'federation.activitypub.delivery.failure_threshold' => 5,
        'federation.activitypub.delivery.max_backoff' => 86400,
        'federation.activitypub.delivery.fail_after_days' => 7,
    ]);

    foreach (['dead.example', 'other.example', 'live.example'] as $domain) {
        DeliveryHostService::reset($domain);
    }
});

afterEach(function () {
    foreach (['dead.example', 'other.example', 'live.example'] as $domain) {
        DeliveryHostService::reset($domain);
    }
});

describe('breaker states', function () {
    it('stays closed under the threshold', function () {
        breakerFail('dead.example', 4);

        $row = breakerRow('dead.example');

        expect((int) $row->delivery_failures)->toBe(4);
        expect($row->delivery_down_since)->toBeNull();
        expect($row->delivery_failed_at)->toBeNull();
        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_FAILING);
        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();
    });

    it('opens at the threshold, stamps when the outage started and skips the host', function () {
        breakerFail('dead.example', 5);

        expect(breakerRow('dead.example')->delivery_down_since)->not->toBeNull();
        expect(DeliveryHostService::state(breakerRow('dead.example')))->toBe(DeliveryHostService::STATE_OPEN);
        expect(DeliveryHostService::allows('dead.example'))->toBeFalse();
        expect(DeliveryHostService::isUnavailable('dead.example'))->toBeTrue();
        expect(DeliveryHostService::isFailed('dead.example'))->toBeFalse();
    });

    it('lets exactly one probe through once the backoff window passes', function () {
        breakerFail('dead.example', 5);

        $this->travel(2)->hours();

        expect(DeliveryHostService::state(breakerRow('dead.example')))->toBe(DeliveryHostService::STATE_HALF_OPEN);
        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();
        expect(DeliveryHostService::allows('dead.example'))->toBeFalse();
    });

    it('closes when the probe succeeds', function () {
        breakerFail('dead.example', 5);

        $this->travel(2)->hours();

        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();

        DeliveryHostService::recordSuccess('dead.example');

        $row = breakerRow('dead.example');

        expect((int) $row->delivery_failures)->toBe(0);
        expect($row->delivery_down_since)->toBeNull();
        expect($row->delivery_next_after)->toBeNull();
        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_CLOSED);
        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();
        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();
    });

    it('reopens with a longer backoff when the probe fails', function () {
        breakerFail('dead.example', 5);

        $this->travel(2)->hours();

        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();

        breakerFail('dead.example');

        $row = breakerRow('dead.example');

        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_OPEN);
        expect($row->delivery_next_after->greaterThan(now()->addMinutes(90)))->toBeTrue();
        expect(DeliveryHostService::allows('dead.example'))->toBeFalse();
    });

    it('caps the backoff at max_backoff', function () {
        breakerFail('dead.example', 20);

        $row = breakerRow('dead.example');

        expect($row->delivery_next_after->lessThanOrEqualTo(now()->addDay()->addSecond()))->toBeTrue();
    });

    it('marks the host failed once it has been down for more than the configured days', function () {
        breakerMarkFailed($this, 'dead.example');

        $row = breakerRow('dead.example');

        expect($row->delivery_failed_at)->not->toBeNull();
        expect($row->delivery_next_after)->toBeNull();
        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_FAILED);
        expect(DeliveryHostService::isFailed('dead.example'))->toBeTrue();
        expect(DeliveryHostService::isUnavailable('dead.example'))->toBeTrue();
        expect(DeliveryHostService::allows('dead.example'))->toBeFalse();

        $this->travel(30)->days();

        expect(DeliveryHostService::allows('dead.example'))->toBeFalse();
    });

    it('honours a custom fail_after_days', function () {
        config(['federation.activitypub.delivery.fail_after_days' => 2]);

        breakerFail('dead.example', 5);

        $this->travel(3)->days();

        breakerFail('dead.example');

        expect(DeliveryHostService::isFailed('dead.example'))->toBeTrue();
    });

    it('does not mark a host failed when a delivery succeeded inside the window', function () {
        breakerFail('dead.example', 5);

        $this->travel(4)->days();

        DeliveryHostService::recordSuccess('dead.example');

        $this->travel(4)->days();

        breakerFail('dead.example', 5);

        $row = breakerRow('dead.example');

        expect($row->delivery_failed_at)->toBeNull();
        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_OPEN);
    });

    it('starts the outage clock when the breaker opens, not at an old stray failure', function () {
        breakerFail('dead.example');

        $this->travel(30)->days();

        breakerFail('dead.example', 4);

        $row = breakerRow('dead.example');

        expect($row->delivery_failed_at)->toBeNull();
        expect($row->delivery_down_since->greaterThan(now()->subMinute()))->toBeTrue();
        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_OPEN);
    });

    it('keeps the outage clock running across probes', function () {
        breakerFail('dead.example', 5);

        $since = breakerRow('dead.example')->delivery_down_since;

        $this->travel(2)->hours();

        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();

        breakerFail('dead.example');

        expect(breakerRow('dead.example')->delivery_down_since->equalTo($since))->toBeTrue();
    });

    it('does not mark a slow trickle of failures failed before the threshold', function () {
        breakerFail('dead.example');

        $this->travel(10)->days();

        breakerFail('dead.example');

        $row = breakerRow('dead.example');

        expect($row->delivery_failed_at)->toBeNull();
        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_FAILING);
    });
});

describe('recovery', function () {
    it('revives a failed host on inbound activity for a single probe', function () {
        breakerMarkFailed($this, 'dead.example');

        DeliveryHostService::recordInbound('https://dead.example/users/alice');

        $row = breakerRow('dead.example');

        expect($row->delivery_failed_at)->toBeNull();
        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_HALF_OPEN);
        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();
        expect(DeliveryHostService::allows('dead.example'))->toBeFalse();
    });

    it('sends a revived host straight back to failed when its probe fails', function () {
        breakerMarkFailed($this, 'dead.example');

        DeliveryHostService::recordInbound('https://dead.example/users/alice');

        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();

        breakerFail('dead.example');

        expect(DeliveryHostService::isFailed('dead.example'))->toBeTrue();
    });

    it('closes a revived host when its probe succeeds', function () {
        breakerMarkFailed($this, 'dead.example');

        DeliveryHostService::recordInbound(['id' => 'https://dead.example/users/alice']);

        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();

        DeliveryHostService::recordSuccess('dead.example');

        $row = breakerRow('dead.example');

        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_CLOSED);
        expect($row->delivery_down_since)->toBeNull();
    });

    it('revives a host at most once per day', function () {
        breakerMarkFailed($this, 'dead.example');

        DeliveryHostService::recordInbound('https://dead.example/users/alice');

        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();

        breakerFail('dead.example');

        DeliveryHostService::recordInbound('https://dead.example/users/alice');

        expect(DeliveryHostService::isFailed('dead.example'))->toBeTrue();

        $this->travel(25)->hours();

        DeliveryHostService::recordInbound('https://dead.example/users/alice');

        expect(DeliveryHostService::isFailed('dead.example'))->toBeFalse();
    });

    it('leaves open hosts alone on inbound activity', function () {
        breakerFail('dead.example', 5);

        $before = breakerRow('dead.example')->delivery_next_after;

        DeliveryHostService::recordInbound('https://dead.example/users/alice');

        $row = breakerRow('dead.example');

        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_OPEN);
        expect($row->delivery_next_after->equalTo($before))->toBeTrue();
    });

    it('ignores inbound activity without a usable actor', function () {
        breakerMarkFailed($this, 'dead.example');

        DeliveryHostService::recordInbound(null);
        DeliveryHostService::recordInbound(['type' => 'Person']);
        DeliveryHostService::recordInbound('not a url');

        expect(DeliveryHostService::isFailed('dead.example'))->toBeTrue();
    });

    it('reset clears a failed host', function () {
        breakerMarkFailed($this, 'dead.example');

        DeliveryHostService::reset('dead.example');

        $row = breakerRow('dead.example');

        expect(DeliveryHostService::state($row))->toBe(DeliveryHostService::STATE_CLOSED);
        expect($row->delivery_failed_at)->toBeNull();
        expect($row->delivery_down_since)->toBeNull();
        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();
    });

    it('resetFailed clears every failed host and leaves open hosts alone', function () {
        breakerFail('dead.example', 5);
        breakerFail('other.example', 5);

        $this->travel(8)->days();

        breakerFail('dead.example');
        breakerFail('other.example');
        breakerFail('live.example', 5);

        expect(DeliveryHostService::resetFailed())->toBe(2);

        expect(DeliveryHostService::state(breakerRow('dead.example')))->toBe(DeliveryHostService::STATE_CLOSED);
        expect(DeliveryHostService::state(breakerRow('other.example')))->toBe(DeliveryHostService::STATE_CLOSED);
        expect(DeliveryHostService::state(breakerRow('live.example')))->toBe(DeliveryHostService::STATE_OPEN);
    });
});

describe('delivery', function () {
    it('skips a failed host in a pooled fanout', function () {
        Http::fake(['*' => Http::response('', 202)]);

        $profile = breakerSender();

        breakerMarkFailed($this, 'dead.example');
        breakerSeedHosts(['dead.example', 'live.example']);

        $result = breakerInProduction(fn () => ActivityPubDeliveryService::pool(
            $profile,
            ['https://dead.example/inbox', 'https://live.example/inbox'],
            breakerActivity($profile)
        ));

        expect($result['skipped'])->toBe(1);
        expect($result['delivered'])->toBe(1);

        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'dead.example'));
    });

    it('sends every inbox of a half-open host in the batch that won the probe and closes it', function () {
        Http::fake(['*' => Http::response('', 202)]);

        $profile = breakerSender();

        breakerFail('dead.example', 5);

        $this->travel(2)->hours();

        breakerSeedHosts(['dead.example']);

        $result = breakerInProduction(fn () => ActivityPubDeliveryService::pool(
            $profile,
            ['https://dead.example/users/a/inbox', 'https://dead.example/users/b/inbox'],
            breakerActivity($profile)
        ));

        expect($result['delivered'])->toBe(2);
        expect(DeliveryHostService::state(breakerRow('dead.example')))->toBe(DeliveryHostService::STATE_CLOSED);
    });

    it('skips a half-open host while another worker holds the probe', function () {
        Http::fake(['*' => Http::response('', 202)]);

        $profile = breakerSender();

        breakerFail('dead.example', 5);

        $this->travel(2)->hours();

        breakerSeedHosts(['dead.example']);

        expect(DeliveryHostService::allows('dead.example'))->toBeTrue();

        $result = breakerInProduction(fn () => ActivityPubDeliveryService::pool(
            $profile,
            ['https://dead.example/inbox'],
            breakerActivity($profile)
        ));

        expect($result['skipped'])->toBe(1);
        expect($result['sent'])->toBe(0);

        Http::assertNothingSent();
    });

    it('marks the host failed when a pooled probe fails after the window', function () {
        Http::fake(['*' => Http::response('', 503)]);

        $profile = breakerSender();

        breakerFail('dead.example', 5);

        $this->travel(8)->days();

        breakerSeedHosts(['dead.example']);

        breakerInProduction(fn () => ActivityPubDeliveryService::pool(
            $profile,
            ['https://dead.example/inbox'],
            breakerActivity($profile)
        ));

        expect(DeliveryHostService::isFailed('dead.example'))->toBeTrue();
    });

    it('throws host_failed from deliver() for a failed host', function () {
        Http::fake();

        $profile = breakerSender();

        breakerMarkFailed($this, 'dead.example');
        breakerSeedHosts(['dead.example']);

        $reason = null;

        try {
            breakerInProduction(fn () => ActivityPubDeliveryService::queue()
                ->from($profile)
                ->to('https://dead.example/inbox')
                ->payload(breakerActivity($profile))
                ->deliver()
            );
        } catch (InvalidDeliveryDestinationException $e) {
            $reason = $e->reason;
        }

        expect($reason)->toBe(DeliveryHostService::REASON_FAILED);

        Http::assertNothingSent();
    });

    it('returns null from deliver() for an open host so callers can retry', function () {
        Http::fake();

        $profile = breakerSender();

        breakerFail('dead.example', 5);
        breakerSeedHosts(['dead.example']);

        $response = breakerInProduction(fn () => ActivityPubDeliveryService::queue()
            ->from($profile)
            ->to('https://dead.example/inbox')
            ->payload(breakerActivity($profile))
            ->deliver()
        );

        expect($response)->toBeNull();

        Http::assertNothingSent();
    });

    it('stays silent from send() for a failed host', function () {
        Http::fake();

        $profile = breakerSender();

        breakerMarkFailed($this, 'dead.example');
        breakerSeedHosts(['dead.example']);

        breakerInProduction(fn () => ActivityPubDeliveryService::queue()
            ->from($profile)
            ->to('https://dead.example/inbox')
            ->payload(breakerActivity($profile))
            ->send()
        );

        Http::assertNothingSent();
    });

    it('stops retrying a direct message to a failed host', function () {
        Http::fake();

        $profile = breakerSender();

        breakerMarkFailed($this, 'dead.example');
        breakerSeedHosts(['dead.example']);

        $job = (new DeliverDirectMessageActivity(
            $profile->id,
            'https://dead.example/inbox',
            breakerActivity($profile)
        ))->withFakeQueueInteractions();

        breakerInProduction(fn () => $job->handle());

        $job->assertNotReleased();

        Http::assertNothingSent();
    });

    it('keeps retrying a direct message to an open host', function () {
        Http::fake();

        $profile = breakerSender();

        breakerFail('dead.example', 5);
        breakerSeedHosts(['dead.example']);

        $job = (new DeliverDirectMessageActivity(
            $profile->id,
            'https://dead.example/inbox',
            breakerActivity($profile)
        ))->withFakeQueueInteractions();

        breakerInProduction(fn () => $job->handle());

        $job->assertReleased();

        Http::assertNothingSent();
    });

    it('skips failed hosts in DeliveryHostService::filter', function () {
        breakerMarkFailed($this, 'dead.example');

        $kept = DeliveryHostService::filter([
            'https://dead.example/inbox',
            'https://live.example/inbox',
        ]);

        expect($kept)->toBe([1 => 'https://live.example/inbox']);
    });
});

describe('admin surface', function () {
    it('describes the breaker state for the admin instance resource', function () {
        breakerMarkFailed($this, 'dead.example');

        $delivery = DeliveryHostService::describe(breakerRow('dead.example'));

        expect($delivery['state'])->toBe(DeliveryHostService::STATE_FAILED);
        expect($delivery['failures'])->toBe(6);
        expect($delivery['down_since'])->not->toBeNull();
        expect($delivery['failed_at'])->not->toBeNull();
        expect($delivery['next_attempt_at'])->toBeNull();
    });

    it('lists and resets hosts from the command', function () {
        breakerMarkFailed($this, 'dead.example');

        $this->artisan('app:delivery-hosts', ['--state' => 'failed'])
            ->expectsOutputToContain('dead.example')
            ->assertSuccessful();

        $this->artisan('app:delivery-hosts', ['action' => 'reset', 'domain' => 'dead.example'])
            ->assertSuccessful();

        expect(DeliveryHostService::state(breakerRow('dead.example')))->toBe(DeliveryHostService::STATE_CLOSED);
    });

    it('resets every failed host from the command with --force', function () {
        breakerMarkFailed($this, 'dead.example');

        $this->artisan('app:delivery-hosts', ['action' => 'reset-failed', '--force' => true])
            ->assertSuccessful();

        expect(DeliveryHostService::isFailed('dead.example'))->toBeFalse();
    });
});
