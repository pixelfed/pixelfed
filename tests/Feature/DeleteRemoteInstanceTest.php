<?php

use App\Models\Follower;
use App\Models\Instance;
use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| app:delete-remote-instance removes a whole remote domain in bulk
|--------------------------------------------------------------------------
|
| Seeds a remote instance (accounts, posts, media, likes, follows, hashtag
| links, instance row) alongside untouched local data, then asserts the bulk
| purge removes every remote trace and leaves local content intact.
|
*/

function seedRemoteStatus(Profile $profile): Status
{
    $status = Status::factory()->create([
        'profile_id' => $profile->id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
        'uri' => 'https://'.$profile->domain.'/statuses/'.uniqid(),
    ]);

    DB::table('media')->insert([
        'profile_id' => $profile->id,
        'status_id' => $status->id,
        'media_path' => 'public/remote/'.$status->id.'.jpg',
        'mime' => 'image/jpeg',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('likes')->insert([
        'profile_id' => $profile->id,
        'status_id' => $status->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('status_hashtags')->insert([
        'status_id' => $status->id,
        'hashtag_id' => 1,
        'profile_id' => $profile->id,
        'status_visibility' => 'public',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $status;
}

it('bulk deletes every remote account, post and interaction for a domain', function () {
    $domain = 'badinstance.example';

    $remoteA = Profile::factory()->create(['user_id' => null, 'domain' => $domain]);
    $remoteB = Profile::factory()->create(['user_id' => null, 'domain' => $domain]);
    Instance::create(['domain' => $domain, 'banned' => false]);

    $statusA = seedRemoteStatus($remoteA);
    $statusB = seedRemoteStatus($remoteB);

    // A local user who followed one of the remote accounts.
    $localUser = User::factory()->create();
    $localUser->refresh();
    Follower::create([
        'profile_id' => $localUser->profile_id,
        'following_id' => $remoteA->id,
    ]);

    // Untouched local content on a different (local) profile.
    $localStatus = Status::factory()->create([
        'profile_id' => $localUser->profile_id,
        'type' => 'photo',
        'scope' => 'public',
        'visibility' => 'public',
    ]);

    $this->artisan('app:delete-remote-instance', ['domain' => $domain, '--force' => true])
        ->assertExitCode(0);

    // Every remote trace is gone.
    expect(Profile::whereDomain($domain)->count())->toBe(0);
    expect(Status::whereIn('id', [$statusA->id, $statusB->id])->count())->toBe(0);
    expect(Instance::whereDomain($domain)->count())->toBe(0);
    expect(DB::table('media')->whereIn('status_id', [$statusA->id, $statusB->id])->count())->toBe(0);
    expect(DB::table('likes')->whereIn('status_id', [$statusA->id, $statusB->id])->count())->toBe(0);
    expect(DB::table('status_hashtags')->whereIn('status_id', [$statusA->id, $statusB->id])->count())->toBe(0);
    expect(DB::table('followers')->where('following_id', $remoteA->id)->count())->toBe(0);

    // Local content is untouched.
    expect(Profile::whereId($localUser->profile_id)->exists())->toBeTrue();
    expect(Status::whereId($localStatus->id)->exists())->toBeTrue();
});

it('keeps and bans the instance record when --block is passed', function () {
    $domain = 'blockme.example';
    Profile::factory()->create(['user_id' => null, 'domain' => $domain]);
    Instance::create(['domain' => $domain, 'banned' => false]);

    $this->artisan('app:delete-remote-instance', ['domain' => $domain, '--force' => true, '--block' => true])
        ->assertExitCode(0);

    expect(Profile::whereDomain($domain)->count())->toBe(0);

    $instance = Instance::whereDomain($domain)->first();
    expect($instance)->not->toBeNull();
    expect((bool) $instance->banned)->toBeTrue();
});

it('deletes nothing on a dry run', function () {
    $domain = 'dryrun.example';
    $remote = Profile::factory()->create(['user_id' => null, 'domain' => $domain]);
    $status = seedRemoteStatus($remote);
    Instance::create(['domain' => $domain, 'banned' => false]);

    $this->artisan('app:delete-remote-instance', ['domain' => $domain, '--dry-run' => true])
        ->assertExitCode(0);

    expect(Profile::whereId($remote->id)->exists())->toBeTrue();
    expect(Status::whereId($status->id)->exists())->toBeTrue();
    expect(Instance::whereDomain($domain)->count())->toBe(1);
});

it('refuses to delete the local instance domain', function () {
    $local = strtolower((string) config('pixelfed.domain.app'));

    $this->artisan('app:delete-remote-instance', ['domain' => $local, '--force' => true])
        ->assertExitCode(1);
});

it('reports nothing to do for an unknown domain', function () {
    $this->artisan('app:delete-remote-instance', ['domain' => 'nothing-here.example', '--force' => true])
        ->assertExitCode(0);
});
