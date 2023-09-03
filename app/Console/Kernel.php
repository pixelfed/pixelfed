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
        $schedule->command('media:optimize')->hourlyAt(40)->onOneServer();
        $schedule->command('media:gc')->hourlyAt(5)->onOneServer();
        $schedule->command('horizon:snapshot')->everyFiveMinutes()->onOneServer();
        $schedule->command('story:gc')->everyFiveMinutes()->onOneServer();
        $schedule->command('gc:failedjobs')->dailyAt(3)->onOneServer();
        $schedule->command('gc:passwordreset')->dailyAt('09:41')->onOneServer();
        $schedule->command('gc:sessions')->twiceDaily(13, 23)->onOneServer();

        if(in_array(config_cache('pixelfed.cloud_storage'), ['1', true, 'true']) && config('media.delete_local_after_cloud')) {
            $schedule->command('media:s3gc')->hourlyAt(15)->onOneServer();
        }

        if(config('import.instagram.enabled')) {
            $schedule->command('app:transform-imports')->everyFourMinutes()->onOneServer();
            $schedule->command('app:import-upload-garbage-collection')->hourlyAt(51)->onOneServer();
            $schedule->command('app:import-remove-deleted-accounts')->hourlyAt(37)->onOneServer();
            $schedule->command('app:import-upload-clean-storage')->twiceDailyAt(1, 13, 32)->onOneServer();
        }
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
