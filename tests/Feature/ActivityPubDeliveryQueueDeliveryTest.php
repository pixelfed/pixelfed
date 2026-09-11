<?php

use App\Models\User;
use App\Services\ActivityPubDeliveryService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ActivityPubDeliveryService::send() signature guard
|--------------------------------------------------------------------------
|
| The legacy single-inbox delivery path must not send an unsigned request
| when HttpSignature::sign() fails (returns an empty header array). It should
| log the failure and bail out instead of silently proceeding.
|
*/

it('bails out and logs when signature headers cannot be generated', function () {
    config(['app.env' => 'production']);

    $user = User::factory()->create();
    $user->refresh();
    $profile = $user->profile;

    // Sender must be a local, active profile to pass the delivery guards.
    $profile->domain = null;
    $profile->status = null;
    // Force HttpSignature::sign() to return [] by removing the private key.
    $profile->private_key = '';
    $profile->save();

    Log::spy();

    $result = ActivityPubDeliveryService::queue()
        ->from($profile)
        ->to('https://example.com/inbox')
        ->payload(['type' => 'Create'])
        ->send();

    expect($result)->toBeFalse();

    Log::shouldHaveReceived('error')
        ->withArgs(fn ($message) => str_contains($message, 'could not generate signature'))
        ->once();
});
