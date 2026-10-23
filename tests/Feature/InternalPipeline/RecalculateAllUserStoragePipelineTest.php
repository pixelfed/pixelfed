<?php

use App\Jobs\InternalPipeline\RecalculateAllUserStoragePipeline;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function makeUserMedia(User $user, int $bytes): Media
{
    return Media::create([
        'status_id' => null,
        'profile_id' => $user->profile->id,
        'user_id' => $user->id,
        'media_path' => 'public/m/_v2/1/'.uniqid().'.jpeg',
        'mime' => 'image/jpeg',
        'size' => $bytes,
        'order' => 1,
    ]);
}

/*
| The backfill job repairs storage_used counters that drifted before the
| self-heal logic existed (#7169). It recomputes every active user from their
| actual media, so stale inflated values are corrected in bulk.
*/

it('recalculates storage_used from actual media for all active users', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $a->refresh();
    $b->refresh();

    // Both start with wildly inflated stale counters.
    foreach ([$a, $b] as $u) {
        $u->storage_used = 999999;
        $u->storage_used_updated_at = now()->subYear();
        $u->save();
    }

    makeUserMedia($a, 400000); // 400 KB real usage
    // $b has no media.

    (new RecalculateAllUserStoragePipeline)->handle();

    $a->refresh();
    $b->refresh();
    expect((int) $a->storage_used)->toBe(400);
    expect((int) $b->storage_used)->toBe(0);
});
