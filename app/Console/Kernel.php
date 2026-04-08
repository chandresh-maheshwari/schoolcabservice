<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('push:send-subscription-expiry-notifications')->dailyAt('08:00');
        $schedule->command('push:prune-mobile-notifications')->hourly();
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

    protected $commands = [
        Commands\CreateWriterProfiles::class,
        Commands\CreateRoutePermissionsCommand::class,
        Commands\SeedDemoMobileUsers::class,
        Commands\ReactivateMobileUser::class,
        Commands\SendSubscriptionExpiryNotifications::class,
        Commands\PruneMobileNotifications::class,
    ];
}
