<?php

use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Models\AccountInterstitial;
use App\Models\Profile;
use App\Models\RegistrationBlock;
use App\Models\Report;
use App\Models\Status;
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

function rbdAdmin(): User
{
    $user = User::factory()->create(['is_admin' => true]);
    $user->refresh();

    return $user;
}

function rbdSpammer(array $attrs = []): User
{
    $user = User::factory()->create(array_merge([
        'is_admin' => false,
        'username' => 'casinodeals',
        'app_register_ip' => '203.0.113.77',
    ], $attrs));
    $user->refresh();

    return $user;
}

function rbdStatusReport(User $spammer): Report
{
    $status = Status::factory()->create(['profile_id' => $spammer->profile_id]);
    $reporter = User::factory()->create(['is_admin' => false]);
    $reporter->refresh();

    return Report::create([
        'profile_id' => $reporter->profile_id,
        'user_id' => $reporter->id,
        'object_id' => $status->id,
        'object_type' => Status::class,
        'reported_profile_id' => $spammer->profile_id,
        'type' => 'spam',
    ]);
}

function rbdHandle($test, User $admin, Report $report, string $action)
{
    return $test->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->postJson('/i/admin/api/reports/handle', [
            'id' => $report->id,
            'object_id' => $report->object_id,
            'object_type' => $report->object_type,
            'action' => $action,
            'action_type' => 'profile',
        ]);
}

function rbdSpamReport(User $spammer): AccountInterstitial
{
    $status = Status::factory()->create(['profile_id' => $spammer->profile_id]);

    return AccountInterstitial::forceCreate([
        'user_id' => $spammer->id,
        'type' => 'post.autospam',
        'view' => 'account.moderation.post.autospam',
        'item_id' => $status->id,
        'item_type' => Status::class,
        'is_spam' => true,
        'meta' => json_encode(['is_nsfw' => false, 'scope' => 'public', 'visibility' => 'public']),
    ]);
}

function rbdSpamHandle($test, User $admin, AccountInterstitial $report, string $action)
{
    return $test->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->postJson('/i/admin/api/reports/spam/handle', [
            'id' => $report->id,
            'action' => $action,
        ]);
}

it('blocks the signup ip and deletes the account from a report', function () {
    $spammer = rbdSpammer();
    $report = rbdStatusReport($spammer);

    rbdHandle($this, rbdAdmin(), $report, 'block-ip-delete')
        ->assertOk()
        ->assertJson(['ip' => '203.0.113.77', 'username' => 'casinodeals', 'blocked' => ['ip']]);

    expect(RegistrationBlockService::ipBlocked('203.0.113.77', false))->toBeTrue();
    expect(RegistrationBlockService::usernameBlocked('casinodeals', false))->toBeFalse();
    expect($spammer->fresh()->status)->toBe('delete');
    expect($report->fresh()->admin_seen)->not->toBeNull();
    Queue::assertPushed(DeleteAccountPipeline::class);
});

it('blocks the username and signup ip and deletes the account from a report', function () {
    $spammer = rbdSpammer();
    $report = rbdStatusReport($spammer);

    rbdHandle($this, rbdAdmin(), $report, 'block-username-ip-delete')
        ->assertOk()
        ->assertJson(['blocked' => ['ip', 'username']]);

    expect(RegistrationBlockService::ipBlocked('203.0.113.77', false))->toBeTrue();
    expect(RegistrationBlockService::usernameBlocked('casinodeals', false))->toBeTrue();
    expect(RegistrationBlock::where('type', 'ip')->value('note'))->toContain('@casinodeals');
    expect($spammer->fresh()->status)->toBe('delete');
});

it('still deletes when the reported post is already gone', function () {
    $spammer = rbdSpammer();
    $report = rbdStatusReport($spammer);
    Status::whereKey($report->object_id)->forceDelete();

    rbdHandle($this, rbdAdmin(), $report, 'block-ip-delete')->assertOk();

    expect($spammer->fresh()->status)->toBe('delete');
});

