<?php

use App\Models\AccountInterstitial;
use App\Models\Status;
use App\Models\User;
use App\Util\Sentiment\Bouncer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Bouncer autospam exemption must honour the legacy morph alias
|--------------------------------------------------------------------------
|
| Approved-appeal rows written before the namespace refactor carry
| item_type = 'App\Status'. The exemption lookup compares item_type with a
| plain where(), which never consults the morph map, so those legacy rows
| used to miss and the user's later public posts got re-flagged for 12 days.
|
*/

function bouncerExemptionFor(User $user): bool
{
    $profile = $user->profile;

    $key = 'pf:bouncer_v0:exemption_by_pid:'.$profile->id;
    Cache::forget($key);

    $status = Status::factory()->create([
        'profile_id' => $profile->id,
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    Bouncer::get($status);

    return (bool) Cache::get($key);
}

function seedBouncerApprovedAppeal(User $user, string $itemType): void
{
    $approved = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    $ai = new AccountInterstitial;
    $ai->user_id = $user->id;
    $ai->type = 'post.autospam';
    $ai->view = 'account.moderation.post.autospam';
    $ai->item_type = $itemType;
    $ai->item_id = $approved->id;
    $ai->appeal_handled_at = now();
    $ai->save();
}

it('exempts a user whose approved appeal uses the legacy App\Status morph alias', function () {
    $user = User::factory()->create();
    $user->refresh();

    seedBouncerApprovedAppeal($user, 'App\Status');

    expect(bouncerExemptionFor($user))->toBeTrue();
});

it('exempts a user whose approved appeal uses the modern Status class morph alias', function () {
    $user = User::factory()->create();
    $user->refresh();

    seedBouncerApprovedAppeal($user, Status::class);

    expect(bouncerExemptionFor($user))->toBeTrue();
});

it('does not exempt a user with no approved appeal', function () {
    $user = User::factory()->create();
    $user->refresh();

    expect(bouncerExemptionFor($user))->toBeFalse();
});
