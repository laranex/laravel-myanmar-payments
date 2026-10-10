<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\LaravelMyanmarPayments\Gateways\CyberSource;
use Laranex\LaravelMyanmarPayments\Http\Controllers\FormPaymentController;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;
use Laranex\LaravelMyanmarPayments\MyanmarPayments as MyanmarPaymentsManager;
use Laranex\LaravelMyanmarPayments\MyanmarPaymentsServiceProvider;
use Laranex\PhpMyanmarPayments\Exceptions\ConfigurationException;
use Laranex\PhpMyanmarPayments\Results\FormPayment;

it('merges the package config and lets the host application override it', function () {
    expect(config('myanmar-payments.http.timeout'))->toBe(30)
        ->and(config('myanmar-payments.form_route'))->toBe(['enabled' => true, 'path' => 'myanmar-payments/form', 'middleware' => ['web'], 'ttl_minutes' => 30])
        ->and(config('myanmar-payments.cache_store'))->toBeNull()
        ->and(config('myanmar-payments.kbz_pay'))->toBe(['app_id' => 'kp123', 'app_key' => 'secret-key', 'merchant_code' => '100001']);
});

it('ships no sandbox switch and no defaults for gateway settings', function () {
    $config = require __DIR__.'/../../config/myanmar-payments.php';

    expect(file_get_contents(__DIR__.'/../../config/myanmar-payments.php'))->not->toContain('andbox')
        ->and($config['http']['timeout'])->toBeNull()
        ->and($config['wave_money']['merchant_name'])->toBeNull()
        ->and($config['wave_money']['time_to_live_in_seconds'])->toBeNull()
        ->and($config['yoma_mmqr']['api_version'])->toBeNull()
        ->and($config['form_route'])->toBe(['enabled' => true, 'path' => 'myanmar-payments/form', 'middleware' => ['web'], 'ttl_minutes' => null]);
});

it('requires the HTTP timeout for every gateway that calls an API', function (string $gateway, string $method) {
    config()->set('myanmar-payments.http.timeout', null);
    app()->forgetInstance(MyanmarPaymentsManager::class);
    MyanmarPayments::clearResolvedInstances();

    expect(fn () => MyanmarPayments::{$method}())->toThrow(function (ConfigurationException $exception) use ($gateway) {
        expect($exception->getMessage())->toBe("The {$gateway} configuration is missing [timeout_in_seconds].")
            ->and($exception->gateway)->toBe($gateway)
            ->and($exception->key)->toBe('timeout_in_seconds');
    })->and(MyanmarPayments::cyberSource())->toBeInstanceOf(CyberSource::class);
})->with([
    ['kbz_pay', 'kbzPay'],
    ['wave_money', 'waveMoney'],
    ['aya_pay', 'ayaPay'],
    ['yoma_mmqr', 'yomaMmqr'],
]);

it('rejects an HTTP timeout that is not a whole number greater than 0', function (mixed $timeout) {
    config()->set('myanmar-payments.http.timeout', $timeout);
    app()->forgetInstance(MyanmarPaymentsManager::class);
    MyanmarPayments::clearResolvedInstances();

    MyanmarPayments::kbzPay();
})->with([0, '-5', 'five', '1.5'])->throws(ConfigurationException::class, 'The kbz_pay configuration [timeout_in_seconds] must be a whole number greater than 0.');

it('lets a gateway set its own timeout over the shared one', function () {
    config()->set('myanmar-payments.http.timeout', null);
    config()->set('myanmar-payments.kbz_pay.timeout_in_seconds', '15');
    app()->forgetInstance(MyanmarPaymentsManager::class);
    MyanmarPayments::clearResolvedInstances();

    expect(MyanmarPayments::kbzPay()->config->timeoutSeconds)->toBe(15);
});

it('requires the gateway settings that used to have defaults', function (string $gateway, string $method, string $key) {
    config()->set("myanmar-payments.{$gateway}.{$key}", null);
    app()->forgetInstance(MyanmarPaymentsManager::class);
    MyanmarPayments::clearResolvedInstances();

    MyanmarPayments::{$method}();
})->with([
    ['wave_money', 'waveMoney', 'merchant_name'],
    ['wave_money', 'waveMoney', 'time_to_live_in_seconds'],
    ['yoma_mmqr', 'yomaMmqr', 'api_version'],
])->throws(ConfigurationException::class, 'configuration is missing');

