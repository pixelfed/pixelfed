<?php

use App\Models\Avatar;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Avatar Update API Tests
|--------------------------------------------------------------------------
|
| Regression coverage for the previously silent failure in
| BaseApiController@avatarUpdate, where any exception during upload was
| swallowed and the endpoint still returned a 200 "success" response.
|
*/

describe('POST /api/v1/avatar/update', function () {
    it('returns an error instead of a false success when processing fails', function () {
        $user = User::factory()->create();
        $user->refresh();

        // Ensure no Avatar row exists so the internal firstOrFail() throws.
        Avatar::whereProfileId($user->profile_id)->delete();

        Storage::fake('local');
        Passport::actingAs($user, ['write']);

        $this->postJson('/api/v1/avatar/update', [
            'upload' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ])
            ->assertStatus(500)
            ->assertJsonFragment(['code' => 500])
            ->assertJsonMissing(['msg' => 'Avatar successfully updated']);
    });

    it('updates the avatar with a valid upload', function () {
        $user = User::factory()->create();
        $user->refresh();

        Avatar::updateOrCreate(
            ['profile_id' => $user->profile_id],
            ['media_path' => 'public/avatars/default.jpg', 'change_count' => 0]
        );

        Storage::fake('local');
        Passport::actingAs($user, ['write']);

        $this->postJson('/api/v1/avatar/update', [
            'upload' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ])
            ->assertOk()
            ->assertJsonFragment(['msg' => 'Avatar successfully updated']);
    });

    it('rejects a non-image upload with a validation error', function () {
        $user = User::factory()->create();
        $user->refresh();

        Storage::fake('local');
        Passport::actingAs($user, ['write']);

        $this->postJson('/api/v1/avatar/update', [
            'upload' => UploadedFile::fake()->create('malware.pdf', 100, 'application/pdf'),
        ])
            ->assertStatus(422);
    });
});
