<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| backup:cloud must only consider the configured backup directory
|--------------------------------------------------------------------------
|
| Spatie's BackupDestination enumerates candidate files with
| allFiles($backupName). An empty name scans everything under storage/app
| and lets a stray .zip (e.g. a leftover in backup-temp) be chosen over the
| real newest backup. The command must scope selection to backup.backup.name.
|
*/

beforeEach(function () {
    // The command reads the chosen backup from storage_path('app/{path}'),
    // so the real backups live under the live storage dir. Isolate them in a
    // uniquely-named backup directory we can clean up afterwards.
    $this->backupDirName = 'pf-backup-scope-test';
    config(['backup.backup.name' => $this->backupDirName]);

    $this->realBackupDir = storage_path('app/'.$this->backupDirName);
    $this->decoyDir = storage_path('app/backup-temp');

    if (! is_dir($this->realBackupDir)) {
        mkdir($this->realBackupDir, 0755, true);
    }
    if (! is_dir($this->decoyDir)) {
        mkdir($this->decoyDir, 0755, true);
    }

    // The cloud target is faked so we can inspect what got uploaded, and the
    // command's config guard needs these credentials to be present.
    Storage::fake('backup');
    config([
        'filesystems.disks.backup.key' => 'test-key',
        'filesystems.disks.backup.secret' => 'test-secret',
        'filesystems.disks.backup.endpoint' => 'https://example.test',
        'filesystems.disks.backup.region' => 'us-east-1',
        'filesystems.disks.backup.bucket' => 'test-bucket',
    ]);
});

afterEach(function () {
    foreach (glob($this->realBackupDir.'/*') ?: [] as $f) {
        @unlink($f);
    }
    @rmdir($this->realBackupDir);

    foreach (glob($this->decoyDir.'/pf-backup-scope-decoy-*') ?: [] as $f) {
        @unlink($f);
    }
});

it('uploads the newest backup from the configured directory and ignores a stray zip', function () {
    // Real backup uses Spatie's filename format so its date comes from the name.
    $realName = '2020-01-01-00-00-00.zip';
    $realPath = $this->realBackupDir.'/'.$realName;
    file_put_contents($realPath, 'REAL_BACKUP_CONTENTS');

    // Decoy sits outside the backup directory and parses to a far newer date,
    // so an unscoped enumeration (empty backup name) would prefer it.
    $decoyName = '2099-01-01-00-00-00.zip';
    $decoyPath = $this->decoyDir.'/pf-backup-scope-decoy-'.$decoyName;
    file_put_contents($decoyPath, 'DECOY_PARTIAL_CONTENTS');

    $this->artisan('backup:cloud')->assertExitCode(0);

    // The real backup must be the one uploaded to the cloud disk.
    Storage::disk('backup')->assertExists('backups/'.$realName);
    expect(Storage::disk('backup')->get('backups/'.$realName))
        ->toBe('REAL_BACKUP_CONTENTS');

    // The decoy must never have been uploaded.
    Storage::disk('backup')->assertMissing('backups/'.$decoyName);
    Storage::disk('backup')->assertMissing('backups/pf-backup-scope-decoy-'.$decoyName);
});
