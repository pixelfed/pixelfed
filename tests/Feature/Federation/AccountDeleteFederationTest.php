<?php

use App\Federation\ActivityBuilders\AccountDeleteActivityBuilder;
use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Jobs\Federation\DeliverAccountDeleteActivity;
use App\Jobs\Federation\FanoutAccountDeleteActivity;
use App\Models\Instance;
use App\Models\Profile;
use App\Models\User;
use App\Services\AccountDeleteFederationService;
use App\Services\DeliveryHostService;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Account deletion federation
|--------------------------------------------------------------------------
|
| Covers the service extracted from app:user-account-delete, the two jobs
| that run it from the queue, and the hook at the end of
| DeleteAccountPipeline that makes every local deletion federate.
|
*/

/**
 * A local profile in the state DeleteAccountPipeline leaves it in.
 */
function accountDeleteProfile(): Profile
{
    $user = User::factory()->create();
    $user->refresh();

    $profile = $user->profile;
    $profile->status = 'delete';
    $profile->save();
    $profile->delete();

    return Profile::withTrashed()->findOrFail($profile->id);
}

/**
 * Resolve the hosts up front so validation never reaches for real dns. Call
 * after any factory work: the lazy database refresh can flush the cache.
 *
 * @param  array<int, string>  $hosts
 */
function accountDeleteResolve(array $hosts, string $state = Helpers::URL_OK): void
{
    foreach ($hosts as $host) {
        Cache::put(
            'helpers:url:public-ips:v2:'.hash('xxh128', $host),
            ['state' => $state, 'ips' => $state === Helpers::URL_OK ? ['203.0.113.40'] : []],
            3600
        );
    }
}

function accountDeleteInstance(string $domain, array $attributes = []): Instance
{
    return Instance::create(array_merge([
        'domain' => $domain,
        'shared_inbox' => "https://{$domain}/inbox",
        'nodeinfo_last_fetched' => now()->subDay(),
    ], $attributes));
}

function accountDeleteInProduction(callable $fn): mixed
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

describe('AccountDeleteActivityBuilder', function () {
    it('builds a Delete of the actor addressed to public', function () {
        $profile = accountDeleteProfile();
        $actor = $profile->permalink();

        expect((new AccountDeleteActivityBuilder)->build($profile))->toBe([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actor.'#delete',
            'type' => 'Delete',
            'actor' => $actor,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'object' => $actor,
        ]);
    });
});

describe('AccountDeleteFederationService::prepare()', function () {
    it('hashes the exact payload it will send', function () {
        $profile = accountDeleteProfile();

        $prepared = app(AccountDeleteFederationService::class)->prepare($profile);

        expect($prepared['digest'])->toBe(base64_encode(hash('sha256', $prepared['payload'], true)))
            ->and($prepared['length'])->toBe(strlen($prepared['payload']))
            ->and($prepared['key_id'])->toBe($profile->keyId())
            ->and($prepared)->not->toHaveKey('private_key');
    });

    it('refuses an account that is not deleted', function () {
        $user = User::factory()->create();
        $user->refresh();

        app(AccountDeleteFederationService::class)->prepare($user->profile);
    })->throws(RuntimeException::class, 'not deleted');

    it('accepts a soft deleted profile whose status was never set', function () {
        $user = User::factory()->create();
        $user->refresh();
        $user->profile->delete();

        $profile = Profile::withTrashed()->findOrFail($user->profile_id);

        expect(app(AccountDeleteFederationService::class)->prepare($profile))->toHaveKey('payload');
    });

    it('refuses a profile whose key has been wiped', function () {
        $profile = accountDeleteProfile();
        $profile->private_key = null;

        app(AccountDeleteFederationService::class)->prepare($profile);
    })->throws(RuntimeException::class, 'private key');

    it('refuses a remote profile', function () {
        $profile = accountDeleteProfile();
        $profile->domain = 'remote.example';

        app(AccountDeleteFederationService::class)->prepare($profile);
    })->throws(RuntimeException::class, 'local');
});

describe('AccountDeleteFederationService::audience()', function () {
    it('returns distinct shared inboxes of recently seen instances', function () {
        accountDeleteInstance('fresh.example');
        accountDeleteInstance('alias.example', ['shared_inbox' => 'https://fresh.example/inbox']);
        accountDeleteInstance('stale.example', ['nodeinfo_last_fetched' => now()->subDays(45)]);
        accountDeleteInstance('never.example', ['nodeinfo_last_fetched' => null]);
        accountDeleteInstance('noinbox.example', ['shared_inbox' => null]);

        expect(app(AccountDeleteFederationService::class)->audience()->all())
            ->toBe(['https://fresh.example/inbox']);
    });
});

