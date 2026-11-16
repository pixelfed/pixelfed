<?php

use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'instance.enable_cc' => false,
        'pixelfed.open_registration' => true,
        'pixelfed.allow_app_registration' => true,
        'pixelfed.bouncer.cloud_ips.ban_signups' => false,
    ]);

    app(ClientRepository::class)->createPersonalAccessGrantClient(
        'Test Personal Access Client',
        config('auth.guards.api.provider')
    );
});

function iarcAppUser(array $attrs = []): array
{
    $user = User::factory()->unverified()->create();
    $user->register_source = $attrs['register_source'] ?? 'app';
    $user->app_register_token = array_key_exists('app_register_token', $attrs) ? $attrs['app_register_token'] : Str::random(40);
    if (! empty($attrs['verified'])) {
        $user->email_verified_at = now();
    }
    $user->save();

    $verify = new EmailVerification;
    $verify->user_id = $user->id;
    $verify->email = $user->email;
    $verify->user_token = $attrs['user_token'] ?? $user->app_register_token;
    $verify->random_token = Str::random(64);
    $verify->save();

    return [$user, $verify];
}

function iarcPost($test, array $body)
{
    return $test->withHeaders(['X-PIXELFED-APP' => '1'])->postJson('/api/v1.1/auth/iarc', $body);
}

it('issues a token once for a valid app registration', function () {
    [$user, $verify] = iarcAppUser();

    $body = [
        'email' => $verify->email,
        'user_token' => $verify->user_token,
        'random_token' => $verify->random_token,
    ];

    iarcPost($this, $body)->assertOk()->assertJsonStructure(['access_token']);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
    expect(EmailVerification::whereUserId($user->id)->count())->toBe(0);

    iarcPost($this, $body)->assertForbidden()->assertJsonPath('error', 'Invalid tokens');
});

it('rejects tokens and email that are not strings', function (mixed $value) {
    iarcAppUser();

    iarcPost($this, [
        'email' => $value,
        'user_token' => $value,
        'random_token' => $value,
    ])->assertStatus(422)->assertJsonValidationErrors(['email', 'user_token', 'random_token']);
})->with([
    'integer zero' => 0,
    'integer' => 123,
    'boolean' => true,
    'array' => [['a']],
]);

it('rejects email verification rows that did not come from app registration', function () {
    [$user, $verify] = iarcAppUser([
        'register_source' => null,
        'app_register_token' => null,
        'user_token' => (string) Str::uuid().Str::random(6),
    ]);

    iarcPost($this, [
        'email' => $verify->email,
        'user_token' => $verify->user_token,
        'random_token' => $verify->random_token,
    ])->assertForbidden();

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('rejects a pending verification that does not match the app registration token', function () {
    [$user, $verify] = iarcAppUser(['user_token' => (string) Str::uuid().Str::random(6)]);

    iarcPost($this, [
        'email' => $verify->email,
        'user_token' => $verify->user_token,
        'random_token' => $verify->random_token,
    ])->assertForbidden();

    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('rejects accounts that are already verified', function () {
    [$user, $verify] = iarcAppUser(['verified' => true]);

    iarcPost($this, [
        'email' => $verify->email,
        'user_token' => $verify->user_token,
        'random_token' => $verify->random_token,
    ])->assertForbidden();
});

it('rejects suspended accounts', function () {
    [$user, $verify] = iarcAppUser();
    $user->status = 'suspended';
    $user->save();

    iarcPost($this, [
        'email' => $verify->email,
        'user_token' => $verify->user_token,
        'random_token' => $verify->random_token,
    ])->assertForbidden();
});

it('rate limits confirm attempts for an email', function () {
    [$user, $verify] = iarcAppUser();

    foreach (range(1, 10) as $i) {
        iarcPost($this, [
            'email' => $verify->email,
            'user_token' => Str::random(40),
            'random_token' => Str::random(64),
        ])->assertForbidden();
    }

    iarcPost($this, [
        'email' => $verify->email,
        'user_token' => $verify->user_token,
        'random_token' => $verify->random_token,
    ])->assertStatus(429);

    expect($user->fresh()->email_verified_at)->toBeNull();
});
