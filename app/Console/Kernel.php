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
        'App\Console\Commands\ProcessWeGiveRecurringDonations',
        'App\Console\Commands\UpdateTilledTransactions',
        'App\Console\Commands\SendBrianEmail',
        'App\Console\Commands\ClearExpiredVerificationCodes',
        'App\Console\Commands\ProcessDailyRollupEmails',
        'App\Console\Commands\ProcessWeeklyRollupEmails',
        'App\Console\Commands\CheckForExpiredCards',

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('command:process-wegive-donations')->dailyAt('12:00')->evenInMaintenanceMode();
        $schedule->command('command:update-tilled-transactions')->dailyAt('15:00')->evenInMaintenanceMode();
        $schedule->command('command:send-brian-email')->dailyAt('14:00')->evenInMaintenanceMode();
        $schedule->command('command:send-brian-email')->dailyAt('14:00')->evenInMaintenanceMode();
        $schedule->command('command:clear-verification-codes')->everyMinute()->evenInMaintenanceMode();
        $schedule->command('command:process-daily-rollup-emails')->dailyAt('6:59')->evenInMaintenanceMode();
        $schedule->command('command:process-weekly-rollup-emails')->saturdays()->at('6:59')->evenInMaintenanceMode();
        // $schedule->command('command:check-for-expired-cards expired')->lastDayOfMonth('6:59');
        $schedule->command('telescope:prune --hours=48')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
