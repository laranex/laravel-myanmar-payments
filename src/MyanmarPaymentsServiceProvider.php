<?php

namespace Laranex\LaravelMyanmarPayments;

use Illuminate\Support\ServiceProvider;
use Laranex\LaravelMyanmarPayments\Drivers\AyaPgwDriver;
use Laranex\LaravelMyanmarPayments\Drivers\CyberSourceDriver;
use Laranex\LaravelMyanmarPayments\Drivers\KbzPayDriver;
use Laranex\LaravelMyanmarPayments\Drivers\WaveMoneyDriver;
use Laranex\LaravelMyanmarPayments\Enums\KbzPayTradeType;

class MyanmarPaymentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/myanmar-payments.php', 'myanmar-payments');

        $this->app->singleton('myanmar-payments', function ($app) {
            $manager = new MyanmarPayments($app);

            $kbzConfig = $app['config']->get('myanmar-payments.kbz_pay', []);
            $manager->extend('kbzpay.pwa', fn () => new KbzPayDriver(KbzPayTradeType::Pwa, $kbzConfig));
            $manager->extend('kbzpay.qr', fn () => new KbzPayDriver(KbzPayTradeType::Qr, $kbzConfig));
            $manager->extend('kbzpay.app', fn () => new KbzPayDriver(KbzPayTradeType::App, $kbzConfig));

            $manager->extend('wave_money', fn () => new WaveMoneyDriver($app['config']->get('myanmar-payments.wave_money', [])));

            $manager->extend('aya_pgw', fn () => new AyaPgwDriver($app['config']->get('myanmar-payments.aya_pgw', [])));
            $manager->extend('cyber_source', fn () => new CyberSourceDriver($app['config']->get('myanmar-payments.cyber_source', [])));

            return $manager;
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/myanmar-payments.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/myanmar-payments.php' => config_path('myanmar-payments.php'),
            ], 'myanmar-payments-config');
        }
    }
}
