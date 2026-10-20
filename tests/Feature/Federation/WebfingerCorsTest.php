<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Webfinger CORS header tests
|--------------------------------------------------------------------------
|
| The webfinger endpoint caches its response. The cached (cache-hit) path
| must send the same Access-Control-Allow-Origin header as the fresh path,
| otherwise cross-origin discovery breaks after the first lookup.
|
*/

describe('webfinger CORS', function () {
    it('sends the CORS header on acct-form cache miss and cache hit', function () {
        $user = User::factory()->create(['username' => 'wfcorsuser']);
        $user->refresh();
        $domain = config('pixelfed.domain.app');

        $resource = "/.well-known/webfinger?resource=acct:{$user->username}@{$domain}";

        $miss = $this->getJson($resource)
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonStructure(['subject', 'aliases', 'links']);

        $hit = $this->getJson($resource)
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonStructure(['subject', 'aliases', 'links']);

        expect($hit->json())->toEqual($miss->json());
    });

    it('sends the CORS header on url-form cache miss and cache hit', function () {
        $user = User::factory()->create(['username' => 'wfcorsurluser']);
        $user->refresh();
        $domain = config('pixelfed.domain.app');

        $resource = "/.well-known/webfinger?resource=https://{$domain}/users/{$user->username}";

        $miss = $this->getJson($resource)
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonStructure(['subject', 'aliases', 'links']);

        $hit = $this->getJson($resource)
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonStructure(['subject', 'aliases', 'links']);

        expect($hit->json())->toEqual($miss->json());
    });
});
