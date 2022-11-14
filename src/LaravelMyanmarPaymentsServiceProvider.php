<?php

namespace Laranex\LaravelMyanmarPayments;

use Illuminate\Support\ServiceProvider;

class LaravelMyanmarPaymentsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('laravel-myanmar-payments.php'),
            ], 'laravel-myanmar-payments');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'laravel-myanmar-payments');

        $this->app->singleton('laravel-myanmar-payments', function () {
            return new LaravelMyanmarPayments;
        });
    }
}
