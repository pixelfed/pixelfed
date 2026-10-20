<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| External-link redirect preserves a correctly-encoded url
|--------------------------------------------------------------------------
|
| The DM external-link interstitial builds /i/redirect?url=<value> and the
| value must be percent-encoded as a single query parameter. When it is, the
| server has to honour the full url, including its own query string, rather
| than parsing stray key=value pairs off the end and redirecting to a
| truncated target. A url on the app's own domain skips the network
| resolution check so this stays deterministic.
|
*/

it('keeps the full target url when the value is percent-encoded', function () {
    $target = 'https://pixelfed.test/page?a=1&b=2';

    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/i/redirect?url='.rawurlencode($target))
        ->assertOk()
        ->assertSee($target);
});

it('404s when the encoded url carries no stray query pairs to the controller', function () {
    $user = User::factory()->create();
    $user->refresh();

    // A raw (unencoded) ampersand splits into a separate query param, so the
    // controller only ever sees the truncated head of the url. The encoded
    // form above is what the fixed client always sends.
    $this->actingAs($user)
        ->get('/i/redirect?url=https://pixelfed.test/page?a=1&b=2')
        ->assertOk()
        ->assertDontSee('https://pixelfed.test/page?a=1&b=2', false);
});
