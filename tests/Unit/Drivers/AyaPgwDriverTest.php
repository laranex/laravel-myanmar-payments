<?php

use Illuminate\Support\Facades\Http;
use Laranex\LaravelMyanmarPayments\Data\AyaPgwPaymentData;
use Laranex\LaravelMyanmarPayments\Data\KbzPayPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

it('initiates aya pgw payment and returns redirect url', function () {
    $result = app('myanmar-payments')->driver('aya_pgw')->initiate(new AyaPgwPaymentData(
        orderId: 'ORD-001',
        amount: 2000,
        channel: 'AYA_PAY',
        method: 'WALLET',
    ));

    expect($result->status)->toBe(PaymentStatus::Initiated)
        ->and($result->requiresRedirect())->toBeTrue()
        ->and($result->redirectUrl)->toContain('/myanmar-payments/form?payload=');
});

it('throws when wrong data class is passed to aya pgw driver', function () {
    app('myanmar-payments')->driver('aya_pgw')->initiate(new KbzPayPaymentData(
        orderId: 'ORD-001',
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(PaymentException::class, 'AyaPgwDriver expects AyaPgwPaymentData');

it('throws validation error for empty orderId', function () {
    app('myanmar-payments')->driver('aya_pgw')->initiate(new AyaPgwPaymentData(
        orderId: '',
        amount: 2000,
        channel: 'AYA_PAY',
        method: 'WALLET',
    ));
})->throws(PaymentException::class, 'orderId is required');

it('throws validation error for empty channel', function () {
    app('myanmar-payments')->driver('aya_pgw')->initiate(new AyaPgwPaymentData(
        orderId: 'ORD-001',
        amount: 2000,
        channel: '',
        method: 'WALLET',
    ));
})->throws(PaymentException::class, 'channel is required');

it('throws validation error when user refs exceed 5', function () {
    app('myanmar-payments')->driver('aya_pgw')->initiate(new AyaPgwPaymentData(
        orderId: 'ORD-001',
        amount: 2000,
        channel: 'AYA_PAY',
        method: 'WALLET',
        userRefs: ['a', 'b', 'c', 'd', 'e', 'f'],
    ));
})->throws(PaymentException::class, 'maximum of 5 user reference fields');

it('verifies aya pgw order and returns successful status', function () {
    Http::fake([
        '*/v1/payment/enquiry' => Http::response([
            'status' => '00',
            'data' => [
                'transactionStatus' => 'SUCCESS',
                'transactionId' => 'AYA_TXN_001',
            ],
        ]),
    ]);

    $result = app('myanmar-payments')->driver('aya_pgw')->verify('ORD-001');

    expect($result->status)->toBe(PaymentStatus::Successful);
});

it('handles a valid aya pgw callback', function () {
    $appSecret = 'TEST_AYA_APP_SECRET';
    $decoded = [
        'transactionStatus' => 'SUCCESS',
        'transactionId' => 'AYA_TXN_002',
        'merchOrderId' => 'ORD-001',
    ];
    $encodedPayload = base64_encode(json_encode($decoded));
    $checkSum = hash_hmac('sha256', implode(':', array_values($decoded)), $appSecret);

    $result = app('myanmar-payments')->driver('aya_pgw')->handleCallback([
        'payload' => $encodedPayload,
        'checkSum' => $checkSum,
    ]);

    expect($result->status)->toBe(PaymentStatus::Successful)
        ->and($result->orderId)->toBe('ORD-001');
});

it('throws on invalid aya pgw callback checksum', function () {
    $decoded = ['transactionStatus' => 'SUCCESS', 'transactionId' => 'AYA_TXN_002', 'merchOrderId' => 'ORD-001'];
    $encodedPayload = base64_encode(json_encode($decoded));

    app('myanmar-payments')->driver('aya_pgw')->handleCallback([
        'payload' => $encodedPayload,
        'checkSum' => 'INVALID_CHECKSUM',
    ]);
})->throws(PaymentException::class, 'checksum verification failed');
