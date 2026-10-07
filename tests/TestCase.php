<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Tests;

use Laranex\LaravelMyanmarPayments\MyanmarPaymentsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MyanmarPaymentsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('cache.default', 'array');
        $app['config']->set('myanmar-payments.kbz_pay', ['app_id' => 'kp123', 'app_key' => 'secret-key', 'merchant_code' => '100001']);
        $app['config']->set('myanmar-payments.wave_money', ['merchant_id' => 'wave', 'secret_key' => 'wave-secret', 'merchant_name' => 'Shop']);
        $app['config']->set('myanmar-payments.aya_pay', ['app_key' => 'app-key', 'app_secret' => 'aya-secret']);
        $app['config']->set('myanmar-payments.yoma_mmqr', ['merchant_id' => 'M001', 'client_id' => 'client', 'client_secret' => 'secret', 'webhook_hashkey' => 'hash-key']);
        $app['config']->set('myanmar-payments.cyber_source', ['profile_id' => 'profile', 'access_key' => 'access', 'secret_key' => 'cs-secret']);
    }
}
