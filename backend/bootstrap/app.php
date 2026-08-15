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
        $schedule->command('notifications:purge-old')->hourly();
        $schedule->command('content-planner:process-reminders')->everyMinute();
        $schedule->command('blog:publish-scheduled')->everyMinute();
        // SEO Growth Engine — automatic-safe jobs only (no auto rewrite/merge)
        $schedule->command('seo:collect-gsc')->dailyAt('03:15');
        $schedule->command('seo:analyze')->weeklyOn(1, '04:00');
        $schedule->command('seo:analyze --weekly-report')->weeklyOn(1, '04:30');
        $schedule->command('cro:bootstrap')->weeklyOn(1, '05:00');
        // Phase 8 — technical SEO audits (no fake metrics)
        $schedule->command('seo:technical-audit --scope=daily')->dailyAt('02:30');
        $schedule->command('seo:technical-audit --scope=weekly --scan-links')->weeklyOn(2, '02:45');
        $schedule->command('seo:local-audit')->dailyAt('02:50');
        $schedule->command('seo:local-audit --opportunities')->weeklyOn(3, '03:00');
        $schedule->command('content:process-ai-jobs --limit=20')->everyFiveMinutes();
        $schedule->command('content:ops-audit --process=5')->dailyAt('03:10');
        $schedule->command('content:ops-audit --weekly')->weeklyOn(1, '05:30');
        $schedule->command('content:ops-audit --monthly')->monthlyOn(1, '06:00');
        $schedule->command('blog:image-process --limit=10')->everyFiveMinutes();
        $schedule->command('blog:image-audit')->weeklyOn(4, '03:40');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
