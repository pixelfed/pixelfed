<?php

use App\Jobs\MediaPipeline\MediaSyncLicensePipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Passport\Passport;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| sync_licenses opt-out handling
|--------------------------------------------------------------------------
|
| sync_licenses is an opt-in flag that triggers MediaSyncLicensePipeline when
| a user changes their default license. A client that explicitly opts out with
| a falsey form value must not trigger the sync.
|
*/

it('does not sync when sync_licenses is an explicit false string', function () {
    Bus::fake();

    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write', 'read']);

    $this->patchJson('/api/v1/accounts/update_credentials', [
        'license' => '5',
        'sync_licenses' => 'false',
    ])->assertOk();

    Bus::assertNotDispatched(MediaSyncLicensePipeline::class);
});

it('syncs when sync_licenses is true and the license changed', function () {
    Bus::fake();

    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write', 'read']);

    $this->patchJson('/api/v1/accounts/update_credentials', [
        'license' => '5',
        'sync_licenses' => 'true',
    ])->assertOk();

    Bus::assertDispatched(MediaSyncLicensePipeline::class);
});

it('does not sync when sync_licenses is omitted', function () {
    Bus::fake();

    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write', 'read']);

    $this->patchJson('/api/v1/accounts/update_credentials', [
        'license' => '5',
    ])->assertOk();

    Bus::assertNotDispatched(MediaSyncLicensePipeline::class);
});
