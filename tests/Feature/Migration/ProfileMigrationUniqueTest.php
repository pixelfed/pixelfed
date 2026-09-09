<?php

use App\Models\ProfileMigration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| profile_migrations unique(profile_id)
|--------------------------------------------------------------------------
|
| A profile may only have one migration row. The DB-level unique constraint
| is the hard backstop against the race that let concurrent requests create
| multiple migrations (splitting followers across targets).
|
*/

it('enforces a unique profile_id on profile_migrations', function () {
    ProfileMigration::create([
        'profile_id' => 12345,
        'acct' => '@alice@remote.example',
        'followers_count' => 3,
        'target_profile_id' => 999,
    ]);

    $threw = false;
    try {
        ProfileMigration::create([
            'profile_id' => 12345,
            'acct' => '@alice@other.example',
            'followers_count' => 3,
            'target_profile_id' => 1000,
        ]);
    } catch (QueryException $e) {
        $threw = true;
    }

    expect($threw)->toBeTrue();
    expect(ProfileMigration::where('profile_id', 12345)->count())->toBe(1);
});
