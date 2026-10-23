<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| app:import-upload-clean-storage must only evaluate top-level user dirs
|--------------------------------------------------------------------------
|
| The GC used Storage::allDirectories('imports'), which recurses, then read
| the last path segment as a user id. So a valid user's nested folder like
| imports/{id}/media resolved to uid "media", matched no user, and was
| deleted — destroying that user's import data. Only imports/{user_id} may
| be evaluated.
|
*/

beforeEach(function () {
    Storage::fake('local');
});

it('keeps a valid users import dir and its nested subfolders', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Valid user's import tree, including a nested subfolder.
    Storage::disk('local')->put('imports/'.$user->id.'/photo.jpg', 'x');
    Storage::disk('local')->put('imports/'.$user->id.'/media/nested.jpg', 'y');

    $this->artisan('app:import-upload-clean-storage')->assertExitCode(0);

    expect(Storage::disk('local')->exists('imports/'.$user->id.'/photo.jpg'))->toBeTrue();
    expect(Storage::disk('local')->exists('imports/'.$user->id.'/media/nested.jpg'))->toBeTrue();
});

it('deletes an import dir for a non-existent user', function () {
    $orphanId = 999999;
    Storage::disk('local')->put('imports/'.$orphanId.'/photo.jpg', 'x');

    $this->artisan('app:import-upload-clean-storage')->assertExitCode(0);

    expect(Storage::disk('local')->exists('imports/'.$orphanId.'/photo.jpg'))->toBeFalse();
});

it('deletes an import dir for a suspended user', function () {
    $user = User::factory()->create();
    $user->refresh();
    $user->status = 'delete';
    $user->save();

    Storage::disk('local')->put('imports/'.$user->id.'/photo.jpg', 'x');

    $this->artisan('app:import-upload-clean-storage')->assertExitCode(0);

    // whereNull('status') excludes the suspended user, so the dir is removed.
    expect(Storage::disk('local')->exists('imports/'.$user->id.'/photo.jpg'))->toBeFalse();
});
