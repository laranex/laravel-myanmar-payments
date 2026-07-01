<?php

use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\MyanmarPayments;
use Laranex\LaravelMyanmarPayments\MyanmarPaymentsFacade;

it('resolves the myanmar-payments manager from the container', function () {
    expect(app('myanmar-payments'))->toBeInstanceOf(MyanmarPayments::class);
});

it('resolves each registered driver as a payment driver', function (string $driver) {
    expect(app('myanmar-payments')->driver($driver))->toBeInstanceOf(PaymentDriver::class);
})->with([
    'kbzpay.pwa',
    'kbzpay.qr',
    'kbzpay.app',
    'wave_money',
    'aya_pgw',
    'cyber_source',
]);

it('resolves the facade correctly', function () {
    expect(MyanmarPaymentsFacade::getFacadeRoot())->toBeInstanceOf(MyanmarPayments::class);
});

it('throws when no driver is specified', function () {
    app('myanmar-payments')->driver();
})->throws(InvalidArgumentException::class, 'No default Myanmar payment driver configured');

it('flow helpers report the correct type', function () {
    $redirect = new RequestPaymentResult(flow: PaymentFlow::RedirectBased, value: 'https://pay.example.com');
    $form = new RequestPaymentResult(flow: PaymentFlow::FormBased, value: ['url' => 'https://pay.example.com', 'data' => []]);
    $qr = new RequestPaymentResult(flow: PaymentFlow::QrBased, value: 'QR_STRING');
    $app = new RequestPaymentResult(flow: PaymentFlow::AppBased, value: []);
    $none = new RequestPaymentResult;

    expect($redirect->isRedirectBased())->toBeTrue()
        ->and($form->isFormBased())->toBeTrue()
        ->and($qr->isQrBased())->toBeTrue()
        ->and($app->isAppBased())->toBeTrue()
        ->and($none->isRedirectBased())->toBeFalse();
});

it('each driver reports its payment flow', function (string $driver, PaymentFlow $expectedFlow) {
    expect(app('myanmar-payments')->driver($driver)->getPaymentFlow())->toBe($expectedFlow);
})->with([
    ['kbzpay.pwa', PaymentFlow::RedirectBased],
    ['kbzpay.qr', PaymentFlow::QrBased],
    ['kbzpay.app', PaymentFlow::AppBased],
    ['wave_money', PaymentFlow::RedirectBased],
    ['aya_pgw', PaymentFlow::FormBased],
    ['cyber_source', PaymentFlow::FormBased],
]);
