<?php

namespace Laranex\LaravelMyanmarPayments\Tests;

use Laranex\LaravelMyanmarPayments\MyanmarPaymentsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [MyanmarPaymentsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('myanmar-payments.kbz_pay', [
            'base_url' => 'https://api.kbzpay.test/payment/gateway/uat',
            'merchant_name' => 'Test',
            'merchant_code' => 'TEST_MERCHANT',
            'app_id' => 'TEST_APP_ID',
            'app_key' => 'TEST_APP_KEY',
            'pwa' => ['base_redirect_url' => 'https://static.kbzpay.test/pgw/uat/pwa/#'],
        ]);

        $app['config']->set('myanmar-payments.wave_money', [
            'base_url' => 'https://testpayments.wavemoney.test',
            'time_to_live_in_seconds' => 300,
            'merchant_name' => 'Test',
            'merchant_id' => 'TEST_WAVE_MERCHANT',
            'secret_key' => 'TEST_WAVE_SECRET',
        ]);

        $app['config']->set('myanmar-payments.aya_pay', [
            'base_url' => 'https://uat.ayapay.test',
            'app_key' => 'TEST_AYA_APP_KEY',
            'app_secret' => 'TEST_AYA_APP_SECRET',
        ]);

        $app['config']->set('myanmar-payments.cyber_source', [
            'base_url' => 'https://testsecureacceptance.cybersource.test',
            'profile_id' => 'TEST_PROFILE_ID',
            'access_key' => 'TEST_ACCESS_KEY',
            'secret_key' => 'TEST_CS_SECRET_KEY',
        ]);
    }
}
