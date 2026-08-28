<?php

use App\Mail\PasswordChange;
use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Settings Profile Update Tests
|--------------------------------------------------------------------------
*/

describe('profile update', function () {
    it('updates the display name', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->post('/settings/home', [
                'name' => 'New Display Name',
            ])
            ->assertRedirect('/settings/home');

        $user->profile->refresh();
        expect($user->profile->name)->toBe('New Display Name');
    });

    it('updates the bio', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->post('/settings/home', [
                'bio' => 'This is my updated bio',
            ])
            ->assertRedirect('/settings/home');

        $user->profile->refresh();
        expect($user->profile->bio)->toContain('This is my updated bio');
    });

    it('rejects a bio exceeding max length', function () {
        $user = User::factory()->create();
        $user->refresh();
        $maxLen = config('pixelfed.max_bio_length', 125);

        $this->actingAs($user)
            ->post('/settings/home', [
                'bio' => str_repeat('a', $maxLen + 10),
            ])
            ->assertSessionHasErrors('bio');
    });

    it('rejects an invalid website URL', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->post('/settings/home', [
                'website' => 'not-a-url',
            ])
            ->assertSessionHasErrors('website');
    });

    it('accepts a valid website URL', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->post('/settings/home', [
                'website' => 'https://example.com',
            ])
            ->assertRedirect('/settings/home');

        $user->profile->refresh();
        expect($user->profile->website)->toBe('https://example.com');
    });
});

describe('password change', function () {
    it('changes password with correct current password', function () {
        Mail::fake();

        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/settings/password', [
                'current' => 'old-password',
                'password' => 'new-secure-pass-123',
                'password_confirmation' => 'new-secure-pass-123',
            ])
            ->assertRedirect('/settings/home');

        $user->refresh();
        expect(Hash::check('new-secure-pass-123', $user->password))->toBeTrue();

        Mail::assertSent(PasswordChange::class);
    });

    it('rejects password change with wrong current password', function () {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/settings/password', [
                'current' => 'wrong-password',
                'password' => 'new-secure-pass-123',
                'password_confirmation' => 'new-secure-pass-123',
            ])
            ->assertRedirect();

        $user->refresh();
        expect(Hash::check('old-password', $user->password))->toBeTrue();
    });

    it('rejects password change when new password is same as current', function () {
        $user = User::factory()->create([
            'password' => Hash::make('same-password'),
        ]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/settings/password', [
                'current' => 'same-password',
                'password' => 'same-password',
                'password_confirmation' => 'same-password',
            ])
            ->assertSessionHasErrors('password');
    });

    it('rejects password shorter than minimum length', function () {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post('/settings/password', [
                'current' => 'old-password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    });

    it('requires password confirmation to access the password page', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->get('/settings/password')
            ->assertRedirect(route('password.confirm'));
    });
});
