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
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('media:optimize')->hourlyAt(40);
        $schedule->command('media:gc')->hourlyAt(5);
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('story:gc')->everyFiveMinutes();
        $schedule->command('gc:failedjobs')->dailyAt(3);
        $schedule->command('gc:passwordreset')->dailyAt('09:41');
        $schedule->command('gc:sessions')->twiceDaily(13, 23);

        if(in_array(config_cache('pixelfed.cloud_storage'), ['1', true, 'true']) && config('media.delete_local_after_cloud')) {
            $schedule->command('media:s3gc')->hourlyAt(15);
        }

        if(config('import.instagram.enabled')) {
            $schedule->command('app:transform-imports')->everyFourMinutes();
            $schedule->command('app:import-upload-garbage-collection')->hourlyAt(51);
            $schedule->command('app:import-remove-deleted-accounts')->hourlyAt(37);
            $schedule->command('app:import-upload-clean-storage')->twiceDailyAt(1, 13, 32);
        }
        $schedule->command('app:notification-epoch-update')->weeklyOn(1, '2:21');
        $schedule->command('app:hashtag-cached-count-update')->hourlyAt(25);
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
