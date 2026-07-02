<?php

use Laranex\LaravelMyanmarPayments\Data\Request\AyaPayRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\Request\KbzPayRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\HandlePaymentStatus;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\Exceptions\SignatureVerificationException;

it('initiates aya pay payment and returns redirect url', function () {
    $result = app('myanmar-payments')->driver('aya_pay')->initiate(new AyaPayRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 2000,
        method: 'WALLET',
    ));

    expect($result->flow)->toBe(PaymentFlow::FormBased)
        ->and($result->value)->toContain('/myanmar-payments/form?payload=')
        ->and($result->originalValue)->toHaveKeys(['url', 'data']);
});

it('throws when wrong data class is passed to aya pay driver', function () {
    app('myanmar-payments')->driver('aya_pay')->initiate(new KbzPayRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(InvalidArgumentException::class, 'expects');

it('throws validation error for empty transactionId', function () {
    app('myanmar-payments')->driver('aya_pay')->initiate(new AyaPayRequestPaymentData(
        transactionId: '',
        amount: 2000,
        method: 'WALLET',
    ));
})->throws(InvalidArgumentException::class, 'transactionId is required');

it('throws validation error when user refs exceed 5', function () {
    app('myanmar-payments')->driver('aya_pay')->initiate(new AyaPayRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 2000,
        method: 'WALLET',
        userRefs: ['a', 'b', 'c', 'd', 'e', 'f'],
    ));
})->throws(InvalidArgumentException::class, 'maximum of 5 user reference fields');

it('handles a valid aya pay callback', function () {
    $orderId = fake()->uuid();
    $appSecret = 'TEST_AYA_APP_SECRET';
    $decoded = [
        'transactionStatus' => 'SUCCESS',
        'transactionId' => 'AYA_TXN_002',
        'merchOrderId' => $orderId,
    ];
    $encodedPayload = base64_encode(json_encode($decoded));
    $checkSum = hash_hmac('sha256', implode(':', array_values($decoded)), $appSecret);

    $result = app('myanmar-payments')->driver('aya_pay')->handleCallback([
        'payload' => $encodedPayload,
        'checkSum' => $checkSum,
    ]);

    expect($result->status)->toBe(HandlePaymentStatus::Successful)
        ->and($result->transactionId)->toBe('AYA_TXN_002');
});

it('throws SignatureVerificationException on invalid aya pay callback checksum', function () {
    $decoded = ['transactionStatus' => 'SUCCESS', 'transactionId' => 'AYA_TXN_002', 'merchOrderId' => fake()->uuid()];
    $encodedPayload = base64_encode(json_encode($decoded));

    app('myanmar-payments')->driver('aya_pay')->handleCallback([
        'payload' => $encodedPayload,
        'checkSum' => 'INVALID_CHECKSUM',
    ]);
})->throws(SignatureVerificationException::class, 'checksum verification failed');
