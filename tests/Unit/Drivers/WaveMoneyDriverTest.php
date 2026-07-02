<?php

use Illuminate\Support\Facades\Http;
use Laranex\LaravelMyanmarPayments\Data\Request\KbzPayRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\Request\WaveMoneyRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\Exceptions\ApiException;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;
use Laranex\LaravelMyanmarPayments\Exceptions\SignatureVerificationException;

it('initiates wave money payment and returns redirect url', function () {
    Http::fake([
        '*/payment' => Http::response(['message' => 'success', 'transaction_id' => 'WAVE_TXN_123']),
    ]);

    $result = app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyRequestPaymentData(
        transactionId: fake()->uuid(),
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'https://example.com/success',
        description: 'Test payment',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));

    expect($result->flow)->toBe(PaymentFlow::RedirectBased)
        ->and($result->value)->toContain('transaction_id=WAVE_TXN_123');
});

it('throws ApiException when wave money returns a non-success message', function () {
    Http::fake([
        '*/payment' => Http::response(['message' => 'invalid hash']),
    ]);

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyRequestPaymentData(
        transactionId: fake()->uuid(),
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'https://example.com/success',
        description: 'Test payment',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));
})->throws(ApiException::class, 'initiation failed.');

it('throws ApiException when wave money response is missing transaction_id', function () {
    Http::fake([
        '*/payment' => Http::response(['message' => 'success']),
    ]);

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyRequestPaymentData(
        transactionId: fake()->uuid(),
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'https://example.com/success',
        description: 'Test payment',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));
})->throws(ApiException::class, 'initiation failed.');

it('throws when wrong data class is passed to wave money driver', function () {
    app('myanmar-payments')->driver('wave_money')->initiate(new KbzPayRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(InvalidArgumentException::class, 'expects');

it('throws validation error when items are empty', function () {
    Http::fake();

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyRequestPaymentData(
        transactionId: fake()->uuid(),
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'https://example.com/success',
        description: 'Test payment',
        items: [],
    ));
})->throws(InvalidArgumentException::class, 'at least one item is required');

it('throws validation error for invalid item structure', function () {
    Http::fake();

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyRequestPaymentData(
        transactionId: fake()->uuid(),
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'https://example.com/success',
        description: 'Test payment',
        items: [['title' => 'Bad', 'price' => 5000]],
    ));
})->throws(InvalidArgumentException::class, 'is invalid, must be');

it('throws validation error for invalid callback url', function () {
    Http::fake();

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyRequestPaymentData(
        transactionId: fake()->uuid(),
        callbackUrl: 'not-a-url',
        frontendUrl: 'https://example.com/success',
        description: 'Test payment',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));
})->throws(InvalidArgumentException::class, 'callbackUrl must be a valid URL');

it('throws validation error for invalid frontend url', function () {
    Http::fake();

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyRequestPaymentData(
        transactionId: fake()->uuid(),
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'not-a-url',
        description: 'Test payment',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));
})->throws(InvalidArgumentException::class, 'frontendUrl must be a valid URL');

it('throws validation error for empty description', function () {
    Http::fake();

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyRequestPaymentData(
        transactionId: fake()->uuid(),
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'https://example.com/success',
        description: '',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));
})->throws(InvalidArgumentException::class, 'description is required');

it('throws ApiException when wave money request fails with http error', function () {
    Http::fake([
        '*/payment' => Http::response([], 500),
    ]);

    app('myanmar-payments')->driver('wave_money')->initiate(new WaveMoneyRequestPaymentData(
        transactionId: fake()->uuid(),
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'https://example.com/success',
        description: 'Test payment',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));
})->throws(ApiException::class, 'initiation failed.');

it('handles a valid wave money callback', function () {
    $orderId = fake()->uuid();
    $secretKey = 'TEST_WAVE_SECRET';
    $fields = [
        'PAYMENT_CONFIRMED', '300', 'TEST_WAVE_MERCHANT', $orderId, '5000',
        'https://example.com/callback', 'REF-001', '09123456789',
        'WAVE_TXN_123', 'PAY_REQ_001', '1234567890',
    ];
    $hashValue = hash_hmac('sha256', implode('', $fields), $secretKey);

    $result = app('myanmar-payments')->driver('wave_money')->handleCallback([
        'status' => 'PAYMENT_CONFIRMED',
        'timeToLiveSeconds' => '300',
        'merchantId' => 'TEST_WAVE_MERCHANT',
        'orderId' => $orderId,
        'amount' => '5000',
        'backendResultUrl' => 'https://example.com/callback',
        'merchantReferenceId' => 'REF-001',
        'initiatorMsisdn' => '09123456789',
        'transactionId' => 'WAVE_TXN_123',
        'paymentRequestId' => 'PAY_REQ_001',
        'requestTime' => '1234567890',
        'hashValue' => $hashValue,
    ]);

    expect($result->successful)->toBeTrue()
        ->and($result->transactionId)->toBe('WAVE_TXN_123');
});

