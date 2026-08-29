<?php

use App\Jobs\DeletePipeline\FanoutDeletePipeline;
use App\Jobs\StatusPipeline\StatusActivityPubDeliver;
use App\Models\Follower;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use App\Services\ActivityPubFetchService;
use App\Services\MediaStorageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| HTTP Client Migration Tests
|--------------------------------------------------------------------------
|
| Verifies that the Guzzle-to-Laravel HTTP client migration works
| correctly for MediaStorageService, ActivityPub delivery jobs, and
| ActivityPubFetchService URI resolution.
|
*/

describe('MediaStorageService::head()', function () {
    it('returns length and mime on successful HEAD response', function () {
        Http::fake([
            'https://example.com/image.jpg' => Http::response('', 200, [
                'Content-Length' => '50000',
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $result = MediaStorageService::head('https://example.com/image.jpg');

        expect($result)->toBeArray();
        expect($result['length'])->toBe(50000);
        expect($result['mime'])->toBe('image/jpeg');
    });

    it('returns false when content-length is too small', function () {
        Http::fake([
            'https://example.com/tiny.jpg' => Http::response('', 200, [
                'Content-Length' => '5',
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $result = MediaStorageService::head('https://example.com/tiny.jpg');

        expect($result)->toBeFalse();
    });

    it('returns false when response is not successful', function () {
        Http::fake([
            'https://example.com/missing.jpg' => Http::response('', 404),
        ]);

        $result = MediaStorageService::head('https://example.com/missing.jpg');

        expect($result)->toBeFalse();
    });

    it('returns false when content-length header is missing', function () {
        Http::fake([
            'https://example.com/noheaders.jpg' => Http::response('', 200, [
                'Content-Type' => 'image/jpeg',
            ]),
        ]);

        $result = MediaStorageService::head('https://example.com/noheaders.jpg');

        expect($result)->toBeFalse();
    });

    it('returns false on connection exception', function () {
        Http::fake([
            'https://unreachable.example.com/*' => fn () => throw new ConnectionException('Connection refused'),
        ]);

        $result = MediaStorageService::head('https://unreachable.example.com/image.jpg');

        expect($result)->toBeFalse();
    });
});

describe('FanoutDeletePipeline delivery', function () {
    it('sends delete activities to known shared inboxes via Http::pool', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        // Create remote profiles with shared inboxes
        Profile::factory()->remote()->create([
            'sharedInbox' => 'https://remote1.example/inbox',
        ]);
        Profile::factory()->remote()->create([
            'sharedInbox' => 'https://remote2.example/inbox',
        ]);

        Cache::forget('pf:ap:known_instances');

        $job = new FanoutDeletePipeline($profile);
        $job->handle();

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() === 'https://remote1.example/inbox'
            && $request->method() === 'POST'
            && str_contains($request->header('Content-Type')[0] ?? '', 'application/ld+json')
        );
        Http::assertSent(fn ($request) => $request->url() === 'https://remote2.example/inbox'
            && $request->method() === 'POST'
        );
    });

    it('skips delivery when profile lacks private key', function () {
        Http::fake();

        $profile = Profile::factory()->remote()->create([
            'private_key' => null,
        ]);

        $job = new FanoutDeletePipeline($profile);
        $job->handle();

        Http::assertNothingSent();
    });
});

describe('StatusActivityPubDeliver delivery', function () {
    it('sends create activities to audience inboxes via Http::pool', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();
        $profile = $user->profile;

        // Create a remote follower with inbox
        $remoteFollower = Profile::factory()->remote()->create([
            'sharedInbox' => 'https://remote.example/inbox',
            'inbox_url' => 'https://remote.example/users/bob/inbox',
        ]);

        // Make them a follower
        Follower::create([
            'profile_id' => $remoteFollower->id,
            'following_id' => $profile->id,
        ]);

        // Clear audience cache
        Cache::forget('pf:services:follower:audience:'.$profile->id);

        $status = Status::factory()->create([
            'profile_id' => $profile->id,
            'type' => 'photo',
            'scope' => 'public',
            'visibility' => 'public',
        ]);

        // Force local and no url/uri to pass the guard clause
        $status->local = true;
        $status->setAttribute('url', null);
        $status->uri = null;
        $status->saveQuietly();
        $status->refresh();

        $job = new StatusActivityPubDeliver($status);
        $job->handle();

        Http::assertSent(fn ($request) => $request->url() === 'https://remote.example/inbox'
            && $request->method() === 'POST'
        );
    });

    it('does not deliver non-local statuses', function () {
        Http::fake();

        $user = User::factory()->create();
        $user->refresh();

        $status = Status::factory()->create([
            'profile_id' => $user->profile->id,
            'uri' => 'https://remote.example/status/1',
            'local' => false,
        ]);

        $job = new StatusActivityPubDeliver($status);
        $job->handle();

        Http::assertNothingSent();
    });
});

describe('ActivityPubFetchService URI resolution', function () {
    it('resolves a relative location against a base URL', function () {
        $method = new ReflectionMethod(ActivityPubFetchService::class, 'resolveRedirect');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'https://example.com/users/alice', '/statuses/123');

        expect($result)->toBe('https://example.com/statuses/123');
    });

    it('resolves an absolute location unchanged', function () {
        $method = new ReflectionMethod(ActivityPubFetchService::class, 'resolveRedirect');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'https://example.com/users/alice', 'https://other.example/status/456');

        expect($result)->toBe('https://other.example/status/456');
    });

    it('returns null for empty location', function () {
        $method = new ReflectionMethod(ActivityPubFetchService::class, 'resolveRedirect');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'https://example.com/users/alice', '');

        expect($result)->toBeNull();
    });

    it('returns null for location with control characters', function () {
        $method = new ReflectionMethod(ActivityPubFetchService::class, 'resolveRedirect');
        $method->setAccessible(true);

        $result = $method->invoke(null, 'https://example.com/users/alice', "/bad\x00path");

        expect($result)->toBeNull();
    });
});
