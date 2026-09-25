---
name: laravel-backup
description: "Configure and extend spatie/laravel-backup for database and file backups, cleanup strategies, health monitoring, and notifications. Activates when working with backup configuration, scheduling backups, creating custom cleanup strategies or health checks, customizing notifications, or when the user mentions backups, backup monitoring, backup cleanup, or spatie/laravel-backup."
license: MIT
metadata:
  author: spatie
---

# Laravel Backup

## When to Apply

Activate this skill when:

- Configuring backup sources, destinations, or notifications
- Scheduling backup, cleanup, or monitor commands
- Creating custom cleanup strategies or health checks
- Customizing backup notifications
- Troubleshooting backup failures

## Key Commands

```bash
# Run a backup
php artisan backup:run

# Backup only the database
php artisan backup:run --only-db

# Backup specific database connections
php artisan backup:run --db-name=mysql --db-name=pgsql

# Backup only files (no database)
php artisan backup:run --only-files

# Backup to a specific disk
php artisan backup:run --only-to-disk=s3

# Custom filename
php artisan backup:run --filename=my-backup.zip

# Clean old backups
php artisan backup:clean

# List all backups
php artisan backup:list

# Monitor backup health
php artisan backup:monitor

# Use an alternative config key
php artisan backup:run --config=backup_secondary
```

## Scheduling

Add to `routes/console.php` or `app/Console/Kernel.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('10:00');
```

## Configuration

Published to `config/backup.php` with sections: `backup` (source files/databases, destination disks, encryption), `notifications` (mail, Slack, Discord), `monitor_backups` (health checks), and `cleanup` (retention strategy).

### Database Dump Customization

Customize dumps per connection in `config/database.php`:

```php
'mysql' => [
    // ...
    'dump' => [
        'exclude_tables' => ['telescope_entries', 'telescope_monitoring'],
        'useSingleTransaction' => true,
    ],
],
```

Enable dump compression:

```php
// config/backup.php
'database_dump_compressor' => \Spatie\DbDumper\Compressors\GzipCompressor::class,
```

### Multiple Backup Destinations

```php
// config/backup.php
'destination' => [
    'disks' => ['local', 's3'],
],
```

### Encryption

```php
// config/backup.php
'password' => env('BACKUP_ARCHIVE_PASSWORD'),
'encryption' => 'default', // Uses ZipArchive::EM_AES_256
```

## Custom Cleanup Strategy

Extend `Spatie\Backup\Tasks\Cleanup\CleanupStrategy` and implement `deleteOldBackups`:

```php
use Spatie\Backup\BackupDestination\BackupCollection;
use Spatie\Backup\Tasks\Cleanup\CleanupStrategy;

class MyCleanupStrategy extends CleanupStrategy
{
    public function deleteOldBackups(BackupCollection $backups): void
    {
        $backups
            ->reject(fn ($backup) => $backup->date()->gt(now()->subMonth()))
            ->each(fn ($backup) => $backup->delete());
    }
}
```

Register in `config/backup.php`:

```php
'cleanup' => [
    'strategy' => \App\Backup\MyCleanupStrategy::class,
],
```

## Custom Health Check

Extend `Spatie\Backup\Tasks\Monitor\HealthCheck` and implement `checkHealth`:

```php
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Tasks\Monitor\HealthCheck;

class MinimumBackupCount extends HealthCheck
{
    protected int $minimumCount;

    public function __construct(int $minimumCount = 3)
    {
        $this->minimumCount = $minimumCount;
    }

    public function checkHealth(BackupDestination $backupDestination): void
    {
        $this->failIf(
            $backupDestination->backups()->count() < $this->minimumCount,
            "Expected at least {$this->minimumCount} backups."
        );
    }
}
```

Register in `config/backup.php`:

```php
'health_checks' => [
    \App\Backup\MinimumBackupCount::class => 5,
],
```

## Custom Notification

Extend `Spatie\Backup\Notifications\BaseNotification`:

```php
use Illuminate\Notifications\Messages\MailMessage;
use Spatie\Backup\Notifications\BaseNotification;

class CustomBackupFailedNotification extends BaseNotification
{
    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("Backup failed for {$this->applicationName()}")
            ->line($this->event->exception->getMessage());
    }
}
```

Map it in `config/backup.php`:

```php
'notifications' => [
    'notifications' => [
        \App\Notifications\CustomBackupFailedNotification::class => ['mail'],
    ],
],
```

## Events

All events are in `Spatie\Backup\Events`:

- `BackupWasSuccessful` - Backup completed successfully
- `BackupHasFailed` - Backup failed (includes exception and optional backup destination)
- `BackupManifestWasCreated` - File manifest created before zipping
- `BackupZipWasCreated` - Zip archive created (used for encryption hook)
- `DumpingDatabase` - Database dump in progress
- `CleanupWasSuccessful` / `CleanupHasFailed` - Cleanup result
- `HealthyBackupWasFound` / `UnhealthyBackupWasFound` - Monitor result

## Common Pitfalls

- Forgetting to schedule `backup:clean` alongside `backup:run`, causing disk space to fill up
- Not excluding `vendor` and `node_modules` from file backups (excluded by default)
- Setting `only-db` and `only-files` together (mutually exclusive)
- Missing the `ext-zip` PHP extension
- Not configuring the notification `mail.to` address after publishing config
