<?php

use App\Models\User;
use App\Models\UserRoles;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| POST /api/v2/media can-post role enforcement
|--------------------------------------------------------------------------
|
| The Mastodon-compatible v2 media endpoint must enforce the same can-post
| role check as POST /api/v1/media. A restricted user (has_roles = true with
| can-post = false, e.g. via Parental Controls) could previously upload media
| via v2 despite being blocked on v1.
|
*/

function restrictedRoleUser(array $roles): User
{
    $user = User::factory()->create([
        'has_roles' => true,
        'last_active_at' => now(),
    ]);
    $user->refresh();

    UserRoles::create([
        'profile_id' => $user->profile_id,
        'user_id' => $user->id,
        'roles' => $roles,
    ]);

    return $user;
}

beforeEach(function () {
    Storage::fake(config('filesystems.default'));
    config(['pixelfed.enforce_account_limit' => false]);
});

it('rejects a can-post=false user on POST /api/v1/media with 403', function () {
    $user = restrictedRoleUser(['can-post' => false]);
    Passport::actingAs($user, ['write']);

    $this->postJson('/api/v1/media', [
        'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
    ])->assertStatus(403);
});

it('rejects a can-post=false user on POST /api/v2/media with 403', function () {
    $user = restrictedRoleUser(['can-post' => false]);
    Passport::actingAs($user, ['write']);

    $this->postJson('/api/v2/media', [
        'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
    ])->assertStatus(403);
});

it('allows a can-post=true user on POST /api/v2/media', function () {
    $user = restrictedRoleUser(['can-post' => true]);
    Passport::actingAs($user, ['write']);

    $this->postJson('/api/v2/media', [
        'file' => UploadedFile::fake()->image('test.jpg', 100, 100),
        'description' => 'test image',
    ])
        ->assertAccepted()
        ->assertJsonStructure(['id', 'type', 'url', 'preview_url']);
});
