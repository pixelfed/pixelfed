<?php

use App\Models\Hashtag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Hashtag collation fix (PR #6098)
|--------------------------------------------------------------------------
|
| Verifies that the hashtags collation migration runs safely on all drivers
| and, on MySQL/MariaDB, prevents conflation of distinct hashtags that use
| characters outside the Basic Multilingual Plane (codepoints >= 0x10000).
|
| It also verifies that pre-existing rows which become equal under the
| stricter target collation are merged before the collation change, so the
| migration no longer fails with a duplicate-entry (1062) error.
|
*/

it('migration runs without error regardless of database driver', function () {
    // The test suite uses sqlite by default. The migration detects the driver
    // and no-ops gracefully rather than attempting unsupported ALTER syntax.
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_07_31_164635_change_hashtags_collation.php',
        '--force' => true,
    ]);

    // If we reach here without exception the no-op path worked.
    expect(true)->toBeTrue();
});

it('migration rollback runs without error regardless of database driver', function () {
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_07_31_164635_change_hashtags_collation.php',
        '--force' => true,
    ]);

    Artisan::call('migrate:rollback', [
        '--path' => 'database/migrations/2025_07_31_164635_change_hashtags_collation.php',
        '--force' => true,
    ]);

    expect(true)->toBeTrue();
});

it('distinct BMP-outside hashtags do not collide on the unique index', function () {
    // This test is only meaningful on MySQL/MariaDB where the collation fix matters.
    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->markTestSkipped('Collation behavior is MySQL/MariaDB-specific.');
    }

    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_07_31_164635_change_hashtags_collation.php',
        '--force' => true,
    ]);

    // Two distinct 5-character hashtags using characters outside the BMP.
    // Under the old utf8mb4_unicode_ci collation these were considered equal.
    $shavian = '𐑖𐑱𐑝𐑾𐑯';     // Shavian script
    $cuneiform = '𒆳𒆍𒀭𒊏𒆠';   // Cuneiform script

    $tag1 = Hashtag::create(['name' => $shavian, 'slug' => $shavian]);
    $tag2 = Hashtag::create(['name' => $cuneiform, 'slug' => $cuneiform]);

    // Both must coexist as separate rows with distinct IDs.
    expect($tag1->id)->not->toBe($tag2->id);
    expect(Hashtag::where('slug', $shavian)->first()->id)->toBe($tag1->id);
    expect(Hashtag::where('slug', $cuneiform)->first()->id)->toBe($tag2->id);
});

it('merges pre-existing colliding rows instead of failing the ALTER', function () {
    // Reproduces the original failure: rows that are distinct under the old
    // collation but equal under the target collation must be merged, and their
    // references repointed, before the unique index is re-enforced.
    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->markTestSkipped('Collation behavior is MySQL/MariaDB-specific.');
    }

    // Seed the table under the OLD collation so the two BMP-outside tags are
    // stored as separate rows (they only collide under the target collation).
    DB::statement('ALTER TABLE `hashtags` MODIFY `name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
    DB::statement('ALTER TABLE `hashtags` MODIFY `slug` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');

    $shavian = '𐑖𐑱𐑝𐑾𐑯';
    $cuneiform = '𒆳𒆍𒀭𒊏𒆠';

    $keep = Hashtag::create(['name' => $shavian, 'slug' => $shavian]);
    $dup = Hashtag::create(['name' => $cuneiform, 'slug' => $cuneiform]);

    // A status_hashtags reference pointing at the row that will be merged away.
    DB::table('status_hashtags')->insert([
        'status_id' => 1,
        'hashtag_id' => $dup->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Running the migration must not throw a duplicate-entry error.
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_07_31_164635_change_hashtags_collation.php',
        '--force' => true,
    ]);

    // The duplicate row is gone, the surviving row remains.
    expect(Hashtag::find($dup->id))->toBeNull();
    expect(Hashtag::find($keep->id))->not->toBeNull();

    // The reference was repointed to the surviving hashtag.
    expect(DB::table('status_hashtags')->where('hashtag_id', $dup->id)->count())->toBe(0);
    expect(DB::table('status_hashtags')->where('hashtag_id', $keep->id)->count())->toBe(1);
});

it('same-script hashtags with the same slug still correctly deduplicate', function () {
    // Sanity check: two identical hashtags should NOT create duplicates.
    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->markTestSkipped('Collation behavior is MySQL/MariaDB-specific.');
    }

    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_07_31_164635_change_hashtags_collation.php',
        '--force' => true,
    ]);

    $tag = Hashtag::firstOrCreate(['slug' => 'hello'], ['name' => 'hello']);
    $same = Hashtag::firstOrCreate(['slug' => 'hello'], ['name' => 'hello']);

    expect($tag->id)->toBe($same->id);
    expect(Hashtag::where('slug', 'hello')->count())->toBe(1);
});

it('case-insensitivity is preserved after collation change', function () {
    // utf8mb4_unicode_520_ci is still case-insensitive, so #Hello == #hello.
    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->markTestSkipped('Collation behavior is MySQL/MariaDB-specific.');
    }

    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_07_31_164635_change_hashtags_collation.php',
        '--force' => true,
    ]);

    Hashtag::create(['name' => 'Pixelfed', 'slug' => 'pixelfed']);

    // Case-insensitive lookup should find it with different casing.
    $found = Hashtag::where('slug', 'PIXELFED')->first();
    expect($found)->not->toBeNull();
    expect($found->slug)->toBe('pixelfed');
});