it('throws SignatureVerificationException on invalid wave money callback signature', function () {
    app('myanmar-payments')->driver('wave_money')->handleCallback([
        'status' => 'PAYMENT_CONFIRMED',
        'hashValue' => 'INVALID_HASH',
    ]);
})->throws(SignatureVerificationException::class, 'Signature Verification Failed');

it('returns failed status for non-confirmed wave money callback', function () {
    $orderId = fake()->uuid();
    $secretKey = 'TEST_WAVE_SECRET';
    $fields = ['PAYMENT_FAILED', '300', 'TEST_WAVE_MERCHANT', $orderId, '5000', 'https://example.com/callback', 'REF-001', '09123456789', 'TXN', 'PAY_REQ', '1234'];
    $hashValue = hash_hmac('sha256', implode('', $fields), $secretKey);

    $result = app('myanmar-payments')->driver('wave_money')->handleCallback([
        'status' => 'PAYMENT_FAILED',
        'timeToLiveSeconds' => '300',
        'merchantId' => 'TEST_WAVE_MERCHANT',
        'orderId' => $orderId,
        'amount' => '5000',
        'backendResultUrl' => 'https://example.com/callback',
        'merchantReferenceId' => 'REF-001',
        'initiatorMsisdn' => '09123456789',
        'transactionId' => 'TXN',
        'paymentRequestId' => 'PAY_REQ',
        'requestTime' => '1234',
        'hashValue' => $hashValue,
    ]);

    expect($result->successful)->toBeFalse();
});

it('returns failed status for timed out wave money callback', function () {
    $orderId = fake()->uuid();
    $secretKey = 'TEST_WAVE_SECRET';
    $status = 'SCHEDULER_TRANSACTION_TIMED_OUT';
    $fields = [$status, '300', 'TEST_WAVE_MERCHANT', $orderId, '5000', 'https://example.com/callback', $orderId, 'null', 'null', 'PAY_REQ', '1234'];
    $hashValue = hash_hmac('sha256', implode('', $fields), $secretKey);

    $result = app('myanmar-payments')->driver('wave_money')->handleCallback([
        'status' => $status,
        'timeToLiveSeconds' => '300',
        'merchantId' => 'TEST_WAVE_MERCHANT',
        'orderId' => $orderId,
        'amount' => '5000',
        'backendResultUrl' => 'https://example.com/callback',
        'merchantReferenceId' => $orderId,
        'initiatorMsisdn' => null,
        'transactionId' => null,
        'paymentRequestId' => 'PAY_REQ',
        'requestTime' => '1234',
        'hashValue' => $hashValue,
    ]);

    expect($result->successful)->toBeFalse();
});

it('throws on unknown wave money callback status', function () {
    $orderId = fake()->uuid();
    $secretKey = 'TEST_WAVE_SECRET';
    $status = 'SOME_UNKNOWN_STATUS';
    $fields = [$status, '300', 'TEST_WAVE_MERCHANT', $orderId, '5000', 'https://example.com/callback', $orderId, 'null', 'null', 'PAY_REQ', '1234'];
    $hashValue = hash_hmac('sha256', implode('', $fields), $secretKey);

    app('myanmar-payments')->driver('wave_money')->handleCallback([
        'status' => $status,
        'timeToLiveSeconds' => '300',
        'merchantId' => 'TEST_WAVE_MERCHANT',
        'orderId' => $orderId,
        'amount' => '5000',
        'backendResultUrl' => 'https://example.com/callback',
        'merchantReferenceId' => $orderId,
        'initiatorMsisdn' => null,
        'transactionId' => null,
        'paymentRequestId' => 'PAY_REQ',
        'requestTime' => '1234',
        'hashValue' => $hashValue,
    ]);
})->throws(PaymentException::class, 'unknown status: SOME_UNKNOWN_STATUS');
