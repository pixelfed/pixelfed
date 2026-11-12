<?php

use App\Models\DmMessage;
use App\Models\Instance;
use App\Models\ModeratedProfile;
use App\Models\ModLog;
use App\Models\Profile;
use App\Models\Report;
use App\Models\Status;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

function arcUser(array $attrs = []): User
{
    $user = User::factory()->create(array_merge(['is_admin' => false], $attrs));
    $user->refresh();

    return $user;
}

function arcAdmin(): User
{
    return arcUser(['is_admin' => true]);
}

function arcReport(User $reporter, int $objectId, string $objectType, ?int $reportedProfileId, array $attrs = []): Report
{
    return Report::create(array_merge([
        'profile_id' => $reporter->profile_id,
        'user_id' => $reporter->id,
        'object_id' => $objectId,
        'object_type' => $objectType,
        'reported_profile_id' => $reportedProfileId,
        'type' => 'spam',
    ], $attrs));
}

function arcStatusReport(User $reporter, ?Profile $author = null): array
{
    $author ??= arcUser()->profile;
    $status = Status::factory()->create(['profile_id' => $author->id]);

    return [$status, arcReport($reporter, $status->id, Status::class, $author->id)];
}

function arcPost($test, User $actor, string $uri, array $data = [])
{
    return $test->actingAs($actor)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->postJson($uri, $data);
}

it('closes open reports whose post was deleted', function () {
    $reporter = arcUser();
    [$softDeleted, $softReport] = arcStatusReport($reporter);
    [$gone, $goneReport] = arcStatusReport($reporter);
    [, $validReport] = arcStatusReport($reporter);

    Status::whereKey($softDeleted->id)->update(['deleted_at' => now()]);
    Status::whereKey($gone->id)->forceDelete();

    arcPost($this, arcAdmin(), '/i/admin/api/reports/cleanup')
        ->assertOk()
        ->assertJson(['closed' => 2]);

    expect($softReport->fresh()->admin_seen)->not->toBeNull();
    expect($goneReport->fresh()->admin_seen)->not->toBeNull();
    expect($validReport->fresh()->admin_seen)->toBeNull();
});

it('closes open reports whose account was deleted or suspended', function () {
    $reporter = arcUser();
    $deleting = arcUser();
    $suspended = arcUser();
    $active = arcUser();

    Profile::whereKey($deleting->profile_id)->update(['status' => 'delete']);
    Profile::whereKey($suspended->profile_id)->update(['status' => 'suspended']);

    $deletingReport = arcReport($reporter, $deleting->profile_id, Profile::class, $deleting->profile_id);
    $suspendedReport = arcReport($reporter, $suspended->profile_id, Profile::class, $suspended->profile_id);
    $missingReport = arcReport($reporter, 987654321, Profile::class, 987654321);
    $activeReport = arcReport($reporter, $active->profile_id, Profile::class, $active->profile_id);

    arcPost($this, arcAdmin(), '/i/admin/api/reports/cleanup')
        ->assertOk()
        ->assertJson(['closed' => 3]);

    expect($deletingReport->fresh()->admin_seen)->not->toBeNull();
    expect($suspendedReport->fresh()->admin_seen)->not->toBeNull();
    expect($missingReport->fresh()->admin_seen)->not->toBeNull();
    expect($activeReport->fresh()->admin_seen)->toBeNull();
});

it('closes open reports against banned remote accounts and banned instances', function () {
    $reporter = arcUser();
    $banned = Profile::factory()->create(['domain' => 'remote.example', 'user_id' => null]);
    $onBannedInstance = Profile::factory()->create(['domain' => 'banned.example', 'user_id' => null]);
    $remote = Profile::factory()->create(['domain' => 'fine.example', 'user_id' => null]);

    ModeratedProfile::create(['profile_id' => $banned->id, 'domain' => 'remote.example', 'is_banned' => true]);
    Instance::forceCreate(['domain' => 'banned.example', 'banned' => true]);

    $bannedReport = arcReport($reporter, $banned->id, Profile::class, $banned->id);
    $instanceReport = arcReport($reporter, $onBannedInstance->id, Profile::class, $onBannedInstance->id);
    $remoteReport = arcReport($reporter, $remote->id, Profile::class, $remote->id);

    arcPost($this, arcAdmin(), '/i/admin/api/reports/cleanup')
        ->assertOk()
        ->assertJson(['closed' => 2]);

    expect($bannedReport->fresh()->admin_seen)->not->toBeNull();
    expect($instanceReport->fresh()->admin_seen)->not->toBeNull();
    expect($remoteReport->fresh()->admin_seen)->toBeNull();
});

it('closes open reports whose story or direct message no longer exists', function () {
    $reporter = arcUser();
    $author = arcUser();

    $storyReport = arcReport($reporter, 123450001, Story::class, $author->profile_id);
    $dmReport = arcReport($reporter, 123450002, DmMessage::class, $author->profile_id);

    arcPost($this, arcAdmin(), '/i/admin/api/reports/cleanup')
        ->assertOk()
        ->assertJson(['closed' => 2]);

    expect($storyReport->fresh()->admin_seen)->not->toBeNull();
    expect($dmReport->fresh()->admin_seen)->not->toBeNull();
});

it('leaves closed reports alone and previews the same count', function () {
    $reporter = arcUser();
    [$status, $report] = arcStatusReport($reporter);
    [$closedStatus, $closedReport] = arcStatusReport($reporter);
    $seen = now()->subDays(3)->startOfSecond();

    $closedReport->update(['admin_seen' => $seen]);
    Status::whereKey([$status->id, $closedStatus->id])->forceDelete();

    $admin = arcAdmin();

    $this->actingAs($admin)
        ->getJson('/i/admin/api/reports/cleanup')
        ->assertOk()
        ->assertJson(['count' => 1]);

    arcPost($this, $admin, '/i/admin/api/reports/cleanup')->assertJson(['closed' => 1]);

    expect($report->fresh()->admin_seen)->not->toBeNull();
    expect($closedReport->fresh()->admin_seen->equalTo($seen))->toBeTrue();
});

it('closes every open report by the reporter of a report', function () {
    $spamReporter = arcUser();
    $otherReporter = arcUser();

    [, $first] = arcStatusReport($spamReporter);
    [, $second] = arcStatusReport($spamReporter);
    [, $other] = arcStatusReport($otherReporter);

    arcPost($this, arcAdmin(), '/i/admin/api/reports/close-by-reporter', ['id' => $first->id])
        ->assertOk()
        ->assertJson(['closed' => 2, 'reporter' => $spamReporter->username]);

    expect($first->fresh()->admin_seen)->not->toBeNull();
    expect($second->fresh()->admin_seen)->not->toBeNull();
    expect($other->fresh()->admin_seen)->toBeNull();
    expect(ModLog::where('action', 'admin.report.close_by_reporter')->where('object_uid', $spamReporter->id)->exists())->toBeTrue();
});

it('does not let non admins clean up or close reports', function () {
    $reporter = arcUser();
    [$status, $report] = arcStatusReport($reporter);
    Status::whereKey($status->id)->forceDelete();
    $user = arcUser();

    arcPost($this, $user, '/i/admin/api/reports/cleanup');
    arcPost($this, $user, '/i/admin/api/reports/close-by-reporter', ['id' => $report->id]);

    expect($report->fresh()->admin_seen)->toBeNull();
});
