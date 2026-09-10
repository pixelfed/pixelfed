<?php

use Illuminate\Console\Scheduling\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Central definition of the application's scheduled commands. This file is
| required from bootstrap/app.php's withSchedule() closure with the active
| Schedule instance available as $schedule.
|
*/

/** @var Schedule $schedule */

$schedule->command('media:optimize')->hourlyAt(40)->onOneServer();
$schedule->command('media:gc')->hourlyAt(5)->onOneServer();
$schedule->command('horizon:snapshot')->everyFiveMinutes()->onOneServer();
$schedule->command('story:gc')->everyFiveMinutes()->onOneServer();
$schedule->command('gc:failedjobs')->dailyAt(3)->onOneServer();
$schedule->command('gc:passwordreset')->dailyAt('09:41')->onOneServer();
$schedule->command('gc:sessions')->twiceDaily(13, 23)->onOneServer();
$schedule->command('storage:maintenance')->dailyAt('04:15')->onOneServer();
$schedule->command('app:weekly-instance-scan')->weeklyOn(2, '4:20')->onOneServer();
$schedule->command('app:cleanup-expired-app-registrations')->dailyAt(1)->onOneServer();
$schedule->command('passport:purge')->everyFourHours(20)->onOneServer();
$schedule->command('notifications:prune-old')->everySixHours(33)->onOneServer()->withoutOverlapping(360);

if ((bool) config_cache('pixelfed.cloud_storage') && (bool) config_cache('media.delete_local_after_cloud')) {
    // Upload any local stragglers to cloud and GC verified local copies.
    $schedule->command('admin:MediaMoveStorageLocalToCloud --force --limit=500')->hourlyAt(15);
}

if (config('import.instagram.enabled')) {
    $schedule->command('app:transform-imports')->twiceDaily(13, 22)->onOneServer();
    $schedule->command('app:import-upload-garbage-collection')->hourlyAt(51)->onOneServer();
    $schedule->command('app:import-remove-deleted-accounts')->hourlyAt(37)->onOneServer();
    $schedule->command('app:import-upload-clean-storage')->twiceDailyAt(1, 13, 32)->onOneServer();

    if (config('import.instagram.storage.cloud.enabled') && (bool) config_cache('pixelfed.cloud_storage')) {
        $schedule->command('app:import-upload-media-to-cloud-storage')->hourlyAt(39)->onOneServer();
    }
}

$schedule->command('app:notification-epoch-update')->weeklyOn(1, '2:21')->onOneServer();
$schedule->command('app:hashtag-cached-count-update')->hourlyAt(25)->onOneServer();
$schedule->command('app:account-post-count-stat-update')->everySixHours(25)->onOneServer();

if ((bool) config('scheduledtasks.account_storage_reconcile')) {
    $schedule->command('user:storage:recalculate --stale=168')->weeklyOn(3, '3:30')->onOneServer()->withoutOverlapping(1440);
}

$schedule->command('app:instance-update-total-local-posts')->twiceDailyAt(1, 13, 45)->onOneServer();
