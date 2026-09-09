<?php

use App\Models\Profile;
use App\Util\ActivityPub\Helpers;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Helpers::getOrFetchRemoteProfile fallback
|--------------------------------------------------------------------------
|
| When an existing remote profile is stale and the refresh fails, the method
| must fall back to the stored profile rather than returning null (which
| crashed downstream activity handlers that dereference the actor).
|
*/

it('returns the existing profile when a stale refresh fails', function () {
    // Any outbound fetch fails.
    Http::fake(fn () => throw new ConnectionException('unreachable'));

    $remoteUrl = 'https://remote.example/users/alice';

    $profile = Profile::factory()->create([
        'user_id' => null,
        'domain' => 'remote.example',
        'remote_url' => $remoteUrl,
        'last_fetched_at' => now()->subDays(5), // stale -> needsFetch true
    ]);

    $result = Helpers::getOrFetchRemoteProfile($remoteUrl);

    expect($result)->not->toBeNull();
    expect($result->id)->toBe($profile->id);
});

it('returns the existing profile without fetching when fresh', function () {
    Http::fake(fn () => throw new ConnectionException('should not be called'));

    $remoteUrl = 'https://remote.example/users/bob';

    $profile = Profile::factory()->create([
        'user_id' => null,
        'domain' => 'remote.example',
        'remote_url' => $remoteUrl,
        'last_fetched_at' => now()->subMinutes(5), // fresh -> no fetch
    ]);

    $result = Helpers::getOrFetchRemoteProfile($remoteUrl);

    expect($result->id)->toBe($profile->id);
    Http::assertNothingSent();
});
