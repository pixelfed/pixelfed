<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| /authorize_interaction unauthenticated redirect
|--------------------------------------------------------------------------
|
| A guest hitting the remote-follow endpoint must be sent to /login with the
| intended URL stored in session, so login returns them to the interaction.
| The old `?next=` param was never consumed and the flow was lost.
|
*/

beforeEach(function () {
    config(['federation.activitypub.enabled' => true]);
    // validateUrl resolves the host to a public IP; pre-seed so it passes
    // without a real DNS lookup.
    Cache::put('helpers:url:public-ips:'.hash('xxh128', 'remote.example'), ['203.0.113.70'], 3600);
});

it('redirects a guest to /login and stores the intended interaction url', function () {
    $uri = 'https://remote.example/users/alice';
    $target = '/authorize_interaction?'.http_build_query(['uri' => $uri]);

    $response = $this->get($target);

    $response->assertRedirect('/login');
    // Laravel's intended-redirect key holds the full authorize_interaction URL.
    expect(session('url.intended'))->toContain('/authorize_interaction');
    expect(session('url.intended'))->toContain('uri=');
});

it('redirects to plain /login without the unused next query param', function () {
    $uri = 'https://remote.example/users/alice';
    $target = '/authorize_interaction?'.http_build_query(['uri' => $uri]);

    $location = $this->get($target)->headers->get('Location');

    // The old ?next= param was never consumed; the redirect target is /login
    // and the intended URL lives in the session instead.
    expect($location)->toEndWith('/login');
    expect($location)->not->toContain('next=');
});
