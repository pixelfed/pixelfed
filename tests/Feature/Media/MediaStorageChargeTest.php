<?php

use App\Enums\MediaQuotaStatus;
use App\Models\Media;
use App\Models\User;
use App\Services\UserStorageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Media storage accounting: enforce on raw, charge raw, correct to optimized
|--------------------------------------------------------------------------
|
| Upload-time quota enforcement uses the RAW upload size (a user cannot upload
| an original larger than their remaining quota). The raw size is charged to
| storage_used immediately at upload (quota_status = OriginalSize) so the quota
| never under-counts while optimization is queued, then the async finalize job
| corrects it down to the optimized media.size (quota_status = OptimizedSize).
|
*/

it('rejects an upload when the raw size exceeds the remaining quota', function () {
    Storage::fake(config('filesystems.default'));
    config([
        'pixelfed.enforce_account_limit' => true,
        'pixelfed.max_account_size' => 1000, // 1000 KB cap
    ]);

    $user = User::factory()->create();
    $user->refresh();

    // Already near the cap.
    $user->storage_used = 990;
    $user->storage_used_updated_at = now();
    $user->save();

    // A ~1.4 MB raw image pushes the raw-size projection over the cap.
    $file = UploadedFile::fake()->image('big.jpg', 4000, 4000)->size(1400);

    $this->actingAs($user)
        ->post('/api/compose/v0/media/upload', ['file' => $file])
        ->assertStatus(403);

    // Nothing charged: the counter is unchanged and no media row persisted.
    $user->refresh();
    expect((int) $user->storage_used)->toBe(990);
    expect(Media::where('user_id', $user->id)->count())->toBe(0);
});

it('charges the raw size at upload and marks the media OriginalSize', function () {
    Storage::fake(config('filesystems.default'));
    config([
        'pixelfed.enforce_account_limit' => true,
        'pixelfed.max_account_size' => 1000000,
    ]);

    // Don't run the finalize jobs; assert the upload (controller) half only.
    Bus::fake();

    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 100;
    $user->storage_used_updated_at = now();
    $user->save();

    $file = UploadedFile::fake()->image('ok.jpg', 1080, 1080);

    $this->actingAs($user)
        ->post('/api/compose/v0/media/upload', ['file' => $file])
        ->assertOk();

    $media = Media::where('user_id', $user->id)->first();

    expect($media)->not->toBeNull()
        ->and((int) $media->original_size)->toBeGreaterThan(0)
        ->and($media->quota_status)->toBe(MediaQuotaStatus::OriginalSize);

    // storage_used grew by the raw upload size immediately (never under-counts).
    $expected = 100 + (int) ceil($media->original_size / 1000);
    $user->refresh();
    expect((int) $user->storage_used)->toBe($expected);
});

it('corrects the quota down to the optimized size when the finalize job runs', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 0;
    $user->storage_used_updated_at = now();
    $user->save();

    // Uploaded (raw 900 KB) and charged at upload.
    $media = Media::create([
        'status_id' => null,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/final.jpeg',
        'mime' => 'image/jpeg',
        'size' => 900000,
        'original_size' => 900000,
        'quota_status' => MediaQuotaStatus::Pending,
        'order' => 1,
    ]);

    UserStorageService::chargeOriginal($media);
    $user->refresh();
    expect((int) $user->storage_used)->toBe(900);

    // Finalize job optimizes to 320 KB then corrects the quota.
    $media->size = 320000;
    $media->save();
    UserStorageService::chargeOptimized($media->fresh());

    $user->refresh();
    expect((int) $user->storage_used)->toBe(320)
        ->and($media->fresh()->quota_status)->toBe(MediaQuotaStatus::OptimizedSize);
});
