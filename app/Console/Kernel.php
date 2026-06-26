<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\ClearZerorRemainingRecords::class,
        \App\Console\Commands\TestDepartmentBalance::class,
        \App\Console\Commands\StoreDeparemtnsRecipesBalances::class,
        \App\Console\Commands\ActivateBookingsCommand::class,
        \App\Service\NetworkTracking\Console\Commands\ScanArpTable::class,
        \App\Console\Commands\MigrateOfficersAndBeneficiariesCommand::class,
        \App\Console\Commands\ImportIdDataCommand::class,
        \App\Console\Commands\ImportBeneficiaryDataCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        //    $schedule->command('clear:zero-remaining-records')->everyMinute();
        //    $schedule->command('store-deparemtns-recipes-balances')->dailyAt('2:00');

        $schedule
            ->command('app:change-cashier-department')
            ->dailyAt('00:05')
            ->dailyAt('08:05')
            ->dailyAt('16:05');

        $schedule
            ->command('app:change-cashier-department')
            ->everyTenMinutes();

        // $schedule
        //     ->command('app:backup-database')
        //     ->dailyAt('16:05');

        // $schedule
        //     ->command('app:backup-database')
        //     ->dailyAt('5:00');

        // $schedule
        // ->command('app:change-cashier-department')
        //    ->everyMinute();

        $schedule
            ->command('app:capture-store-recipe-details')
            ->dailyAt('00:01')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule
            ->command('app:capture-store-recipe-details')
            ->dailyAt('08:01')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule
            ->command('app:capture-store-recipe-details')
            ->dailyAt('16:01')
            ->withoutOverlapping()
            ->onOneServer();

        // Activate confirmed bookings with check-in date tomorrow
        $schedule
            ->command('bookings:activate-tomorrow')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule
            ->command('network:scan-arp')
            ->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
