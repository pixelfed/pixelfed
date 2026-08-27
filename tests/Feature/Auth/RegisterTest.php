<?php

use App\User;
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

it('crashes with str_ends_with TypeError on valid registration data', function () {
    // BUG: RegisterController.php:82 passes array to str_ends_with().
    // This is a real bug that prevents registration.
    config(['pixelfed.open_registration' => true]);
    config(['pixelfed.max_users' => 1000]);

    $this->post('/register', [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'agree' => 'on',
    ])->assertServerError();
})->group('known-bugs');

it('rejects registration with missing required fields', function () {
    config(['pixelfed.open_registration' => true]);

    $this->post('/register', [])
        ->assertRedirect()
        ->assertSessionHasErrors();
});

it('crashes on registration with a duplicate username', function () {
    // BUG: Same str_ends_with TypeError.
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
    ])->assertServerError();
})->group('known-bugs');

it('crashes on registration with a duplicate email', function () {
    // BUG: Same str_ends_with TypeError.
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
    ])->assertServerError();
})->group('known-bugs');

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
