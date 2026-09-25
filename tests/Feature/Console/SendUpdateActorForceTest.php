<?php

use App\Models\Instance;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| ap:update-actors --force must re-sync previously-synced actors
|--------------------------------------------------------------------------
|
| The resume cursor (actor-update-cache/{domain}) persisted across runs, so
| a --force re-sync kept the old id and filtered out every previously-synced
| user via where('id', '>', cursor). --force must clear the cursor.
|
*/

it('clears the resume cursor on a forced re-sync', function () {
    $domain = 'remote.example';

    // A local user to (re)deliver to.
    $user = User::factory()->create();
    $user->refresh();

    // Remote instance already synced (guard would block without --force).
    Instance::create([
        'domain' => $domain,
        'actors_last_synced_at' => now()->subDay(),
    ]);

    // Remote sharedInbox so the command can resolve a delivery target.
    $remote = new Profile;
    $remote->domain = $domain;
    $remote->username = 'peer@'.$domain;
    $remote->sharedInbox = 'https://'.$domain.'/f/inbox';
    $remote->save();

    // Stale resume cursor from a prior run, past every real user id.
    Storage::put('actor-update-cache/'.$domain, (string) ($user->id + 9999));

    $this->artisan('ap:update-actors', ['--force' => true])
        ->expectsChoice('What do you want to do?', 'Send updates to an instance', [
            'View top instances',
            'Send updates to an instance',
        ])
        ->expectsQuestion('Enter the instance domain', $domain)
        ->expectsConfirmation('Are you sure you want to send actor updates to '.$domain.'?', 'yes')
        ->assertExitCode(0);

    // The stale cursor must have been cleared, so the run reprocessed real
    // users and the cursor now reflects an actual user id (not the stale one).
    $cursor = (int) Storage::get('actor-update-cache/'.$domain);
    expect($cursor)->toBeLessThanOrEqual($user->id);
});
