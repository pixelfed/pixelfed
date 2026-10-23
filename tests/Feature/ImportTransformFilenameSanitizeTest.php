<?php

use App\Models\ImportPost;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| app:transform-imports must find media stored under its sanitized name
|--------------------------------------------------------------------------
|
| ImportPostController::storeMedia writes uploads under a sanitized filename
| (unsafe chars -> "_"), but TransformImports built the lookup path from the
| raw import URI. A filename with spaces/special chars therefore never matched
| on disk and the post was wrongly skipped as missing media. The lookup must
| apply the same sanitization.
|
*/

beforeEach(function () {
    Storage::fake('local');
    config(['filesystems.default' => 'local']);
});

it('imports a post whose media filename contains spaces/special chars', function () {
    $user = User::factory()->create();
    $user->refresh();

    // The raw uri as stored in the import metadata (unsanitized).
    $rawName = 'my photo (1).jpg';
    // How storeMedia() actually wrote it to disk (sanitized).
    $sanitized = 'my_photo__1_.jpg';

    Storage::disk('local')->put('imports/'.$user->id.'/'.$sanitized, 'fake-image-bytes');

    $ip = new ImportPost;
    $ip->profile_id = $user->profile_id;
    $ip->user_id = $user->id;
    $ip->service = 'instagram';
    $ip->filename = $sanitized;
    $ip->media_count = 1;
    $ip->post_type = 'photo';
    $ip->caption = 'hello world';
    $ip->media = [['uri' => 'media/posts/'.$rawName]];
    $ip->creation_year = 21;
    $ip->creation_month = 6;
    $ip->creation_day = 15;
    $ip->creation_date = now()->parse('2021-06-15 12:00:00');
    $ip->skip_missing_media = false;
    $ip->save();

    $this->artisan('app:transform-imports')->assertExitCode(0);

    $ip->refresh();
    expect($ip->status_id)->not->toBeNull();
    expect((bool) $ip->skip_missing_media)->toBeFalse();
});

it('still skips a post whose sanitized media file is genuinely absent', function () {
    $user = User::factory()->create();
    $user->refresh();

    // No file placed on disk at all.
    $ip = new ImportPost;
    $ip->profile_id = $user->profile_id;
    $ip->user_id = $user->id;
    $ip->service = 'instagram';
    $ip->filename = 'missing.jpg';
    $ip->media_count = 1;
    $ip->post_type = 'photo';
    $ip->caption = 'hello world';
    $ip->media = [['uri' => 'media/posts/missing.jpg']];
    $ip->creation_year = 21;
    $ip->creation_month = 6;
    $ip->creation_day = 15;
    $ip->creation_date = now()->parse('2021-06-15 12:00:00');
    $ip->skip_missing_media = false;
    $ip->save();

    $this->artisan('app:transform-imports')->assertExitCode(0);

    $ip->refresh();
    expect($ip->status_id)->toBeNull();
    expect((bool) $ip->skip_missing_media)->toBeTrue();
});
