<?php

use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;
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

it('payment result has correct helper methods', function () {
    $initiated = new RequestPaymentResult(status: PaymentStatus::Initiated, redirectUrl: 'https://pay.example.com');
    $successful = new RequestPaymentResult(status: PaymentStatus::Successful);
    $failed = new RequestPaymentResult(status: PaymentStatus::Failed);
    $pending = new RequestPaymentResult(status: PaymentStatus::Pending);

    expect($initiated->isInitiated())->toBeTrue()
        ->and($initiated->requiresRedirect())->toBeTrue()
        ->and($successful->isSuccessful())->toBeTrue()
        ->and($failed->isFailed())->toBeTrue()
        ->and($pending->isPending())->toBeTrue();
});

it('redirect result reports requiresRedirect correctly', function () {
    $withRedirect = new RequestPaymentResult(status: PaymentStatus::Initiated, redirectUrl: 'https://pay.example.com/redirect');
    $withoutRedirect = new RequestPaymentResult(status: PaymentStatus::Initiated);

    expect($withRedirect->requiresRedirect())->toBeTrue()
        ->and($withoutRedirect->requiresRedirect())->toBeFalse();
});
