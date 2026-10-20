<?php

use App\Models\User;
use App\Services\UserFilterService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| app:add-user-domain-block / app:delete-user-domain-block cache
|--------------------------------------------------------------------------
|
| The bulk block/unblock commands must refresh the per-profile domain-block
| cache so changes take effect immediately instead of serving a stale list
| for up to the 6h TTL.
|
*/

$blockedTestDomain = 'bad.example';

it('refreshes the per-profile domain-block cache when blocking and unblocking', function () use ($blockedTestDomain) {
    $user = User::factory()->create();
    $user->refresh();
    $pid = $user->profile_id;

    // Warm the cache before any block exists so a stale read would return [].
    expect(UserFilterService::domainBlocks($pid))->toBe([]);

    $this->artisan('app:add-user-domain-block')
        ->expectsQuestion('Enter domain you want to block', $blockedTestDomain)
        ->expectsConfirmation('Are you sure you want to block '.$blockedTestDomain.'?', 'yes')
        ->assertExitCode(0);

    expect(UserFilterService::domainBlocks($pid))->toContain($blockedTestDomain);

    $this->artisan('app:delete-user-domain-block')
        ->expectsQuestion('Enter domain you want to unblock', $blockedTestDomain)
        ->expectsConfirmation('Are you sure you want to unblock '.$blockedTestDomain.'?', 'yes')
        ->assertExitCode(0);

    expect(UserFilterService::domainBlocks($pid))->not->toContain($blockedTestDomain);
});
