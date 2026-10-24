<?php

use App\Models\ImportPost;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| app:transform-imports must preserve source media until commit
|--------------------------------------------------------------------------
|
| The command moved media out of imports/ before the DB transaction, then on
| failure deleted the moved destination — destroying the only copy and losing
| the user's media permanently. It now copies before the transaction, deletes
| the source only after a successful commit, and on failure removes only the
| copy so the source survives for retry.
|
*/

beforeEach(function () {
    Storage::fake('local');
    config(['filesystems.default' => 'local']);
});

function makeImportFixture(User $user): array
{
    $filename = 'photo1.jpg';
    $source = 'imports/'.$user->id.'/'.$filename;
    Storage::disk('local')->put($source, 'fake-image-bytes');

    $ip = new ImportPost;
    $ip->profile_id = $user->profile_id;
    $ip->user_id = $user->id;
    $ip->service = 'instagram';
    $ip->filename = $filename;
    $ip->media_count = 1;
    $ip->post_type = 'photo';
    $ip->caption = 'hello world';
    $ip->media = [['uri' => 'media/posts/'.$filename]];
    $ip->creation_year = 21;
    $ip->creation_month = 6;
    $ip->creation_day = 15;
    $ip->creation_date = now()->parse('2021-06-15 12:00:00');
    $ip->skip_missing_media = false;
    $ip->save();

    return [$ip, $source];
}

it('deletes the source only after a successful import', function () {
    $user = User::factory()->create();
    $user->refresh();
    [$ip, $source] = makeImportFixture($user);

    $this->artisan('app:transform-imports')->assertExitCode(0);

    $ip->refresh();
    expect($ip->status_id)->not->toBeNull();

    // Source consumed, destination present.
    expect(Storage::disk('local')->exists($source))->toBeFalse();
    $media = Media::whereStatusId($ip->status_id)->first();
    expect($media)->not->toBeNull();
    expect(Storage::disk('local')->exists($media->media_path))->toBeTrue();
});

it('preserves the source and removes the copy when the import fails', function () {
    $user = User::factory()->create();
    $user->refresh();
    [$ip, $source] = makeImportFixture($user);

    // Force a failure inside the transaction: Media::save() fires events, so a
    // creating listener that throws aborts the transaction after the file copy.
    Media::creating(function () {
        throw new RuntimeException('simulated db failure');
    });

    $this->artisan('app:transform-imports')->assertExitCode(0);

    // The source must still be there (no data loss, retryable)...
    expect(Storage::disk('local')->exists($source))->toBeTrue();

    // ...and the copied destination must have been cleaned up (no orphan).
    $files = collect(Storage::disk('local')->allFiles('public'))
        ->filter(fn ($f) => str_ends_with($f, '.jpg'));
    expect($files)->toBeEmpty();

    // The post was marked skipped, and no media/status row leaked.
    $ip->refresh();
    expect((bool) $ip->skip_missing_media)->toBeTrue();
    expect($ip->status_id)->toBeNull();
});
