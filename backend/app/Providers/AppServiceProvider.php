<?php

namespace App\Providers;

use App\Contracts\SmsProviderInterface;
use App\Services\Sms\IpPanelSmsProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProviderInterface::class, IpPanelSmsProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
