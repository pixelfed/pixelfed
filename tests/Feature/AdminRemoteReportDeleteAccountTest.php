<?php

use App\Jobs\DeletePipeline\DeleteAccountPipeline;
use App\Models\Profile;
use App\Models\RemoteReport;
use App\Models\Report;
use App\Models\Status;
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

function rdaUser(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['is_admin' => false], $attrs));
    $user->refresh();

    return $user;
}

function rdaRemoteReport(int $accountId, array $attrs = []): RemoteReport
{
    return RemoteReport::forceCreate(array_merge([
        'status_ids' => [],
        'comment' => 'spam',
        'account_id' => $accountId,
        'uri' => 'https://remote.example/reports/'.uniqid(),
        'report_meta' => ['actor' => 'https://remote.example/actor', 'object' => []],
    ], $attrs));
}

function rdaLocalReport(User $reporter, int $objectId, string $objectType, int $reportedProfileId): Report
{
    return Report::create([
        'profile_id' => $reporter->profile_id,
        'user_id' => $reporter->id,
        'object_id' => $objectId,
        'object_type' => $objectType,
        'reported_profile_id' => $reportedProfileId,
        'type' => 'spam',
    ]);
}

function rdaDelete($test, User $actor, RemoteReport $report, bool $ban = false)
{
    return $test->actingAs($actor)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->postJson('/i/admin/api/reports/remote/delete-account', ['id' => $report->id, 'ban' => $ban]);
}

it('deletes the reported local account and closes every report against it', function () {
    $spammer = rdaUser(['username' => 'spammer']);
    $bystander = rdaUser();
    $reporter = rdaUser();

    $report = rdaRemoteReport($spammer->profile_id);
    $otherRemote = rdaRemoteReport($spammer->profile_id);
    $bystanderRemote = rdaRemoteReport($bystander->profile_id);

    $status = Status::factory()->create(['profile_id' => $spammer->profile_id]);
    $postReport = rdaLocalReport($reporter, $status->id, Status::class, $spammer->profile_id);
    $profileReport = rdaLocalReport($reporter, $spammer->profile_id, Profile::class, $spammer->profile_id);
    $bystanderLocal = rdaLocalReport($reporter, $bystander->profile_id, Profile::class, $bystander->profile_id);

    rdaDelete($this, rdaUser(['is_admin' => true]), $report)
        ->assertOk()
        ->assertJson(['username' => 'spammer', 'closed_remote' => 2, 'closed_local' => 2]);

    expect($report->fresh()->action_taken_at)->not->toBeNull();
    expect($otherRemote->fresh()->action_taken_at)->not->toBeNull();
    expect($bystanderRemote->fresh()->action_taken_at)->toBeNull();

    expect($postReport->fresh()->admin_seen)->not->toBeNull();
    expect($profileReport->fresh()->admin_seen)->not->toBeNull();
    expect($bystanderLocal->fresh()->admin_seen)->toBeNull();

    $spammer->refresh();
    expect($spammer->status)->toBe('delete');
    expect($spammer->email)->toEqual((string) $spammer->id);
    expect(Profile::find($spammer->profile_id)->status)->toBe('delete');

    Queue::assertPushed(DeleteAccountPipeline::class);
});

it('refuses admin accounts', function () {
    $admin = rdaUser(['is_admin' => true]);
    $report = rdaRemoteReport($admin->profile_id);

    rdaDelete($this, rdaUser(['is_admin' => true]), $report)->assertStatus(422);

    expect($admin->fresh()->status)->toBeNull();
    expect($report->fresh()->action_taken_at)->toBeNull();
    Queue::assertNotPushed(DeleteAccountPipeline::class);
});

it('refuses remote or missing reported accounts', function () {
    $remote = Profile::factory()->create(['domain' => 'remote.example', 'user_id' => null]);
    $remoteReport = rdaRemoteReport($remote->id);
    $missingReport = rdaRemoteReport(987654321);
    $admin = rdaUser(['is_admin' => true]);

    rdaDelete($this, $admin, $remoteReport)->assertStatus(422);
    rdaDelete($this, $admin, $missingReport)->assertStatus(422);

    expect($remote->fresh()->status)->toBeNull();
    expect($remoteReport->fresh()->action_taken_at)->toBeNull();
    Queue::assertNotPushed(DeleteAccountPipeline::class);
});

it('refuses accounts that are already being deleted', function () {
    $spammer = rdaUser();
    User::whereKey($spammer->id)->update(['status' => 'delete']);
    $report = rdaRemoteReport($spammer->profile_id);

    rdaDelete($this, rdaUser(['is_admin' => true]), $report)->assertStatus(422);

    expect($report->fresh()->action_taken_at)->toBeNull();
});

it('does not let non admins delete accounts', function () {
    $spammer = rdaUser();
    $report = rdaRemoteReport($spammer->profile_id);

    rdaDelete($this, rdaUser(), $report);

    expect($spammer->fresh()->status)->toBeNull();
    expect($report->fresh()->action_taken_at)->toBeNull();
});

it('bans the signup ip and username, deletes the account and closes its reports', function () {
    $spammer = rdaUser(['username' => 'casinodeals', 'app_register_ip' => '203.0.113.77']);
    $reporter = rdaUser();
    $report = rdaRemoteReport($spammer->profile_id);
    $localReport = rdaLocalReport($reporter, $spammer->profile_id, Profile::class, $spammer->profile_id);

    rdaDelete($this, rdaUser(['is_admin' => true]), $report, true)
        ->assertOk()
        ->assertJson([
            'username' => 'casinodeals',
            'ip' => '203.0.113.77',
            'blocked' => ['ip', 'username'],
            'closed_remote' => 1,
            'closed_local' => 1,
        ]);

    expect(RegistrationBlockService::ipBlocked('203.0.113.77', false))->toBeTrue();
    expect(RegistrationBlockService::usernameBlocked('casinodeals', false))->toBeTrue();
    expect($spammer->fresh()->status)->toBe('delete');
    expect($report->fresh()->action_taken_at)->not->toBeNull();
    expect($localReport->fresh()->admin_seen)->not->toBeNull();
    Queue::assertPushed(DeleteAccountPipeline::class);
});

it('refuses to ban and changes nothing when no signup ip was recorded', function () {
    $spammer = rdaUser(['app_register_ip' => null]);
    $report = rdaRemoteReport($spammer->profile_id);

    rdaDelete($this, rdaUser(['is_admin' => true]), $report, true)
        ->assertStatus(422)
        ->assertJsonPath('error', fn ($e) => str_contains($e, 'No signup IP'));

    expect(RegistrationBlock::count())->toBe(0);
    expect($spammer->fresh()->status)->toBeNull();
    expect($report->fresh()->action_taken_at)->toBeNull();
    Queue::assertNotPushed(DeleteAccountPipeline::class);
});

it('refuses to ban when account deletion is disabled', function () {
    config(['pixelfed.account_deletion' => false]);
    $spammer = rdaUser(['app_register_ip' => '203.0.113.77']);
    $report = rdaRemoteReport($spammer->profile_id);

    rdaDelete($this, rdaUser(['is_admin' => true]), $report, true)->assertStatus(422);

    expect(RegistrationBlock::count())->toBe(0);
    expect($spammer->fresh()->status)->toBeNull();
});
