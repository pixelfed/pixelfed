<?php

use App\Models\Media;
use App\Models\MediaBlocklist;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ComposeController::mediaUpdate must enforce the content blocklist
|--------------------------------------------------------------------------
|
| mediaUpload checks MediaBlocklistService::exists($hash) before storing, but
| the sibling mediaUpdate overwrote the on-disk file with new, unvalidated
| bytes. A blocklisted file rejected by upload could be swapped in via update.
|
*/

// A tiny valid 1x1 GIF (GIF is a deterministic oracle: not re-encoded).
function gifBytes(): string
{
    return base64_decode('R0lGODdhAQABAIAAAP///////ywAAAAAAQABAAACAkQBADs=');
}

function gifUpload(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'blk').'.gif';
    file_put_contents($path, gifBytes());

    return new UploadedFile($path, 'x.gif', 'image/gif', null, true);
}

beforeEach(function () {
    config(['instance.enable_cc' => false]);
    config(['pixelfed.media_types' => 'image/jpeg,image/png,image/gif']);
    config(['pixelfed.max_photo_size' => 15000]);
    Storage::fake('local');
});

it('rejects a blocklisted file swapped in via mediaUpdate', function () {
    $user = User::factory()->create();
    $user->refresh();

    $hash = hash('sha256', gifBytes());

    // Operator blocklists the file by sha256.
    $bl = new MediaBlocklist;
    $bl->sha256 = $hash;
    $bl->active = true;
    $bl->save();

    // Existing draft media row owned by the user (no status yet).
    $mediaPath = 'public/m/_v2/'.$user->profile_id.'/aa/bb/orig.gif';
    Storage::disk('local')->put($mediaPath, 'placeholder');
    $media = new Media;
    $media->user_id = $user->id;
    $media->profile_id = $user->profile_id;
    $media->media_path = $mediaPath;
    $media->mime = 'image/gif';
    $media->size = 11;
    $media->save();

    // Swapping the blocklisted bytes in via update must be rejected (451).
    $this->actingAs($user)
        ->post('/api/compose/v0/media/update', [
            'id' => $media->id,
            'file' => gifUpload(),
        ])
        ->assertStatus(451);

    // The blocklisted bytes must not have overwritten the stored file.
    expect(hash('sha256', Storage::disk('local')->get($mediaPath)))->not->toBe($hash);
});

it('allows a non-blocklisted file via mediaUpdate', function () {
    $user = User::factory()->create();
    $user->refresh();

    $mediaPath = 'public/m/_v2/'.$user->profile_id.'/cc/dd/orig.gif';
    Storage::disk('local')->put($mediaPath, 'placeholder');
    $media = new Media;
    $media->user_id = $user->id;
    $media->profile_id = $user->profile_id;
    $media->media_path = $mediaPath;
    $media->mime = 'image/gif';
    $media->size = 11;
    $media->save();

    $this->actingAs($user)
        ->post('/api/compose/v0/media/update', [
            'id' => $media->id,
            'file' => gifUpload(),
        ])
        ->assertOk();

    // The row is kept in sync with the new bytes.
    expect($media->fresh()->original_sha256)->toBe(hash('sha256', gifBytes()));
});
