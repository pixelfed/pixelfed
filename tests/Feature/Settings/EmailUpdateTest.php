<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| POST /settings/email
|--------------------------------------------------------------------------
|
| The email form is pre-filled with the user's current address, so submitting
| unchanged must be a no-op (the unique rule must ignore the user's own row),
| while a genuinely new email still persists and collisions with other
| accounts still fail.
|
*/

it('accepts an unchanged email submission as a no-op', function () {
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/settings/email', ['email' => $user->email])
        ->assertRedirect('/settings/email')
        ->assertSessionHasNoErrors();
});

it('persists a genuinely new email', function () {
    $user = User::factory()->create();
    $user->refresh();

    $newEmail = 'brand.new.'.uniqid().'@example.com';

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/settings/email', ['email' => $newEmail])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe($newEmail);
});

it('rejects an email already used by another account', function () {
    $other = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();
    $user->refresh();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/settings/email', ['email' => 'taken@example.com'])
        ->assertSessionHasErrors('email');

    expect($user->fresh()->email)->not->toBe('taken@example.com');
});
