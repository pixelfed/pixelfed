<?php

use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| storage:maintenance
|--------------------------------------------------------------------------
|
| Sweeps stale remcache temp files and prunes empty directories left behind
| by the media/story/avatar/import storage flows.
|
*/

beforeEach(function () {
    Storage::fake('local');

    // remcache lives under the real storage_path, not the faked disk root, so
    // isolate a temp remcache dir for the age-based sweep assertions.
    $this->remcacheDir = storage_path('app/remcache');
    if (! is_dir($this->remcacheDir)) {
        mkdir($this->remcacheDir, 0755, true);
    }
});

afterEach(function () {
    // Clean up any temp files this test created in the real remcache dir.
    foreach (glob($this->remcacheDir.'/{,.}pf-maint-test-*', GLOB_BRACE) ?: [] as $f) {
        @unlink($f);
    }
});

it('deletes stale remcache files older than the cutoff and preserves fresh ones and dotfiles', function () {
    $stale = $this->remcacheDir.'/pf-maint-test-stale.tmp';
    $fresh = $this->remcacheDir.'/pf-maint-test-fresh.tmp';
    // A stale dotfile (like the directory's .gitignore) must be preserved.
    $dot = $this->remcacheDir.'/.pf-maint-test-keep';

    file_put_contents($stale, 'old');
    file_put_contents($fresh, 'new');
    file_put_contents($dot, 'keep');
    touch($stale, now()->subHours(48)->getTimestamp());
    touch($dot, now()->subHours(48)->getTimestamp());

    $this->artisan('storage:maintenance', ['--only' => 'remcache', '--hours' => 24])
        ->assertExitCode(0);

    expect(is_file($stale))->toBeFalse();
    expect(is_file($fresh))->toBeTrue();
    // Dotfiles (e.g. .gitignore) are always preserved.
    expect(is_file($dot))->toBeTrue();
});

it('dry-run reports remcache deletions without removing files', function () {
    $stale = $this->remcacheDir.'/pf-maint-test-stale.tmp';
    file_put_contents($stale, 'old');
    touch($stale, now()->subHours(48)->getTimestamp());

    $this->artisan('storage:maintenance', ['--only' => 'remcache', '--hours' => 24, '--dry-run' => true])
        ->assertExitCode(0);

    expect(is_file($stale))->toBeTrue();
});

it('prunes empty directories across managed trees while keeping live files', function () {
    $disk = Storage::disk('local');

    // Empty leftovers.
    $disk->makeDirectory('public/m/_v2/9/month/rand');
    $disk->makeDirectory('public/_esm.t3/m/u/leaf');
    $disk->makeDirectory('public/avatars/000/111/222');
    $disk->makeDirectory('imports/42');
    $disk->makeDirectory('story_archives/7/202601');

    // Live files that must survive.
    $disk->put('public/m/_v2/9/month/keep/live.jpg', 'x');
    $disk->put('imports/99/live.jpg', 'y');

    $this->artisan('storage:maintenance', ['--only' => 'empty-dirs'])
        ->assertExitCode(0);

    expect($disk->directoryExists('public/m/_v2/9/month/rand'))->toBeFalse();
    expect($disk->directoryExists('public/_esm.t3/m'))->toBeFalse();
    expect($disk->directoryExists('public/avatars/000'))->toBeFalse();
    expect($disk->directoryExists('imports/42'))->toBeFalse();
    expect($disk->directoryExists('story_archives/7'))->toBeFalse();

    // Roots and live branches preserved.
    expect($disk->exists('public/m/_v2/9/month/keep/live.jpg'))->toBeTrue();
    expect($disk->exists('imports/99/live.jpg'))->toBeTrue();
});

it('dry-run reports empty-dir removals without deleting them', function () {
    $disk = Storage::disk('local');
    $disk->makeDirectory('public/m/_v2/9/month/rand');

    $this->artisan('storage:maintenance', ['--only' => 'empty-dirs', '--dry-run' => true])
        ->assertExitCode(0);

    expect($disk->directoryExists('public/m/_v2/9/month/rand'))->toBeTrue();
});

it('rejects an --hours value below 1', function () {
    $this->artisan('storage:maintenance', ['--only' => 'remcache', '--hours' => 0])
        ->assertExitCode(1);
});

it('errors when only/except leave no tasks to run', function () {
    $this->artisan('storage:maintenance', ['--except' => 'remcache,empty-dirs'])
        ->assertExitCode(1);
});