describe('AccountDeleteFederationService::deliver()', function () {
    // Ported from the old UserAccountDeleteUserAgentTest: Http::pool requests
    // inherit nothing, so the signed User-Agent has to be set per request.
    it('sends the Pixelfed User-Agent and a signature on pooled deliveries', function () {
        Http::fake(['*' => Http::response('', 202)]);

        $profile = accountDeleteProfile();
        $service = app(AccountDeleteFederationService::class);
        $prepared = $service->prepare($profile);

        accountDeleteResolve(['remote.example']);

        $result = $service->deliver($profile, $prepared, ['https://remote.example/f/inbox']);

        expect($result['delivered'])->toBe(['https://remote.example/f/inbox' => 202]);

        Http::assertSent(function ($request) use ($prepared) {
            return $request->url() === 'https://remote.example/f/inbox'
                && $request->method() === 'POST'
                && $request->body() === $prepared['payload']
                && str_contains($request->header('User-Agent')[0], 'Pixelfed')
                && $request->header('Digest')[0] === 'SHA-256='.$prepared['digest']
                && str_contains($request->header('Signature')[0], 'user-agent')
                && $request->header('Content-Type')[0] === AccountDeleteFederationService::CONTENT_TYPE;
        });
    });

    it('puts every inbox in exactly one bucket', function () {
        Http::fake([
            'ok.example/*' => Http::response('', 202),
            'gone.example/*' => Http::response('nope', 401),
            'busy.example/*' => Http::response('', 503),
            'down.example/*' => fn () => throw new ConnectionException('timed out'),
        ]);

        $profile = accountDeleteProfile();
        $service = app(AccountDeleteFederationService::class);
        $prepared = $service->prepare($profile);

        accountDeleteResolve(['ok.example', 'gone.example', 'busy.example', 'down.example']);
        accountDeleteResolve(['internal.example'], Helpers::URL_PRIVATE_IP);

        $failures = [];

        $result = $service->deliver(
            $profile,
            $prepared,
            [
                'https://ok.example/inbox',
                'https://ok.example/inbox',
                'https://gone.example/inbox',
                'https://busy.example/inbox',
                'https://down.example/inbox',
                'https://internal.example/inbox',
                '',
            ],
            null,
            function (string $url, string $reason, ?int $status) use (&$failures) {
                $failures[$url] = $status;
            }
        );

        expect($result['delivered'])->toBe(['https://ok.example/inbox' => 202])
            ->and($result['http_failed'])->toBe(['https://gone.example/inbox' => ['status' => 401, 'body' => 'nope']])
            ->and(array_keys($result['retryable']))->toBe(['https://busy.example/inbox', 'https://down.example/inbox'])
            ->and($result['invalid'])->toBe(['https://internal.example/inbox' => Helpers::URL_PRIVATE_IP])
            ->and($result['skipped'])->toBe([])
            ->and($failures)->toBe([
                'https://gone.example/inbox' => 401,
                'https://busy.example/inbox' => 503,
                'https://down.example/inbox' => null,
            ]);

        // The private address was never contacted
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'internal.example'));
    });

    it('skips hosts that delivery health has marked unavailable', function () {
        Http::fake(['*' => Http::response('', 202)]);

        $profile = accountDeleteProfile();
        $service = app(AccountDeleteFederationService::class);
        $prepared = $service->prepare($profile);

        accountDeleteInstance('dead.example');
        accountDeleteResolve(['dead.example', 'alive.example']);

        for ($i = 0; $i < (int) config('federation.activitypub.delivery.failure_threshold', 5); $i++) {
            DeliveryHostService::recordFailure('dead.example');
        }

        $result = $service->deliver($profile, $prepared, [
            'https://dead.example/inbox',
            'https://alive.example/inbox',
        ]);

        expect($result['skipped'])->toBe(['https://dead.example/inbox'])
            ->and(array_keys($result['delivered']))->toBe(['https://alive.example/inbox']);

        Http::assertSentCount(1);
    });
});

describe('AccountDeleteFederationService::fanout()', function () {
    it('queues one delivery job per chunk, carrying ids and urls only', function () {
        Queue::fake();

        $profile = accountDeleteProfile();

        foreach (['a', 'b', 'c', 'd', 'e'] as $name) {
            accountDeleteInstance("{$name}.example");
        }

        $queued = app(AccountDeleteFederationService::class)->fanout($profile, 2);

        expect($queued)->toBe(5);

        Queue::assertPushedOn('delete', DeliverAccountDeleteActivity::class);
        Queue::assertPushed(DeliverAccountDeleteActivity::class, 3);
        Queue::assertPushed(DeliverAccountDeleteActivity::class, fn ($job) => $job->profileId() === $profile->id
            && $job->pass() === 1
            && $job->inboxes() === ['https://a.example/inbox', 'https://b.example/inbox']
        );
        Queue::assertPushed(DeliverAccountDeleteActivity::class, fn ($job) => $job->inboxes() === ['https://e.example/inbox']);
    });

    it('broadcasts once when it is run twice for the same account', function () {
        Queue::fake();

        $profile = accountDeleteProfile();
        accountDeleteInstance('a.example');

        $service = app(AccountDeleteFederationService::class);

        expect($service->fanout($profile))->toBe(1)
            ->and($service->fanout($profile))->toBe(0);

        Queue::assertPushed(DeliverAccountDeleteActivity::class, 1);
    });

    it('queues nothing for an account that is not deleted', function () {
        Queue::fake();

        $user = User::factory()->create();
        $user->refresh();
        accountDeleteInstance('a.example');

        expect(fn () => app(AccountDeleteFederationService::class)->fanout($user->profile))
            ->toThrow(RuntimeException::class);

        Queue::assertNotPushed(DeliverAccountDeleteActivity::class);
    });
});

