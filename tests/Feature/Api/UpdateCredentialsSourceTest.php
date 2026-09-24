<?php

use App\Models\User;
use App\Models\UserSetting;
use App\Services\MarkerService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| update_credentials / markers nested-input handling
|--------------------------------------------------------------------------
|
| These endpoints read nested Mastodon fields (source[privacy], source[language],
| home[last_read_id], notifications[last_read_id]). Laravel resolves nested
| input via dot notation, so bracket-notation keys matched nothing and the
| updates were silently dropped while still returning 200.
|
*/

beforeEach(function () {
    Redis::spy();
});

it('updates default posting privacy from source.privacy', function () {
    $user = User::factory()->create(['language' => 'en']);
    $user->refresh();

    Passport::actingAs($user, ['write', 'read']);

    $this->patchJson('/api/v1/accounts/update_credentials', [
        'source' => ['privacy' => 'unlisted'],
    ])->assertOk();

    $settings = UserSetting::whereUserId($user->id)->first();

    expect($settings)->not->toBeNull()
        ->and($settings->compose_settings['default_scope'])->toBe('unlisted');
});

it('updates default posting language from source.language', function () {
    $user = User::factory()->create(['language' => 'en']);
    $user->refresh();

    Passport::actingAs($user, ['write', 'read']);

    $res = $this->patchJson('/api/v1/accounts/update_credentials', [
        'source' => ['language' => 'fr-FR'],
    ])->assertOk()->json();

    expect($user->fresh()->language)->toBe('fr-FR')
        ->and($res['language'] ?? null)->toBe('fr-FR');
});

it('updates both source fields from a nested payload', function () {
    $user = User::factory()->create(['language' => 'en']);
    $user->refresh();

    Passport::actingAs($user, ['write', 'read']);

    $this->patchJson('/api/v1/accounts/update_credentials', [
        'source' => ['privacy' => 'unlisted', 'language' => 'de-DE'],
    ])->assertOk();

    $settings = UserSetting::whereUserId($user->id)->first();

    expect($user->fresh()->language)->toBe('de-DE')
        ->and($settings->compose_settings['default_scope'])->toBe('unlisted');
});

it('sets the home marker from home.last_read_id', function () {
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write', 'read']);

    $res = $this->postJson('/api/v1/markers', [
        'home' => ['last_read_id' => '12345'],
    ])->assertOk()->json();

    expect($res['last_read_id'])->toBe('12345')
        ->and(MarkerService::get($user->profile_id, 'home')['last_read_id'])->toBe('12345');
});

it('sets the notifications marker from notifications.last_read_id', function () {
    $user = User::factory()->create();
    $user->refresh();

    Passport::actingAs($user, ['write', 'read']);

    $res = $this->postJson('/api/v1/markers', [
        'notifications' => ['last_read_id' => '67890'],
    ])->assertOk()->json();

    expect($res['last_read_id'])->toBe('67890')
        ->and(MarkerService::get($user->profile_id, 'notifications')['last_read_id'])->toBe('67890');
});
