<?php

namespace Biigle\Console;

use TileCache;
use ImageCache;
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
        Commands\NewUser::class,
        Commands\UpdateThumbnailStorageScheme::class,
        Commands\UpdateVolumeUrls::class,
        // Insert console commands here.
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            ImageCache::prune();
        })->everyFiveMinutes();

        $schedule->call(function () {
            TileCache::prune();
        })->everyFiveMinutes();

        // Insert scheduled tasks here.
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
