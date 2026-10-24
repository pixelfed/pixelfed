<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\MessageBag;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Curated registration age gate must be enforced server-side
|--------------------------------------------------------------------------
|
| Step 1's age verification was client-side only, and a Blade $errors->any()
| short-circuit let a user who first failed step 2 skip the date-of-birth
| prompt entirely. proceed() now requires the age_verified flag on step 1,
| so the gate holds even if the client script is bypassed.
|
*/

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    config(['instance.enable_cc' => false]);
    config(['instance.curated_registration.enabled' => true]);
    config(['pixelfed.open_registration' => false]);
    config(['instance.curated_registration.state.fallback_on_closed_reg' => true]);
    config(['instance.curated_registration.state.only_enabled_on_closed_reg' => false]);
});

it('rejects step 1 without age verification', function () {
    $this->post('/auth/sign_up', ['step' => 1])
        ->assertSessionHasErrors('age_verified');
});

it('rejects step 1 when age_verified is not accepted', function () {
    $this->post('/auth/sign_up', ['step' => 1, 'age_verified' => '0'])
        ->assertSessionHasErrors('age_verified');
});

it('advances past step 1 when age is verified', function () {
    $this->post('/auth/sign_up', ['step' => 1, 'age_verified' => '1'])
        ->assertOk()
        ->assertSessionHasNoErrors();
});

it('does not expose an errors-based bypass of the age gate', function () {
    // Simulate the old exploit: a prior request flashed validation errors,
    // then step 1 is submitted with no age verification. It must still fail.
    $this->from('/auth/sign_up')
        ->withSession(['errors' => new MessageBag(['password' => ['bad']])])
        ->post('/auth/sign_up', ['step' => 1])
        ->assertSessionHasErrors('age_verified');
});
