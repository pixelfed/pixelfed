<?php

use App\Enums\MediaQuotaStatus;
use App\Jobs\MediaPipeline\MediaDeletePipeline;
use App\Models\Media;
use App\Models\Status;
use App\Models\User;
use App\Services\UserStorageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| MediaDeletePipeline skip logging
|--------------------------------------------------------------------------
|
| When media is still attached to a status, the delete job must skip deletion
| and log rich context (media/status/profile ids, paths, etc.) so operators
| can trace why an orphan purge was skipped instead of a bare message.
|
*/

it('skips deletion and logs metadata when media is still attached to a status', function () {
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create(['profile_id' => $user->profile->id, 'type' => 'photo']);

    $media = Media::create([
        'status_id' => $status->id,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/abc.jpeg',
        'mime' => 'image/jpeg',
        'size' => 12345,
        'order' => 1,
    ]);

    Log::shouldReceive('info')
        ->once()
        ->withArgs(function ($message, $context = []) use ($media, $status, $user) {
            return $message === 'MediaDeletePipeline: Media is attached to a status, skipping deletion'
                && $context['media_id'] === $media->id
                && $context['status_id'] === $status->id
                && $context['profile_id'] === $user->profile->id
                && $context['user_id'] === $user->id
                && $context['mime'] === 'image/jpeg'
                && $context['media_path'] === 'public/m/_v2/1/abc.jpeg';
        });

    (new MediaDeletePipeline($media))->handle();

    // Media must not be deleted while still attached.
    expect(Media::whereId($media->id)->exists())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| MediaDeletePipeline storage decrement
|--------------------------------------------------------------------------
|
| When orphaned media is deleted, the owner's users.storage_used must be
| decremented by the deleted media's size so freed space is returned to their
| account size quota. Without this, storage_used only ever grows and users can
| hit the account size limit even though their real usage is low (#7169).
|
| The delete path decrements incrementally (it does NOT re-sum the media table);
| drift is reconciled by the scheduled user:storage:recalculate command.
|
*/

it('decrements the owner storage_used by the deleted media size', function () {
    $user = User::factory()->create();
    $user->refresh();

    // Accurate starting counter: 800 KB accounts for the two media below.
    $user->storage_used = 800;
    $user->storage_used_updated_at = now();
    $user->save();

    // A single orphaned media row of 500,000 bytes (~500 KB) that was charged
    // (quota_status reflects the optimized size on the counter).
    $media = Media::create([
        'status_id' => null,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/orphan.jpeg',
        'mime' => 'image/jpeg',
        'size' => 500000,
        'quota_status' => MediaQuotaStatus::OptimizedSize,
        'order' => 1,
    ]);

    (new MediaDeletePipeline($media))->handle();

    // Media row is gone.
    expect(Media::whereId($media->id)->exists())->toBeFalse();

    // 800 KB - 500 KB = 300 KB.
    $user->refresh();
    expect((int) $user->storage_used)->toBe(300);
});

/*
| The decrement is clamped at zero: even if the cached counter is somehow lower
| than the deleted media size (drift), storage_used never goes negative.
*/

it('clamps storage_used at zero when the deleted media is larger than the counter', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 100;
    $user->storage_used_updated_at = now();
    $user->save();

    $media = Media::create([
        'status_id' => null,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/big.jpeg',
        'mime' => 'image/jpeg',
        'size' => 500000,
        'quota_status' => MediaQuotaStatus::OptimizedSize,
        'order' => 1,
    ]);

    (new MediaDeletePipeline($media))->handle();

    $user->refresh();
    expect((int) $user->storage_used)->toBe(0);
});

/*
| Regression: when media is still attached to a status the job returns early
| and MUST NOT touch storage_used (#7169). A skipped delete freeing quota
| would let users exceed their real usage accounting.
*/

it('does not change storage_used when deletion is skipped for attached media', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 4242;
    $user->storage_used_updated_at = now();
    $user->save();

    $status = Status::factory()->create(['profile_id' => $user->profile->id, 'type' => 'photo']);

    $media = Media::create([
        'status_id' => $status->id,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/attached.jpeg',
        'mime' => 'image/jpeg',
        'size' => 999000,
        'order' => 1,
    ]);

    (new MediaDeletePipeline($media))->handle();

    // Untouched: media still attached, storage_used unchanged.
    expect(Media::whereId($media->id)->exists())->toBeTrue();
    $user->refresh();
    expect((int) $user->storage_used)->toBe(4242);
});

/*
|--------------------------------------------------------------------------
| MediaDeletePipeline refund by quota lifecycle
|--------------------------------------------------------------------------
|
| Delete refunds whatever the media currently reflects in storage_used, per its
| quota_status: the optimized size when OptimizedSize, the raw size when
| OriginalSize, and nothing when it was never charged (Pending). This keeps the
| charge and refund symmetric across the lifecycle.
|
*/

it('charge then delete cancels to baseline via the quota lifecycle', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 0;
    $user->storage_used_updated_at = now();
    $user->save();

    // Uploaded (raw 900 KB) then optimized to 250 KB.
    $media = Media::create([
        'status_id' => null,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/charged.jpeg',
        'mime' => 'image/jpeg',
        'size' => 900000,
        'original_size' => 900000,
        'quota_status' => MediaQuotaStatus::Pending,
        'order' => 1,
    ]);

    UserStorageService::chargeOriginal($media);
    $media->size = 250000;
    $media->save();
    UserStorageService::chargeOptimized($media->fresh());
    $user->refresh();
    expect((int) $user->storage_used)->toBe(250);

    (new MediaDeletePipeline($media->fresh()))->handle();

    // Back to zero: refund of the optimized size cancels the net charge.
    $user->refresh();
    expect((int) $user->storage_used)->toBe(0);
});

it('does not refund storage_used for a never-charged (Pending) media', function () {
    $user = User::factory()->create();
    $user->refresh();

    $user->storage_used = 800;
    $user->storage_used_updated_at = now();
    $user->save();

    // Uploaded but deleted before it was charged (e.g. the DM/remote race).
    $media = Media::create([
        'status_id' => null,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/pending.jpeg',
        'mime' => 'image/jpeg',
        'size' => 500000,
        'original_size' => 500000,
        'quota_status' => MediaQuotaStatus::Pending,
        'order' => 1,
    ]);

    (new MediaDeletePipeline($media))->handle();

    expect(Media::whereId($media->id)->exists())->toBeFalse();

    // Untouched: a Pending row never contributed to the counter.
    $user->refresh();
    expect((int) $user->storage_used)->toBe(800);
});
