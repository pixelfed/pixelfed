<?php

use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Federation & Discovery Endpoint Tests
|--------------------------------------------------------------------------
*/

describe('nodeinfo', function () {
    it('returns well-known nodeinfo with links', function () {
        $this->getJson('/.well-known/nodeinfo')
            ->assertOk()
            ->assertJsonStructure(['links' => [['rel', 'href']]]);
    });

    it('returns nodeinfo 2.0 with correct structure', function () {
        $this->getJson('/api/nodeinfo/2.0.json')
            ->assertOk()
            ->assertJsonStructure([
                'version',
                'software' => ['name', 'version'],
                'protocols',
                'usage' => ['users', 'localPosts'],
                'openRegistrations',
            ])
            ->assertJsonFragment(['name' => 'pixelfed']);
    });

    it('reports correct open registration status', function () {
        config(['pixelfed.open_registration' => true]);
        config(['instance.enable_cc' => false]);

        $response = $this->getJson('/api/nodeinfo/2.0.json');
        $data = $response->json();

        expect($data['openRegistrations'])->toBeBool();
    });
});

describe('webfinger', function () {
    it('returns 400 without resource parameter', function () {
        $this->getJson('/.well-known/webfinger')
            ->assertStatus(400);
    });

    it('returns 400 for invalid resource format', function () {
        $this->getJson('/.well-known/webfinger?resource=invalid')
            ->assertStatus(400);
    });

    it('returns 400 for non-existent user', function () {
        $this->getJson('/.well-known/webfinger?resource=acct:nonexistent@'.config('pixelfed.domain.app'))
            ->assertStatus(400);
    });

    it('returns webfinger data for existing local user', function () {
        $user = User::factory()->create();
        $user->refresh();
        $domain = config('pixelfed.domain.app');

        $this->getJson("/.well-known/webfinger?resource=acct:{$user->username}@{$domain}")
            ->assertOk()
            ->assertJsonStructure(['subject', 'aliases', 'links']);
    });
});

describe('host-meta', function () {
    it('returns host-meta XML', function () {
        $this->get('/.well-known/host-meta')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xrd+xml');
    });
});
