<?php

use App\Models\User;
use App\Models\UserSetting;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| AccountService::getAccountSettings null user_settings handling
|--------------------------------------------------------------------------
|
| A User can have no user_settings row (hasOne is nullable). getAccountSettings
| dereferenced $user->settings->reduce_motion without a null-check, throwing on
| every call and 500ing /settings/privacy, embed routes and verify_credentials
| with no recovery (the exception isn't cached). It must fall back to defaults
| like the sibling settings() method.
|
*/

beforeEach(function () {
    Redis::spy();
});

it('returns defaults instead of throwing when the settings row is missing', function () {
    $user = User::factory()->create();
    $user->refresh();

    UserSetting::where('user_id', $user->id)->delete();
    AccountService::forgetAccountSettings($user->profile_id);

    $res = AccountService::getAccountSettings($user->profile_id);

    $defaults = AccountService::defaultSettings();

    expect($res)->toBeArray()
        ->and($res['reduce_motion'])->toBe((bool) $defaults['reduce_motion'])
        ->and($res['crawlable'])->toBe((bool) $defaults['crawlable'])
        ->and($res['public_dm'])->toBe((bool) $defaults['public_dm'])
        ->and($res['default_scope'])->toBe((string) $defaults['compose_settings']['default_scope'])
        ->and($res['disable_embeds'])->toBe((bool) $defaults['other']['disable_embeds'])
        ->and($res['show_atom'])->toBeFalse();
});

it('allows embeds by default when the settings row is missing', function () {
    $user = User::factory()->create();
    $user->refresh();

    UserSetting::where('user_id', $user->id)->delete();
    AccountService::forgetAccountSettings($user->profile_id);

    // canEmbed() consumes getAccountSettings(); it must not 500.
    expect(AccountService::canEmbed($user->profile_id))->toBeTrue();
});

it('reflects a stored settings row when present', function () {
    $user = User::factory()->create();
    $user->refresh();

    $settings = UserSetting::firstOrNew(['user_id' => $user->id]);
    $settings->reduce_motion = true;
    $settings->public_dm = true;
    $settings->save();

    AccountService::forgetAccountSettings($user->profile_id);

    $res = AccountService::getAccountSettings($user->profile_id);

    expect($res['reduce_motion'])->toBeTrue()
        ->and($res['public_dm'])->toBeTrue();
});