it('registers the facade alias and the manager as singletons', function () {
    expect(class_exists('MyanmarPayments'))->toBeTrue()
        ->and(MyanmarPayments::kbzPay())->toBe(MyanmarPayments::kbzPay())
        ->and(app(MyanmarPaymentsManager::class))->toBe(app(MyanmarPaymentsManager::class))
        ->and(app(FormPaymentUrl::class))->toBe(app(FormPaymentUrl::class));
});

it('publishes the config file under both publish tags', function (string $tag) {
    $source = realpath(__DIR__.'/../../config/myanmar-payments.php');
    $paths = ServiceProvider::pathsToPublish(MyanmarPaymentsServiceProvider::class, $tag);

    expect(array_map('realpath', array_keys($paths)))->toBe([$source])
        ->and(array_values($paths))->toBe([config_path('myanmar-payments.php')]);
})->with(['myanmar-payments', 'myanmar-payments-config']);

it('registers the form route with the configured path, name and middleware', function () {
    $route = Route::getRoutes()->getByName('myanmar-payments.form');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('myanmar-payments/form')
        ->and($route->methods())->toContain('GET')
        ->and($route->middleware())->toBe(['web'])
        ->and($route->getActionName())->toBe(FormPaymentController::class);
});

it('round-trips a form payment through the encrypted auto-submit link', function () {
    $payment = new FormPayment('ORDER_9', 'https://gateway.test/pay', ['amount' => '1000', 'sig' => 'abc'], 'multipart/form-data');

    $attached = app(FormPaymentUrl::class)->attach($payment);
    $resolved = app(FormPaymentUrl::class)->resolve(substr((string) $attached->autoSubmitUrl, strlen('http://localhost/myanmar-payments/form?payload=')));

    expect($attached->autoSubmitUrl)->toStartWith('http://localhost/myanmar-payments/form?payload=')
        ->and($resolved)->not->toBeNull()
        ->and($resolved->orderId)->toBe('ORDER_9')
        ->and($resolved->action)->toBe('https://gateway.test/pay')
        ->and($resolved->fields)->toBe(['amount' => '1000', 'sig' => 'abc'])
        ->and($resolved->enctype)->toBe('multipart/form-data')
        ->and(app(FormPaymentUrl::class)->resolve('not-a-payload'))->toBeNull();
});

it('requires the form link ttl while the form route is enabled', function (mixed $ttl, string $message) {
    config()->set('myanmar-payments.form_route.ttl_minutes', $ttl);
    app()->forgetInstance(FormPaymentUrl::class);

    expect(fn () => app(FormPaymentUrl::class)->attach(new FormPayment('ORDER_9', 'https://gateway.test/pay', [])))
        ->toThrow(function (ConfigurationException $exception) use ($message) {
            expect($exception->getMessage())->toBe($message)
                ->and($exception->gateway)->toBe('form_route')
                ->and($exception->key)->toBe('ttl_minutes');
        });
})->with([
    [null, 'The form_route configuration is missing [ttl_minutes].'],
    [' ', 'The form_route configuration is missing [ttl_minutes].'],
    [0, 'The form_route configuration [ttl_minutes] must be a whole number greater than 0.'],
    ['five', 'The form_route configuration [ttl_minutes] must be a whole number greater than 0.'],
    [1.5, 'The form_route configuration [ttl_minutes] must be a whole number greater than 0.'],
]);

it('honors the configured ttl for form links', function () {
    config()->set('myanmar-payments.form_route.ttl_minutes', '5');
    app()->forgetInstance(FormPaymentUrl::class);

    $url = app(FormPaymentUrl::class)->attach(new FormPayment('ORDER_9', 'https://gateway.test/pay', []))->autoSubmitUrl;

    $this->get((string) $url)->assertOk();
    $this->travel(6)->minutes();
    $this->get((string) $url)->assertStatus(410);
});
