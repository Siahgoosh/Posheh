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
        $schedule->command('blog:publish-scheduled')->everyMinute();
        // SEO Growth Engine — automatic-safe jobs only (no auto rewrite/merge)
        $schedule->command('seo:collect-gsc')->dailyAt('03:15');
        $schedule->command('seo:analyze')->weeklyOn(1, '04:00');
        $schedule->command('seo:analyze --weekly-report')->weeklyOn(1, '04:30');
        $schedule->command('cro:bootstrap')->weeklyOn(1, '05:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
