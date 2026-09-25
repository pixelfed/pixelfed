<?php

use App\Models\Status;
use App\Models\User;
use App\Util\Database\DatabaseDriver;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Redis;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| statuses.visibility 'draft' ENUM repair (MariaDB)
|--------------------------------------------------------------------------
|
| The 2018 migration only widened the visibility ENUM to include 'draft' for
| the literal 'mysql' driver, so MariaDB installs rejected draft statuses. A
| companion migration repairs MariaDB. MySQL already has the value, pgsql's
| CHECK constraint was widened in 2023, and SQLite is unconstrained — so the
| migration must be a safe no-op on every non-MariaDB driver, and a draft
| status must round-trip on the driver the suite runs on.
|
*/

beforeEach(function () {
    Redis::spy();
});

function draftEnumMigration(): object
{
    return require base_path(
        'database/migrations/2026_09_25_000001_add_draft_to_status_visibility_enum_mariadb.php'
    );
}

it('is guarded to mariadb and no-ops on the other drivers', function () {
    // The suite runs on sqlite; the migration must take its early-return path
    // and leave the schema alone on any non-mariadb driver.
    expect(DatabaseDriver::isMariadb())->toBeFalse();

    draftEnumMigration()->up();

    // Schema untouched: a draft status still persists after the no-op run.
    $user = User::factory()->create();
    $user->refresh();
    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'text',
        'scope' => 'draft',
        'visibility' => 'draft',
    ]);

    expect($status->fresh()->visibility)->toBe('draft');
});

it('does not throw when re-run under the test connection', function () {
    // LazilyRefreshDatabase already ran this migration for the suite; running
    // up() again must stay a safe no-op.
    expect(fn () => draftEnumMigration()->up())->not->toThrow(Throwable::class);
});

it('persists and reads back a draft status', function () {
    $user = User::factory()->create();
    $user->refresh();

    $status = Status::factory()->create([
        'profile_id' => $user->profile_id,
        'type' => 'text',
        'scope' => 'draft',
        'visibility' => 'draft',
    ]);

    expect($status->fresh()->visibility)->toBe('draft')
        ->and($status->fresh()->scope)->toBe('draft');
});
