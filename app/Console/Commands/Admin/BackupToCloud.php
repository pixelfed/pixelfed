<?php

namespace App\Console\Commands\Admin;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestination;

#[Signature('backup:cloud')]
#[Description('Send backups to cloud storage')]
final class BackupToCloud extends Command
{
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $localDisk = Storage::disk('local');
        $cloudDisk = Storage::disk('backup');
        $backupDestination = new BackupDestination($localDisk, '', 'local');

        if (
            empty(config('filesystems.disks.backup.key')) ||
            empty(config('filesystems.disks.backup.secret')) ||
            empty(config('filesystems.disks.backup.endpoint')) ||
            empty(config('filesystems.disks.backup.region')) ||
            empty(config('filesystems.disks.backup.bucket'))
        ) {
            $this->error('Backup disk not configured.');
            $this->error('See https://docs.pixelfed.org/technical-documentation/env.html#filesystem for more information.');

            return Command::FAILURE;
        }

        $newest = $backupDestination->newestBackup();

        if ($newest === null) {
            $this->error('No backup found to upload.');

            return Command::FAILURE;
        }

        $name = $newest->path();
        $parts = explode('/', $name);
        $fileName = array_pop($parts);
        $storagePath = 'backups';
        $path = storage_path('app/'.$name);
        $file = $cloudDisk->putFileAs($storagePath, new File($path), $fileName, 'private');

        if ($file === false) {
            $this->error('Failed to upload the backup to cloud storage.');

            return Command::FAILURE;
        }

        $this->info('Backup file successfully saved!');
        $url = $cloudDisk->url($file);
        $this->table(
            ['Name', 'URL'],
            [
                [$fileName, $url],
            ],
        );

        return Command::SUCCESS;
    }
}
