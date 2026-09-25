<?php

use App\Jobs\StatusPipeline\StatusDelete;
use App\Models\AccountInterstitial;
use App\Models\CollectionItem;
use App\Models\Report;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| StatusDelete legacy 'App\Status' morph alias cleanup - regression
|--------------------------------------------------------------------------
|
| reports.object_type, account_interstitials.item_type and
| collection_items.object_type may store the pre-namespace-migration alias
| 'App\Status' as well as the current Status::class. The single-alias
| cleanup filters skipped legacy rows, leaving orphans. Cleanup must match
| both aliases (as Notification already does).
|
*/

it('deletes both legacy App\\Status and modern reports on status delete', function () {
    config(['federation.activitypub.enabled' => false]);

    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    $legacy = Report::create([
        'profile_id' => $user->profile_id,
        'user_id' => 1,
        'object_id' => $status->id,
        'object_type' => 'App\\Status',
        'type' => 'post',
    ]);

    $modern = Report::create([
        'profile_id' => $user->profile_id,
        'user_id' => 2,
        'object_id' => $status->id,
        'object_type' => Status::class,
        'type' => 'post',
    ]);

    (new StatusDelete($status))->handle();

    expect(Report::find($modern->id))->toBeNull('modern report should be removed');
    expect(Report::find($legacy->id))->toBeNull('legacy App\\Status report should also be removed');
});

it('deletes both legacy and modern account interstitials on status delete', function () {
    config(['federation.activitypub.enabled' => false]);

    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    DB::table('account_interstitials')->insert([
        ['user_id' => $user->id, 'type' => 'post.cw', 'item_type' => 'App\\Status', 'item_id' => $status->id, 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => $user->id, 'type' => 'post.cw', 'item_type' => Status::class, 'item_id' => $status->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    (new StatusDelete($status))->handle();

    $remaining = AccountInterstitial::where('item_id', $status->id)
        ->whereIn('item_type', ['App\\Status', Status::class])
        ->count();

    expect($remaining)->toBe(0);
});

it('deletes both legacy and modern collection items on status delete', function () {
    config(['federation.activitypub.enabled' => false]);

    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'photo',
    ]);

    $legacy = CollectionItem::create([
        'collection_id' => 1,
        'object_type' => 'App\\Status',
        'object_id' => $status->id,
        'order' => 1,
    ]);

    $modern = CollectionItem::create([
        'collection_id' => 1,
        'object_type' => Status::class,
        'object_id' => $status->id,
        'order' => 2,
    ]);

    (new StatusDelete($status))->handle();

    expect(CollectionItem::find($legacy->id))->toBeNull('legacy App\\Status collection item should be removed');
    expect(CollectionItem::find($modern->id))->toBeNull('modern collection item should be removed');
});
