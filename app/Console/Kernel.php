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
        $schedule->command('media:optimize')->hourly();
        $schedule->command('media:gc')->hourly();
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('story:gc')->everyFiveMinutes();
        $schedule->command('gc:failedjobs')->dailyAt(3);
        $schedule->command('gc:passwordreset')->dailyAt('09:41');
        $schedule->command('gc:sessions')->twiceDaily(13, 23);
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
