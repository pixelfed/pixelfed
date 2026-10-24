<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Compose Tests
|--------------------------------------------------------------------------
*/

describe('compose page', function () {
    it('loads for authenticated user', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->get('/i/compose')
            ->assertOk();
    });

    it('requires authentication', function () {
        $this->get('/i/compose')
            ->assertStatus(403);
    });
});

describe('compose settings', function () {
    it('returns compose settings for authenticated user', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->getJson('/api/compose/v0/settings')
            ->assertOk()
            ->assertJsonStructure(['max_altext_length']);
    });
});

describe('media upload', function () {
    it('rejects upload without file', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->postJson('/api/compose/v0/media/upload', [])
            ->assertUnprocessable();
    });

    it('rejects file exceeding max size', function () {
        $user = User::factory()->create();
        $user->refresh();
        $maxSize = (int) config('pixelfed.max_photo_size', 15000);

        // Create a file larger than max
        $file = UploadedFile::fake()->create('large.jpg', $maxSize + 1000, 'image/jpeg');

        $this->actingAs($user)
            ->postJson('/api/compose/v0/media/upload', [
                'file' => $file,
            ])
            ->assertUnprocessable();
    });

    it('rejects unsupported file types', function () {
        $user = User::factory()->create();
        $user->refresh();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->postJson('/api/compose/v0/media/upload', [
                'file' => $file,
            ])
            ->assertUnprocessable();
    });
});

describe('hashtag autocomplete', function () {
    it('returns results for hashtag search', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->getJson('/api/compose/v0/search/tag?q=pixel')
            ->assertOk()
            ->assertJsonIsArray();
    });

    it('rejects empty query', function () {
        $user = User::factory()->create();
        $user->refresh();

        $this->actingAs($user)
            ->getJson('/api/compose/v0/search/tag?q=')
            ->assertStatus(422);
    });
});
