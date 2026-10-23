<?php

use App\Models\AccountLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Passport\Passport;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| GET /api/v1.1/accounts/login-activity
|--------------------------------------------------------------------------
|
| Login activity deduplicates by IP. It must return one row per IP, the newest
| login for that IP, deterministically (no ONLY_FULL_GROUP_BY 500, no stale
| earliest-row selection).
|
*/

function loginLog(User $user, string $ip, string $userAgent, $createdAt): AccountLog
{
    return AccountLog::create([
        'user_id' => $user->id,
        'action' => 'auth.login',
        'ip_address' => $ip,
        'user_agent' => $userAgent,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

it('returns one deterministic row per IP', function () {
    $user = User::factory()->create();
    $user->refresh();

    // IP A: an older then a newer login. IP B: a single login.
    loginLog($user, '10.0.0.1', 'OldAgent/1.0', now()->subDays(3));
    $newerA = loginLog($user, '10.0.0.1', 'NewAgent/2.0', now()->subHour());
    $b = loginLog($user, '10.0.0.2', 'AgentB/1.0', now()->subDays(2));

    Passport::actingAs($user, ['read']);

    $res = $this->getJson('/api/v1.1/accounts/login-activity')
        ->assertOk()
        ->json();

    // One row per distinct IP.
    $ips = collect($res)->pluck('ip')->all();
    expect($ips)->toHaveCount(2)
        ->and($ips)->toContain('10.0.0.1')
        ->and($ips)->toContain('10.0.0.2');

    // The row for the repeated IP must be the newest login (by id).
    $rowA = collect($res)->firstWhere('ip', '10.0.0.1');
    expect($rowA['id'])->toBe($newerA->id);
});
