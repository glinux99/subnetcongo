<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Cycle de vie automatique des sessions live :
        // Scheduled → Live → Ended + rappels automatiques.
        $schedule->command('live-sessions:sweep')
            ->everyMinute()
            ->withoutOverlapping();

        // Métriques infra du dashboard admin : diffusion WebSocket uniquement.
        // Le scheduler déclenche la mesure ; le navigateur ne recharge pas la page.
        $schedule->command('dashboard:broadcast-metrics')
            ->everyMinute()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
