<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Public Route Smoke Tests
|--------------------------------------------------------------------------
|
| These tests verify that publicly accessible routes return expected
| HTTP status codes without authentication. They catch routing errors,
| missing controllers, and broken view rendering.
|
*/

test('login page loads', function () {
    $this->get('/login')
        ->assertStatus(200);
});

test('well-known nodeinfo returns json', function () {
    $this->getJson('/.well-known/nodeinfo')
        ->assertStatus(200)
        ->assertJsonStructure(['links']);
});

test('webfinger returns 400 without resource param', function () {
    $this->getJson('/.well-known/webfinger')
        ->assertStatus(400);
});

test('discover page respects public config', function () {
    config(['instance.discover.public' => true]);

    $this->get('/discover')
        ->assertStatus(200);
});

describe('routes that require database', function () {
    uses(RefreshDatabase::class);

    test('register page loads', function () {
        $this->get('/register')
            ->assertStatus(200);
    });

    test('nodeinfo endpoint returns json', function () {
        $this->getJson('/api/nodeinfo/2.0.json')
            ->assertStatus(200)
            ->assertJsonStructure(['version', 'software', 'usage']);
    });

    test('api v1 instance returns json', function () {
        $this->getJson('/api/v1/instance')
            ->assertStatus(200)
            ->assertJsonStructure(['uri', 'title', 'description']);
    });

    test('homepage returns 200 for unauthenticated user', function () {
        $this->get('/')
            ->assertStatus(200);
    });

    test('api v1 apps endpoint accepts POST', function () {
        $this->postJson('/api/v1/apps', [
            'client_name' => 'Test App',
            'redirect_uris' => 'urn:ietf:wg:oauth:2.0:oob',
            'scopes' => 'read',
        ])
            ->assertStatus(200)
            ->assertJsonStructure(['client_id', 'client_secret']);
    });
});
