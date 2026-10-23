<?php

use App\Models\User;
use App\Services\MediaBlocklistService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Media blocklist ordering (check before store)
|--------------------------------------------------------------------------
|
| A blocklisted upload must be rejected BEFORE the file is written to storage,
| otherwise it leaves an orphaned file that media:gc (which only reaps files
| with a Media row) never cleans up.
|
*/

it('does not persist a file when the upload is blocklisted', function () {
    Storage::fake(config('filesystems.default'));

    $user = User::factory()->create();
    $user->refresh();

    // Build the upload first so we can blocklist its exact hash.
    $file = UploadedFile::fake()->image('blocked.jpg', 1080, 1080);
    $hash = hash_file('sha256', $file->getRealPath());

    MediaBlocklistService::add($hash, ['note' => 'test']);

    $this->actingAs($user)
        ->post('/api/compose/v0/media/upload', ['file' => $file])
        ->assertStatus(451);

    // No media (image) file was written to the default disk. Other unrelated
    // service files (e.g. cached stats json) may exist, so filter to images.
    $imageFiles = collect(Storage::disk(config('filesystems.default'))->allFiles())
        ->filter(fn ($p) => preg_match('/\.(jpe?g|png|gif|webp)$/i', $p))
        ->values();

    expect($imageFiles)->toBeEmpty();
});

it('accepts a non-blocklisted upload', function () {
    Storage::fake(config('filesystems.default'));

    $user = User::factory()->create();
    $user->refresh();

    $file = UploadedFile::fake()->image('ok.jpg', 1080, 1080);

    $this->actingAs($user)
        ->post('/api/compose/v0/media/upload', ['file' => $file])
        ->assertOk();
});
