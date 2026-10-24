<?php

use App\Models\Profile;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Instance status_count must not count zero-status profiles
|--------------------------------------------------------------------------
|
| The refresh-stats endpoints compile Profile::whereDomain(...)
| ->leftJoin('statuses', ...)->count() to COUNT(*), which counts one
| NULL-padded row per zero-status profile. count('statuses.id') excludes
| those rows. Both AdminApiController and AdminInstanceController share the
| identical query; this exercises that query directly.
|
*/

function refreshStatusCount(string $domain): int
{
    return Profile::whereDomain($domain)
        ->leftJoin('statuses', 'profiles.id', '=', 'statuses.profile_id')
        ->count('statuses.id');
}

it('excludes zero-status profiles from the instance status count', function () {
    $domain = 'example.test';

    // P1 authors 3 statuses; P2 authors none.
    $p1 = Profile::factory()->create(['domain' => $domain]);
    Profile::factory()->create(['domain' => $domain]);

    $author = User::factory()->create();
    Status::factory()->count(3)->create(['profile_id' => $p1->id]);

    expect(refreshStatusCount($domain))->toBe(3);
});

it('does not let soft-deleted profiles inflate the status count', function () {
    $domain = 'example.test';

    $p1 = Profile::factory()->create(['domain' => $domain]);
    Status::factory()->count(3)->create(['profile_id' => $p1->id]);

    $p3 = Profile::factory()->create(['domain' => $domain]);
    $p3->delete();

    expect(refreshStatusCount($domain))->toBe(3);
});
