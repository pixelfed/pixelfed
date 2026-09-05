<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Registration
|--------------------------------------------------------------------------
*/

it('renders the registration page when open registration is enabled', function () {
    config(['pixelfed.open_registration' => true]);

    $this->get('/register')
        ->assertOk();
});

it('creates a user with valid registration data', function () {
    config(['pixelfed.open_registration' => true]);
    config(['pixelfed.max_users' => 1000]);
    config(['instance.enable_cc' => false]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'testregistration@gmail.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'agree' => 'on',
        'agecheck' => 'on',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', [
        'username' => 'testuser',
    ]);
});

it('rejects registration with missing required fields', function () {
    config(['pixelfed.open_registration' => true]);

    $this->post('/register', [])
        ->assertRedirect()
        ->assertSessionHasErrors();
});

it('rejects registration with a duplicate username', function () {
    config(['pixelfed.open_registration' => true]);
    config(['pixelfed.max_users' => 1000]);

    User::factory()->create(['username' => 'taken']);

    $this->post('/register', [
        'name' => 'Another User',
        'username' => 'taken',
        'email' => 'new@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'agree' => 'on',
    ])->assertRedirect()
        ->assertSessionHasErrors(['username']);
});

it('rejects registration with a duplicate email', function () {
    config(['pixelfed.open_registration' => true]);
    config(['pixelfed.max_users' => 1000]);

    User::factory()->create(['email' => 'existing@example.com']);

    $this->post('/register', [
        'name' => 'Another User',
        'username' => 'newuser',
        'email' => 'existing@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'agree' => 'on',
    ])->assertRedirect()
        ->assertSessionHasErrors(['email']);
});

it('blocks registration when open registration is disabled', function () {
    config(['pixelfed.open_registration' => false]);

    $this->get('/register')
        ->assertStatus(404);
});

it('redirects authenticated users away from the register page', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get('/register')
        ->assertRedirect();
});
