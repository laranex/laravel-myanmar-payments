<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\LaravelMyanmarPayments\Http\Controllers\FormPaymentController;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;
use Laranex\LaravelMyanmarPayments\MyanmarPayments as MyanmarPaymentsManager;
use Laranex\LaravelMyanmarPayments\MyanmarPaymentsServiceProvider;
use Laranex\PhpMyanmarPayments\Results\FormPayment;

it('merges the package config and lets the host application override it', function () {
    expect(config('myanmar-payments.http.timeout'))->toBe(30)
        ->and(config('myanmar-payments.form_route'))->toBe(['enabled' => true, 'path' => 'myanmar-payments/form', 'middleware' => ['web'], 'ttl_minutes' => 30])
        ->and(config('myanmar-payments.cache_store'))->toBeNull()
        ->and(config('myanmar-payments.kbz_pay'))->toBe(['app_id' => 'kp123', 'app_key' => 'secret-key', 'merchant_code' => '100001']);
});

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

it('honors the configured ttl for form links', function () {
    config()->set('myanmar-payments.form_route.ttl_minutes', 5);
    app()->forgetInstance(FormPaymentUrl::class);

    $url = app(FormPaymentUrl::class)->attach(new FormPayment('ORDER_9', 'https://gateway.test/pay', []))->autoSubmitUrl;

    $this->get((string) $url)->assertOk();
    $this->travel(6)->minutes();
    $this->get((string) $url)->assertStatus(410);
});
