<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('media:optimize')->hourlyAt(40)->onOneServer();
        $schedule->command('media:gc')->hourlyAt(5)->onOneServer();
        $schedule->command('horizon:snapshot')->everyFiveMinutes()->onOneServer();
        $schedule->command('story:gc')->everyFiveMinutes()->onOneServer();
        $schedule->command('gc:failedjobs')->dailyAt(3)->onOneServer();
        $schedule->command('gc:passwordreset')->dailyAt('09:41')->onOneServer();
        $schedule->command('gc:sessions')->twiceDaily(13, 23)->onOneServer();
        $schedule->command('app:weekly-instance-scan')->weeklyOn(2, '4:20')->onOneServer();
        $schedule->command('app:cleanup-expired-app-registrations')->dailyAt(1)->onOneServer();

        if ((bool) config_cache('pixelfed.cloud_storage') && (bool) config_cache('media.delete_local_after_cloud')) {
            $schedule->command('media:s3gc')->hourlyAt(15);
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
        $schedule->command('app:instance-update-total-local-posts')->twiceDailyAt(1, 13, 45)->onOneServer();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
