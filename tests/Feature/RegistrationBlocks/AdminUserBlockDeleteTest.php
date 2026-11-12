<?php

use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Models\RegistrationBlock;
use App\Models\User;
use App\Services\RegistrationBlockService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::forget(RegistrationBlockService::CACHE_KEY);
    Queue::fake();
    config(['pixelfed.account_deletion' => true]);
});

function ubdAdmin(): User
{
    $user = User::factory()->create(['is_admin' => true]);
    $user->refresh();

    return $user;
}

function ubdSpammer(array $attrs = []): User
{
    $user = User::factory()->create(array_merge([
        'is_admin' => false,
        'username' => 'casinodeals',
        'app_register_ip' => '203.0.113.77',
    ], $attrs));
    $user->refresh();

    return $user;
}

function ubdBlockDelete($test, User $actor, User $target)
{
    return $test->actingAs($actor)->postJson('/i/admin/users/block-delete/'.$target->id);
}

it('blocks the signup ip and username and deletes the account', function () {
    $spammer = ubdSpammer();

    ubdBlockDelete($this, ubdAdmin(), $spammer)
        ->assertOk()
        ->assertJson(['ip' => '203.0.113.77', 'username' => 'casinodeals', 'blocked' => ['ip', 'username']]);

    expect(RegistrationBlockService::ipBlocked('203.0.113.77', false))->toBeTrue();
    expect(RegistrationBlockService::usernameBlocked('casinodeals', false))->toBeTrue();
    expect($spammer->fresh()->status)->toBe('delete');
    expect($spammer->profile->fresh()->status)->toBe('delete');
    Queue::assertPushed(DeleteAccountPipeline::class);
});

it('does not duplicate rules that already cover the account', function () {
    RegistrationBlockService::store('ip', 'exact', '203.0.113.0/24');
    $spammer = ubdSpammer();

    ubdBlockDelete($this, ubdAdmin(), $spammer)
        ->assertOk()
        ->assertJson(['blocked' => ['username']]);

    expect(RegistrationBlock::where('type', 'ip')->count())->toBe(1);
    expect($spammer->fresh()->status)->toBe('delete');
});

it('refuses and changes nothing when no signup ip was recorded', function () {
    $spammer = ubdSpammer(['app_register_ip' => null]);

    ubdBlockDelete($this, ubdAdmin(), $spammer)
        ->assertStatus(422)
        ->assertJsonPath('error', fn ($e) => str_contains($e, 'No signup IP'));

    expect(RegistrationBlock::count())->toBe(0);
    expect($spammer->fresh()->status)->toBeNull();
    Queue::assertNotPushed(DeleteAccountPipeline::class);
});

it('refuses when account deletion is disabled', function () {
    config(['pixelfed.account_deletion' => false]);
    $spammer = ubdSpammer();

    ubdBlockDelete($this, ubdAdmin(), $spammer)->assertStatus(422);

    expect(RegistrationBlock::count())->toBe(0);
    expect($spammer->fresh()->status)->toBeNull();
});

it('refuses admin accounts', function () {
    $other = ubdSpammer(['is_admin' => true]);

    ubdBlockDelete($this, ubdAdmin(), $other)->assertStatus(422);

    expect(RegistrationBlock::count())->toBe(0);
    expect($other->fresh()->status)->toBeNull();
});

it('refuses accounts already being deleted', function () {
    $spammer = ubdSpammer(['status' => 'delete']);

    ubdBlockDelete($this, ubdAdmin(), $spammer)->assertStatus(422);

    expect(RegistrationBlock::count())->toBe(0);
});

it('does not let non admins use it', function () {
    $spammer = ubdSpammer();
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();

    ubdBlockDelete($this, $user, $spammer);

    expect(RegistrationBlock::count())->toBe(0);
    expect($spammer->fresh()->status)->toBeNull();
});
