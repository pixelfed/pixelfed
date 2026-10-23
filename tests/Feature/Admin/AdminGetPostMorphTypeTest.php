<?php

use App\Models\AccountInterstitial;
use App\Models\Report;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Admin getPost morph-type counts include legacy App\Status rows
|--------------------------------------------------------------------------
|
| getPost's report_count / open_report_count / autospam filtered only the
| new 'App\Models\Status' morph value, silently dropping pre-namespace
| migration 'App\Status' rows. The dual-form whereIn must include both.
|
*/

// Mirrors the getPost query expressions.
function reportCount(int $statusId): int
{
    return Report::whereIn('object_type', ['App\Status', Status::class])
        ->whereObjectId($statusId)
        ->count();
}

function openReportCount(int $statusId): int
{
    return Report::whereIn('object_type', ['App\Status', Status::class])
        ->whereObjectId($statusId)
        ->whereNull('admin_seen')
        ->count();
}

function hasAutospam(int $statusId): bool
{
    return AccountInterstitial::whereIn('item_type', ['App\Status', Status::class])
        ->whereItemId($statusId)
        ->whereType('post.autospam')
        ->whereNull('appeal_handled_at')
        ->exists();
}

it('counts legacy App\\Status reports and interstitials in getPost lookups', function () {
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create(['profile_id' => $user->profile_id, 'type' => 'photo']);

    // Legacy 'App\Status' rows (pre-namespace migration).
    Report::create([
        'profile_id' => $user->profile_id,
        'user_id' => 1,
        'object_id' => $status->id,
        'object_type' => 'App\Status',
        'type' => 'spam',
    ]);

    DB::table('account_interstitials')->insert([
        'user_id' => $user->id,
        'type' => 'post.autospam',
        'item_type' => 'App\Status',
        'item_id' => $status->id,
        'appeal_handled_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(reportCount($status->id))->toBe(1);
    expect(openReportCount($status->id))->toBe(1);
    expect(hasAutospam($status->id))->toBeTrue();
});

it('still counts modern App\\Models\\Status rows', function () {
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create(['profile_id' => $user->profile_id, 'type' => 'photo']);

    Report::create([
        'profile_id' => $user->profile_id,
        'user_id' => 2,
        'object_id' => $status->id,
        'object_type' => Status::class,
        'type' => 'spam',
    ]);

    expect(reportCount($status->id))->toBe(1);
});
