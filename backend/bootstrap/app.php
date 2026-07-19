<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('properties:expire')->dailyAt('00:30');
        $schedule->command('properties:remind-expiry')->dailyAt('09:30');
        $schedule->command('subscriptions:remind')->dailyAt('09:00');
        $schedule->command('visits:remind')->hourly();
        $schedule->command('backup:database')->cron('0 2 */3 * *');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'plan.feature' => \App\Http\Middleware\EnsurePlanFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
