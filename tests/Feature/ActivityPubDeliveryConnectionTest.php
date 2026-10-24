<?php

use App\Models\User;
use App\Services\ActivityPubDeliveryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ActivityPubDeliveryService single-delivery transport failures
|--------------------------------------------------------------------------
|
| queueDelivery() runs synchronously from follow/unfollow (via
| Helpers::sendSignedObject), which already commit local state before delivery
| and have no try/catch. A momentarily-unreachable remote raises a
| ConnectionException from Http::send(); that must be treated as best-effort
| (logged, host-health recorded) and NOT propagated to the caller, or the
| follow/unfollow request 500s after the DB mutation is committed. Other
| exception types (bad sender/destination) must still throw.
|
*/

/**
 * Run a callback with the app environment temporarily set to production, since
 * delivery is skipped outside production. Restores env afterwards.
 */
function deliverAsProduction(callable $fn): mixed
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

it('does not propagate a ConnectionException from a momentarily-unreachable remote', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 7: Failed to connect to remote.example (Connection refused)');
    });

    $user = User::factory()->create();
    $user->refresh();
    $sender = $user->profile;

    // Seed AFTER creating the user: model/factory setup and the lazy DB refresh
    // can flush the cache store, which would wipe an earlier seed. Seed the DNS
    // cache so validateDestination treats the host as publicly resolvable, and
    // the banned-domains cache so the production ban check hits cache
    // (validateUrl runs its ban check only in production).
    Cache::put('helpers:url:public-ips:'.hash('xxh128', 'remote.example'), ['203.0.113.40'], 3600);
    Cache::put('instances:banned:domains', [], 1209600);

    $payload = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $sender->permalink('#follow/1'),
        'type' => 'Follow',
        'actor' => $sender->permalink(),
        'object' => 'https://remote.example/users/target',
    ];

    // Must complete without throwing (best-effort delivery).
    deliverAsProduction(function () use ($sender, $payload) {
        (new ActivityPubDeliveryService)
            ->from($sender)
            ->to('https://remote.example/users/target/inbox')
            ->payload($payload)
            ->send();
    });

    // Reaching here without an exception is the assertion.
    expect(true)->toBeTrue();
});

it('still throws for a missing sender (non-transport error)', function () {
    $user = User::factory()->create();
    $user->refresh();

    // No ->from(): validateSender/precondition path must still throw, unchanged.
    expect(fn () => deliverAsProduction(function () {
        (new ActivityPubDeliveryService)
            ->to('https://remote.example/users/target/inbox')
            ->payload(['type' => 'Follow'])
            ->send();
    }))->toThrow(InvalidArgumentException::class);
});
