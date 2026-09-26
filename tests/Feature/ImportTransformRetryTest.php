<?php

use App\Models\ImportPost;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| app:transform-imports must survive a recoverable unique-id collision
|--------------------------------------------------------------------------
|
| A concurrent import can win the statuses.id insert race, so the losing
| transaction fails with SQLSTATE 23000. The command used to catch that and
| permanently mark the ImportPost skipped. It now retries with a
| freshly-computed id. This test covers the end-to-end happy path through the
| refactored retry loop (guarding against the refactor breaking normal
| imports); the true concurrent race is not single-process reproducible.
|
*/

beforeEach(function () {
    Storage::fake('local');
    config(['filesystems.default' => 'local']);
});

function makeImportPost(User $user): ImportPost
{
    $filename = 'photo1.jpg';

    // The command reads imports/{user_id}/{filename} from disk.
    Storage::disk('local')->put('imports/'.$user->id.'/'.$filename, 'fake-image-bytes');

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

    return $ip;
}

it('imports a post end-to-end through the retry loop', function () {
    $user = User::factory()->create();
    $user->refresh();

    $ip = makeImportPost($user);

    $this->artisan('app:transform-imports')->assertExitCode(0);

    $ip->refresh();
    expect($ip->status_id)->not->toBeNull();
    expect((bool) $ip->skip_missing_media)->toBeFalse();

    // A real status row was created with the reserved id.
    expect(Status::find($ip->status_id))->not->toBeNull();
});

it('assigns a fresh id and imports when the computed status id is already taken', function () {
    $user = User::factory()->create();
    $user->refresh();

    $ip = makeImportPost($user);

    // Occupy the id getUniqueCreationId() would compute first (incr = 1) so it
    // must skip to the next id rather than collide on insert.
    $uid = str_pad((string) $user->id, 6, '0', STR_PAD_LEFT);
    $takenId = '1'.$uid.'210615'.'001';
    DB::table('statuses')->insert([
        'id' => $takenId,
        'profile_id' => $user->profile_id,
        'caption' => 'existing',
        'rendered' => '',
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'local' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->artisan('app:transform-imports')->assertExitCode(0);

    $ip->refresh();
    // The post imported (not skipped) with a different, unused id.
    expect($ip->status_id)->not->toBeNull();
    expect((string) $ip->status_id)->not->toBe($takenId);
    expect((bool) $ip->skip_missing_media)->toBeFalse();
    expect(Status::find($ip->status_id))->not->toBeNull();
});
