<?php

use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Search API resolve (webfinger) Tests
|--------------------------------------------------------------------------
|
| The resolve path of GET /api/v2/search returns a single account object
| from WebfingerService. These tests pin a local profile so the lookup
| short-circuits to the database (no federation) and assert the full
| account object is returned intact, rather than a slice of its fields.
|
*/

function makeResolveTarget(string $username): array
{
    $target = User::factory()->create();
    $target->refresh();
    $profile = $target->profile;
    $profile->username = $username;
    $profile->save();
    AccountService::del($profile->id);

    return [$target, $profile];
}

function actAsSearchViewer(): void
{
    $viewer = User::factory()->create();
    $viewer->refresh();
    Passport::actingAs($viewer, ['read']);
}

describe('GET /api/v2/search resolve single @ branch', function () {
    it('returns the full account object with default params (mastodon mode)', function () {
        [$target, $profile] = makeResolveTarget('@resolveuser@localhost');
        actAsSearchViewer();

        $response = $this->getJson('/api/v2/search?q=resolveuser@localhost&resolve=1');
        $response->assertOk();

        $accounts = $response->json('accounts');
        expect($accounts)->toHaveCount(1);

        $account = $accounts[0];
        expect($account)->toHaveKeys(['id', 'username', 'acct', 'url']);

        $expected = AccountService::getMastodon($profile->id, true);
        expect(array_keys($account))->toEqual(array_keys($expected));
    });

    it('returns the full account object with default params (pixelfed mode)', function () {
        [$target, $profile] = makeResolveTarget('@resolveuser@localhost');
        actAsSearchViewer();

        $response = $this->getJson('/api/v2/search?q=resolveuser@localhost&resolve=1&_pe=1');
        $response->assertOk();

        $accounts = $response->json('accounts');
        expect($accounts)->toHaveCount(1);

        $account = $accounts[0];
        expect($account)->toHaveKeys(['id', 'username', 'acct', 'url']);

        $expected = AccountService::get($profile->id);
        expect(array_keys($account))->toEqual(array_keys($expected));
        expect($account)->toHaveKeys(['location', 'has_story']);
    });

    it('returns the full account object with offset=0', function () {
        [$target, $profile] = makeResolveTarget('@resolveuser@localhost');
        actAsSearchViewer();

        $response = $this->getJson('/api/v2/search?q=resolveuser@localhost&resolve=1&_pe=1&offset=0');
        $response->assertOk();

        $accounts = $response->json('accounts');
        expect($accounts)->toHaveCount(1);

        $expected = AccountService::get($profile->id);
        expect(array_keys($accounts[0]))->toEqual(array_keys($expected));
    });

    it('returns no accounts when offset is past the single result', function () {
        [$target, $profile] = makeResolveTarget('@resolveuser@localhost');
        actAsSearchViewer();

        $response = $this->getJson('/api/v2/search?q=resolveuser@localhost&resolve=1&offset=1');
        $response->assertOk();

        expect($response->json('accounts'))->toBe([]);
    });
});

describe('GET /api/v2/search resolve double @ branch', function () {
    it('returns the full account object with default params', function () {
        [$target, $profile] = makeResolveTarget('@wfuser@remote.test');
        actAsSearchViewer();

        $response = $this->getJson('/api/v2/search?q=@wfuser@remote.test&resolve=1&_pe=1');
        $response->assertOk();

        $accounts = $response->json('accounts');
        expect($accounts)->toHaveCount(1);

        $account = $accounts[0];
        expect($account)->toHaveKeys(['id', 'username', 'acct', 'url']);

        $expected = AccountService::get($profile->id);
        expect(array_keys($account))->toEqual(array_keys($expected));
        expect($account)->toHaveKeys(['location', 'has_story']);
    });

    it('returns no accounts when offset is past the single result', function () {
        [$target, $profile] = makeResolveTarget('@wfuser@remote.test');
        actAsSearchViewer();

        $response = $this->getJson('/api/v2/search?q=@wfuser@remote.test&resolve=1&offset=1');
        $response->assertOk();

        expect($response->json('accounts'))->toBe([]);
    });
});
