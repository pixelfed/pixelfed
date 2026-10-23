<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Password Confirmation (Sudo Mode)
|--------------------------------------------------------------------------
*/

it('renders the password confirmation page', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee('Confirm Password');
});

it('confirms password with correct credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('my-password'),
    ]);
    $user->refresh();

    $this->actingAs($user)
        ->post('/i/auth/sudo', [
            'password' => 'my-password',
        ])->assertRedirect();

    $this->actingAs($user)
        ->get('/settings/security')
        ->assertOk();
});

it('rejects password confirmation with wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('my-password'),
    ]);
    $user->refresh();

    $this->actingAs($user)
        ->post('/i/auth/sudo', [
            'password' => 'wrong-password',
        ])->assertRedirect()
        ->assertSessionHasErrors('password');
});

it('redirects protected routes to password confirmation', function () {
    $user = User::factory()->create();
    $user->refresh();

    $protectedRoutes = [
        '/settings/security',
        '/settings/applications',
        '/settings/data-export',
    ];

    foreach ($protectedRoutes as $route) {
        $this->actingAs($user)
            ->get($route)
            ->assertRedirect(route('password.confirm'));
    }
});