describe('DeliverAccountDeleteActivity', function () {
    it('hands only the retryable inboxes to a delayed follow up job', function () {
        Http::fake([
            'ok.example/*' => Http::response('', 202),
            'gone.example/*' => Http::response('', 410),
            'busy.example/*' => Http::response('', 503),
        ]);

        $profile = accountDeleteProfile();
        accountDeleteResolve(['ok.example', 'gone.example', 'busy.example']);

        Queue::fake();

        accountDeleteInProduction(fn () => (new DeliverAccountDeleteActivity($profile->id, [
            'https://ok.example/inbox',
            'https://gone.example/inbox',
            'https://busy.example/inbox',
        ]))->handle(app(AccountDeleteFederationService::class)));

        Http::assertSentCount(3);

        Queue::assertPushed(DeliverAccountDeleteActivity::class, 1);
        Queue::assertPushed(DeliverAccountDeleteActivity::class, fn ($job) => $job->inboxes() === ['https://busy.example/inbox']
            && $job->pass() === 2
            && $job->queue === 'delete'
            && $job->delay !== null
        );
    });

    it('gives up after the last retry delay', function () {
        Http::fake(['*' => Http::response('', 503)]);

        $profile = accountDeleteProfile();
        accountDeleteResolve(['busy.example']);

        Queue::fake();

        $lastPass = count(DeliverAccountDeleteActivity::RETRY_DELAYS) + 1;

        accountDeleteInProduction(fn () => (new DeliverAccountDeleteActivity(
            $profile->id,
            ['https://busy.example/inbox'],
            $lastPass
        ))->handle(app(AccountDeleteFederationService::class)));

        Http::assertSentCount(1);
        Queue::assertNothingPushed();
    });

    it('sends nothing if the account was restored in the meantime', function () {
        Http::fake();

        $profile = accountDeleteProfile();
        $profile->restore();
        $profile->status = null;
        $profile->save();

        accountDeleteInProduction(fn () => (new DeliverAccountDeleteActivity(
            $profile->id,
            ['https://ok.example/inbox']
        ))->handle(app(AccountDeleteFederationService::class)));

        Http::assertNothingSent();
    });

    it('sends nothing outside production', function () {
        Http::fake();

        $profile = accountDeleteProfile();

        (new DeliverAccountDeleteActivity($profile->id, ['https://ok.example/inbox']))
            ->handle(app(AccountDeleteFederationService::class));

        Http::assertNothingSent();
    });
});

describe('FanoutAccountDeleteActivity', function () {
    it('fans out for a soft deleted profile', function () {
        $profile = accountDeleteProfile();
        accountDeleteInstance('a.example');

        config(['federation.activitypub.enabled' => true]);
        Queue::fake();

        accountDeleteInProduction(fn () => (new FanoutAccountDeleteActivity($profile->id))
            ->handle(app(AccountDeleteFederationService::class)));

        Queue::assertPushed(DeliverAccountDeleteActivity::class, 1);
    });

    it('does nothing when federation is disabled', function () {
        $profile = accountDeleteProfile();
        accountDeleteInstance('a.example');

        config(['federation.activitypub.enabled' => false]);
        Queue::fake();

        accountDeleteInProduction(fn () => (new FanoutAccountDeleteActivity($profile->id))
            ->handle(app(AccountDeleteFederationService::class)));

        Queue::assertNothingPushed();
    });
});

describe('DeleteAccountPipeline', function () {
    it('queues the federation fanout once the account is gone locally', function () {
        $user = User::factory()->create();
        $user->refresh();
        $profileId = $user->profile_id;

        Queue::fake();

        (new DeleteAccountPipeline($user))->handle();

        expect(Profile::find($profileId))->toBeNull()
            ->and(Profile::withTrashed()->find($profileId))->not->toBeNull();

        Queue::assertPushedOn('delete', FanoutAccountDeleteActivity::class);
        Queue::assertPushed(FanoutAccountDeleteActivity::class, fn ($job) => $job->profileId() === (int) $profileId);
    });
});
