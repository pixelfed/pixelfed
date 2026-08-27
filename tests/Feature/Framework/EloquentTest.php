<?php

use App\Profile;
use App\Status;
use App\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Eloquent Model Integration Tests
|--------------------------------------------------------------------------
|
| Verify that models, relationships, casts, scopes, and factories
| work correctly with the current framework version.
|
*/

describe('User model', function () {
    it('creates a user via factory', function () {
        $user = User::factory()->create();

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->id)->toBeInt()
            ->and($user->email)->toBeString();
    });

    it('creates a profile automatically via observer', function () {
        $user = User::factory()->create();
        $user->refresh();

        expect($user->profile)->not->toBeNull()
            ->and($user->profile)->toBeInstanceOf(Profile::class)
            ->and($user->profile->username)->toBe($user->username);
    });

    it('creates user settings automatically via observer', function () {
        $user = User::factory()->create();
        $user->refresh();

        expect($user->settings)->not->toBeNull();
    });

    it('casts email_verified_at to datetime', function () {
        $user = User::factory()->create();

        expect($user->email_verified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

    it('hides sensitive attributes in serialization', function () {
        $user = User::factory()->create();
        $array = $user->toArray();

        expect($array)->not->toHaveKey('password')
            ->not->toHaveKey('remember_token')
            ->not->toHaveKey('2fa_secret');
    });

    it('creates an admin user via factory state', function () {
        $admin = User::factory()->admin()->create();

        expect($admin->is_admin)->toBeTrue();
    });

    it('creates an unverified user via factory state', function () {
        $user = User::factory()->unverified()->create();

        expect($user->email_verified_at)->toBeNull();
    });
});

describe('Profile model', function () {
    it('creates a profile via factory', function () {
        $profile = Profile::factory()->create();

        expect($profile)->toBeInstanceOf(Profile::class)
            ->and($profile->username)->toBeString();
    });

    it('belongs to a user', function () {
        $user = User::factory()->create();
        $user->refresh();

        expect($user->profile->user->id)->toBe($user->id);
    });

    it('soft deletes without removing from database', function () {
        $profile = Profile::factory()->create();
        $id = $profile->id;

        $profile->delete();

        expect(Profile::find($id))->toBeNull()
            ->and(Profile::withTrashed()->find($id))->not->toBeNull();
    });

    it('creates a private profile via factory state', function () {
        $profile = Profile::factory()->private()->create();

        expect($profile->is_private)->toBeTrue();
    });
});

describe('Status model', function () {
    it('creates a status via factory', function () {
        $status = Status::factory()->create();

        expect($status)->toBeInstanceOf(Status::class)
            ->and($status->profile_id)->not->toBeNull();
    });

    it('belongs to a profile', function () {
        $status = Status::factory()->create();

        expect($status->profile)->toBeInstanceOf(Profile::class);
    });

    it('has visibility scopes', function () {
        $user = User::factory()->create();
        $user->refresh();

        Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'public']);
        Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'private']);
        Status::factory()->create(['profile_id' => $user->profile_id, 'scope' => 'unlisted']);

        $public = Status::where('profile_id', $user->profile_id)
            ->whereIn('scope', ['public', 'unlisted'])
            ->count();

        expect($public)->toBe(2);
    });

    it('soft deletes without removing from database', function () {
        $status = Status::factory()->create();
        $id = $status->id;

        $status->delete();

        expect(Status::find($id))->toBeNull()
            ->and(Status::withTrashed()->find($id))->not->toBeNull();
    });

    it('creates typed statuses via factory states', function () {
        $photo = Status::factory()->photo()->create();
        $private = Status::factory()->private()->create();
        $nsfw = Status::factory()->nsfw()->create();

        expect($photo->type)->toBe('photo')
            ->and($private->scope)->toBe('private')
            ->and($nsfw->is_nsfw)->toBeTrue();
    });
});