it('does not duplicate rules that already cover the account', function () {
    RegistrationBlockService::store('ip', 'exact', '203.0.113.0/24');
    $spammer = rbdSpammer();

    rbdHandle($this, rbdAdmin(), rbdStatusReport($spammer), 'block-ip-delete')
        ->assertOk()
        ->assertJson(['blocked' => []]);

    expect(RegistrationBlock::count())->toBe(1);
    expect($spammer->fresh()->status)->toBe('delete');
});

it('refuses and changes nothing when no signup ip was recorded', function () {
    $spammer = rbdSpammer(['app_register_ip' => null]);

    rbdHandle($this, rbdAdmin(), rbdStatusReport($spammer), 'block-username-ip-delete')
        ->assertStatus(422)
        ->assertJsonPath('error', fn ($e) => str_contains($e, 'No signup IP'));

    expect(RegistrationBlock::count())->toBe(0);
    expect($spammer->fresh()->status)->toBeNull();
    Queue::assertNotPushed(DeleteAccountPipeline::class);
});

it('refuses when account deletion is disabled', function () {
    config(['pixelfed.account_deletion' => false]);
    $spammer = rbdSpammer();

    rbdHandle($this, rbdAdmin(), rbdStatusReport($spammer), 'block-ip-delete')->assertStatus(422);

    expect(RegistrationBlock::count())->toBe(0);
    expect($spammer->fresh()->status)->toBeNull();
});

it('refuses admin accounts', function () {
    $other = rbdSpammer(['is_admin' => true]);

    rbdHandle($this, rbdAdmin(), rbdStatusReport($other), 'block-ip-delete')->assertStatus(422);

    expect(RegistrationBlock::count())->toBe(0);
    expect($other->fresh()->status)->toBeNull();
});

it('refuses remote accounts', function () {
    $remote = Profile::factory()->create(['domain' => 'remote.example', 'user_id' => null]);
    $status = Status::factory()->create(['profile_id' => $remote->id, 'local' => false, 'uri' => 'https://remote.example/p/1']);
    $reporter = User::factory()->create(['is_admin' => false]);
    $reporter->refresh();
    $report = Report::create([
        'profile_id' => $reporter->profile_id,
        'user_id' => $reporter->id,
        'object_id' => $status->id,
        'object_type' => Status::class,
        'reported_profile_id' => $remote->id,
        'type' => 'spam',
    ]);

    rbdHandle($this, rbdAdmin(), $report, 'block-ip-delete')->assertStatus(422);

    expect(RegistrationBlock::count())->toBe(0);
});

it('blocks and deletes from the autospam modal', function () {
    $spammer = rbdSpammer();
    $report = rbdSpamReport($spammer);

    rbdSpamHandle($this, rbdAdmin(), $report, 'block-username-ip-delete-profile')
        ->assertOk()
        ->assertJson(['ip' => '203.0.113.77', 'blocked' => ['ip', 'username']]);

    expect(RegistrationBlockService::ipBlocked('203.0.113.77', false))->toBeTrue();
    expect(RegistrationBlockService::usernameBlocked('casinodeals', false))->toBeTrue();
    expect($spammer->fresh()->status)->toBe('delete');
    expect($report->fresh()->appeal_handled_at)->not->toBeNull();
    Queue::assertPushed(DeleteAccountPipeline::class);
});

it('refuses from the autospam modal when no signup ip was recorded', function () {
    $spammer = rbdSpammer(['app_register_ip' => null]);
    $report = rbdSpamReport($spammer);

    rbdSpamHandle($this, rbdAdmin(), $report, 'block-ip-delete-profile')->assertStatus(422);

    expect(RegistrationBlock::count())->toBe(0);
    expect($spammer->fresh()->status)->toBeNull();
    expect($report->fresh()->appeal_handled_at)->toBeNull();
});

it('does not let non admins use the new actions', function () {
    $spammer = rbdSpammer();
    $user = User::factory()->create(['is_admin' => false]);
    $user->refresh();

    rbdHandle($this, $user, rbdStatusReport($spammer), 'block-ip-delete');

    expect(RegistrationBlock::count())->toBe(0);
    expect($spammer->fresh()->status)->toBeNull();
});
