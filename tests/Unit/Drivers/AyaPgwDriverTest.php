<?php

use Laranex\LaravelMyanmarPayments\Data\Request\AyaPgwRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\Request\KbzPayRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\HandlePaymentStatus;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\Exceptions\SignatureVerificationException;

it('initiates aya pgw payment and returns redirect url', function () {
    $result = app('myanmar-payments')->driver('aya_pgw')->initiate(new AyaPgwRequestPaymentData(
        orderId: fake()->uuid(),
        amount: 2000,
        channel: 'AYA_PAY',
        method: 'WALLET',
    ));

    expect($result->flow)->toBe(PaymentFlow::FormBased)
        ->and($result->value)->toContain('/myanmar-payments/form?payload=')
        ->and($result->form)->toHaveKeys(['url', 'data']);
});

it('throws when wrong data class is passed to aya pgw driver', function () {
    app('myanmar-payments')->driver('aya_pgw')->initiate(new KbzPayRequestPaymentData(
        orderId: fake()->uuid(),
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(InvalidArgumentException::class, 'expects');

it('throws validation error for empty orderId', function () {
    app('myanmar-payments')->driver('aya_pgw')->initiate(new AyaPgwRequestPaymentData(
        orderId: '',
        amount: 2000,
        channel: 'AYA_PAY',
        method: 'WALLET',
    ));
})->throws(InvalidArgumentException::class, 'orderId is required');

it('throws validation error for empty channel', function () {
    app('myanmar-payments')->driver('aya_pgw')->initiate(new AyaPgwRequestPaymentData(
        orderId: fake()->uuid(),
        amount: 2000,
        channel: '',
        method: 'WALLET',
    ));
})->throws(InvalidArgumentException::class, 'channel is required');

it('throws validation error when user refs exceed 5', function () {
    app('myanmar-payments')->driver('aya_pgw')->initiate(new AyaPgwRequestPaymentData(
        orderId: fake()->uuid(),
        amount: 2000,
        channel: 'AYA_PAY',
        method: 'WALLET',
        userRefs: ['a', 'b', 'c', 'd', 'e', 'f'],
    ));
})->throws(InvalidArgumentException::class, 'maximum of 5 user reference fields');

it('handles a valid aya pgw callback', function () {
    $orderId = fake()->uuid();
    $appSecret = 'TEST_AYA_APP_SECRET';
    $decoded = [
        'transactionStatus' => 'SUCCESS',
        'transactionId' => 'AYA_TXN_002',
        'merchOrderId' => $orderId,
    ];
    $encodedPayload = base64_encode(json_encode($decoded));
    $checkSum = hash_hmac('sha256', implode(':', array_values($decoded)), $appSecret);

    $result = app('myanmar-payments')->driver('aya_pgw')->handleCallback([
        'payload' => $encodedPayload,
        'checkSum' => $checkSum,
    ]);

    expect($result->status)->toBe(HandlePaymentStatus::Successful)
        ->and($result->transactionId)->toBe('AYA_TXN_002');
});

it('throws SignatureVerificationException on invalid aya pgw callback checksum', function () {
    $decoded = ['transactionStatus' => 'SUCCESS', 'transactionId' => 'AYA_TXN_002', 'merchOrderId' => fake()->uuid()];
    $encodedPayload = base64_encode(json_encode($decoded));

    app('myanmar-payments')->driver('aya_pgw')->handleCallback([
        'payload' => $encodedPayload,
        'checkSum' => 'INVALID_CHECKSUM',
    ]);
})->throws(SignatureVerificationException::class, 'checksum verification failed');
