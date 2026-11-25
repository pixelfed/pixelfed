<?php

use App\Models\AccountLog;
use App\Models\RegistrationBlock;
use App\Models\User;
use App\Services\IpDataImportService;
use App\Services\RegistrationBlockService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

function lbUser(array $attrs = []): User
{
    $user = User::factory()->create(array_merge([
        'password' => Hash::make('correct-password'),
    ], $attrs));
    $user->refresh();

    return $user;
}

function lbLogin($test, User $user, string $ip)
{
    return $test->withServerVariables(['REMOTE_ADDR' => $ip])->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);
}

it('keeps blocks signup only unless they are set to block sign ins', function () {
    RegistrationBlockService::store('ip', 'exact', '203.0.113.0/24');
    $user = lbUser();

    expect(RegistrationBlockService::ipBlocked('203.0.113.9', false))->toBeTrue();
    expect(RegistrationBlockService::loginBlocked('203.0.113.9', $user, false))->toBeFalse();

    lbLogin($this, $user, '203.0.113.9')->assertRedirect('/i/web');
    $this->assertAuthenticatedAs($user);
});

it('stops sign ins from a blocked ip and logs the attempt', function () {
    $block = RegistrationBlockService::store('ip', 'exact', '203.0.113.0/24', null, null, null, true);
    $user = lbUser();

    lbLogin($this, $user, '203.0.113.9')
        ->assertSessionHasErrors(['email' => RegistrationBlockService::LOGIN_MESSAGE]);

    $this->assertGuest();
    expect($block->fresh()->hits)->toBe(1);
    expect(AccountLog::where('user_id', $user->id)->where('action', 'auth.login.network_blocked')->exists())->toBeTrue();

    lbLogin($this, $user, '198.51.100.9')->assertRedirect('/i/web');
    $this->assertAuthenticatedAs($user);
});

it('never blocks admins from signing in', function () {
    RegistrationBlockService::store('ip', 'exact', '203.0.113.0/24', null, null, null, true);
    $admin = lbUser(['is_admin' => true]);

    lbLogin($this, $admin, '203.0.113.9')->assertRedirect('/i/web');
    $this->assertAuthenticatedAs($admin);
});

it('does not reveal the block for a wrong password', function () {
    RegistrationBlockService::store('ip', 'exact', '203.0.113.0/24', null, null, null, true);
    $user = lbUser();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => trans('auth.failed')]);

    $this->assertGuest();
});

it('only lets ip, asn and country blocks stop sign ins', function () {
    $username = RegistrationBlockService::store('username', 'exact', 'casinodeals', null, null, null, true);
    $asn = RegistrationBlockService::store('asn', 'exact', 'AS14061', null, null, null, true);

    expect($username->block_logins)->toBeFalse();
    expect($asn->block_logins)->toBeTrue();

    $again = RegistrationBlockService::store('asn', 'exact', '14061', 'updated note');
    expect($again->id)->toBe($asn->id);
    expect($again->block_logins)->toBeTrue();

    $off = RegistrationBlockService::store('asn', 'exact', '14061', null, null, null, false);
    expect($off->block_logins)->toBeFalse();
});

it('stops sign ins by asn using the ip data', function () {
    Queue::fake();
    $path = tempnam(sys_get_temp_dir(), 'lb').'.csv';
    file_put_contents($path, "network,asn,country_code,name,org,domain\n203.0.112.0/20,14061,US,DIGITALOCEAN-ASN,\"DigitalOcean, LLC\",digitalocean.com\n");
    $dataset = IpDataImportService::stageLocal($path, dispatch: false);
    while (! IpDataImportService::importSlice($dataset)) {
    }

    RegistrationBlockService::store('asn', 'exact', 'AS14061', null, null, null, true);
    $user = lbUser();

    lbLogin($this, $user, '203.0.113.200')->assertSessionHasErrors('email');
    $this->assertGuest();

    lbLogin($this, $user, '198.51.100.1')->assertRedirect('/i/web');
    $this->assertAuthenticatedAs($user);
});

it('lets admins turn sign in blocking on and off per block', function () {
    $admin = lbUser(['is_admin' => true]);
    $ip = RegistrationBlockService::store('ip', 'exact', '203.0.113.7');
    $email = RegistrationBlockService::store('email_domain', 'exact', 'spam.example');

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/i/admin/registration-blocks/'.$ip->id.'/logins')
        ->assertRedirect();
    expect($ip->fresh()->block_logins)->toBeTrue();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/i/admin/registration-blocks/'.$email->id.'/logins')
        ->assertSessionHasErrors('patterns');
    expect($email->fresh()->block_logins)->toBeFalse();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post('/i/admin/registration-blocks', [
            'type' => 'asn',
            'match_type' => 'exact',
            'patterns' => "AS16276\nAS7552",
            'expires' => 'never',
            'block_logins' => '1',
        ])
        ->assertRedirect();

    expect(RegistrationBlock::where('type', 'asn')->where('block_logins', true)->count())->toBe(2);
});

it('reports sign in blocks from the command line test', function () {
    RegistrationBlockService::store('ip', 'exact', '203.0.113.0/24', null, null, null, true);

    $this->artisan('admin:registration-blocks', ['action' => 'test', '--ip' => '203.0.113.5'])
        ->expectsOutputToContain('Sign ins blocked by')
        ->assertSuccessful();

    $this->artisan('admin:registration-blocks', ['action' => 'add', 'args' => ['country', 'vn'], '--logins' => true])
        ->expectsOutputToContain('sign ins blocked too')
        ->assertSuccessful();
});
