<?php

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Laranex\LaravelMyanmarPayments\Data\KbzPayPaymentData;
use Laranex\LaravelMyanmarPayments\Data\WaveMoneyPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

it('initiates wave money payment and returns redirect url', function () {
    Http::fake([
        '*/payment' => Http::response(['transaction_id' => 'WAVE_TXN_123']),
    ]);

    $result = app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyPaymentData(
        orderId: 'ORD-001',
        callbackUrl: 'https://example.com/callback',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));

    expect($result->status)->toBe(PaymentStatus::Initiated)
        ->and($result->requiresRedirect())->toBeTrue()
        ->and($result->redirectUrl)->toContain('transaction_id=WAVE_TXN_123');
});

it('throws when wrong data class is passed to wave money driver', function () {
    app('myanmar-payments')->driver('wave_money')->initiate(new KbzPayPaymentData(
        orderId: 'ORD-001',
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(PaymentException::class, 'WaveMoneyDriver expects WaveMoneyPaymentData');

it('throws validation error when items are empty', function () {
    Http::fake();

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyPaymentData(
        orderId: 'ORD-001',
        callbackUrl: 'https://example.com/callback',
        items: [],
    ));
})->throws(PaymentException::class, 'at least one item is required');

it('throws validation error for invalid item structure', function () {
    Http::fake();

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyPaymentData(
        orderId: 'ORD-001',
        callbackUrl: 'https://example.com/callback',
        items: [['title' => 'Bad', 'price' => 5000]],
    ));
})->throws(PaymentException::class, 'each item must have');

it('throws validation error for invalid callback url', function () {
    Http::fake();

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyPaymentData(
        orderId: 'ORD-001',
        callbackUrl: 'not-a-url',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));
})->throws(PaymentException::class, 'callbackUrl must be a valid URL');

it('throws when wave money request fails', function () {
    Http::fake([
        '*/payment' => Http::response([], 500),
    ]);

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyPaymentData(
        orderId: 'ORD-001',
        callbackUrl: 'https://example.com/callback',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));
})->throws(RequestException::class);

it('handles a valid wave money callback', function () {
    $secretKey = 'TEST_WAVE_SECRET';
    $fields = [
        'PAYMENT_CONFIRMED', '300', 'TEST_WAVE_MERCHANT', 'ORD-001', '5000',
        'https://example.com/callback', 'REF-001', '09123456789',
        'WAVE_TXN_123', 'PAY_REQ_001', '1234567890',
    ];
    $hashValue = hash_hmac('sha256', implode('', $fields), $secretKey);

    $result = app('myanmar-payments')->driver('wave_money')->handleCallback([
        'status' => 'PAYMENT_CONFIRMED',
        'timeToLiveSeconds' => '300',
        'merchantId' => 'TEST_WAVE_MERCHANT',
        'orderId' => 'ORD-001',
        'amount' => '5000',
        'backendResultUrl' => 'https://example.com/callback',
        'merchantReferenceId' => 'REF-001',
        'initiatorMsisdn' => '09123456789',
        'transactionId' => 'WAVE_TXN_123',
        'paymentRequestId' => 'PAY_REQ_001',
        'requestTime' => '1234567890',
        'hashValue' => $hashValue,
    ]);

    expect($result->status)->toBe(PaymentStatus::Successful)
        ->and($result->orderId)->toBe('ORD-001');
});

it('throws on invalid wave money callback signature', function () {
    app('myanmar-payments')->driver('wave_money')->handleCallback([
        'status' => 'PAYMENT_CONFIRMED',
        'hashValue' => 'INVALID_HASH',
    ]);
})->throws(PaymentException::class, 'signature verification failed');

it('returns failed status for non-confirmed wave money callback', function () {
    $secretKey = 'TEST_WAVE_SECRET';
    $fields = ['PAYMENT_FAILED', '300', 'TEST_WAVE_MERCHANT', 'ORD-001', '5000', 'https://example.com/callback', 'REF-001', '09123456789', 'TXN', 'PAY_REQ', '1234'];
    $hashValue = hash_hmac('sha256', implode('', $fields), $secretKey);

    $result = app('myanmar-payments')->driver('wave_money')->handleCallback([
        'status' => 'PAYMENT_FAILED',
        'timeToLiveSeconds' => '300',
        'merchantId' => 'TEST_WAVE_MERCHANT',
        'orderId' => 'ORD-001',
        'amount' => '5000',
        'backendResultUrl' => 'https://example.com/callback',
        'merchantReferenceId' => 'REF-001',
        'initiatorMsisdn' => '09123456789',
        'transactionId' => 'TXN',
        'paymentRequestId' => 'PAY_REQ',
        'requestTime' => '1234',
        'hashValue' => $hashValue,
    ]);

    expect($result->status)->toBe(PaymentStatus::Failed);
});

it('throws when verify is called', function () {
    app('myanmar-payments')->driver('wave_money')->verify('ORD-001');
})->throws(PaymentException::class);
