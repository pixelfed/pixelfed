<?php

use App\Models\RegistrationBlock;
use App\Models\User;
use App\Rules\NotRegistrationBlocked;
use App\Services\RegistrationBlockService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['pixelfed.open_registration' => true]);
    config(['pixelfed.max_users' => 1000]);
    config(['instance.enable_cc' => false]);
    config(['honeypot.enabled' => false]);
    Cache::forget(RegistrationBlockService::CACHE_KEY);
});

function rbSignup(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'testregistration@gmail.com',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'agree' => 'on',
        'agecheck' => 'on',
    ], $overrides);
}

it('stores normalized rules and reactivates duplicates instead of copying them', function () {
    $a = RegistrationBlockService::store('email_domain', 'exact', '@Spam.Example');
    $a->update(['is_active' => false]);

    $b = RegistrationBlockService::store('email_domain', 'exact', 'spam.example', 'second note');

    expect($b->id)->toBe($a->id);
    expect($b->pattern)->toBe('spam.example');
    expect($b->is_active)->toBeTrue();
    expect(RegistrationBlock::count())->toBe(1);
});

it('flushes the rule cache when a rule changes', function () {
    expect(RegistrationBlockService::rules())->toBe([]);

    $block = RegistrationBlockService::store('username', 'wildcard', '*casino*');
    expect(RegistrationBlockService::rules())->toHaveCount(1);

    $block->delete();
    expect(RegistrationBlockService::rules())->toBe([]);
});

it('fails validation and records a hit for blocked values', function () {
    $block = RegistrationBlockService::store('username', 'wildcard', '*casino*');

    $v = Validator::make(['username' => 'luckycasino'], ['username' => [new NotRegistrationBlocked('username')]]);

    expect($v->fails())->toBeTrue();
    expect($v->errors()->first('username'))->toBe(RegistrationBlockService::USERNAME_MESSAGE);
    expect($block->fresh()->hits)->toBe(1);
    expect($block->fresh()->last_hit_at)->not->toBeNull();
});

it('rejects web signups with a blocked email domain', function () {
    RegistrationBlockService::store('email_domain', 'exact', 'gmail.com');

    $this->post('/register', rbSignup())
        ->assertRedirect()
        ->assertSessionHasErrors(['email']);

    $this->assertDatabaseMissing('users', ['username' => 'testuser']);
});

it('rejects web signups with a blocked username', function () {
    RegistrationBlockService::store('username', 'exact', 'testuser');

    $this->post('/register', rbSignup())
        ->assertRedirect()
        ->assertSessionHasErrors(['username']);
});

it('rejects the signup form and submission from a blocked ip range', function () {
    RegistrationBlockService::store('ip', 'exact', '127.0.0.0/8');

    $this->get('/register')->assertForbidden();
    $this->post('/register', rbSignup())->assertForbidden();

    $this->assertDatabaseMissing('users', ['username' => 'testuser']);
});

it('does not count form views as hits', function () {
    $block = RegistrationBlockService::store('ip', 'exact', '127.0.0.1');

    $this->get('/register')->assertForbidden();

    expect($block->fresh()->hits)->toBe(0);
});

it('ignores disabled and expired rules', function () {
    RegistrationBlockService::store('ip', 'exact', '127.0.0.1')->update(['is_active' => false]);
    RegistrationBlockService::store('username', 'exact', 'testuser', null, now()->subMinute());

    $this->get('/register')->assertOk();

    expect(RegistrationBlockService::usernameBlocked('testuser', false))->toBeFalse();
});

it('still lets existing accounts on a blocked network reach login', function () {
    User::factory()->create(['username' => 'existing']);
    RegistrationBlockService::store('ip', 'exact', '127.0.0.1');

    $this->get('/login')->assertOk();
});
