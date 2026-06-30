<?php

use Illuminate\Support\Facades\Http;
use Laranex\LaravelMyanmarPayments\Data\KbzPayPaymentData;
use Laranex\LaravelMyanmarPayments\Data\WaveMoneyPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

it('initiates kbzpay pwa and returns a redirect url', function () {
    Http::fake([
        '*/precreate' => Http::response([
            'Response' => ['code' => '0', 'prepay_id' => 'PREPAY_123'],
        ]),
    ]);

    $result = app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new KbzPayPaymentData(
        orderId: 'ORD-001',
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));

    expect($result->status)->toBe(PaymentStatus::Initiated)
        ->and($result->requiresRedirect())->toBeTrue()
        ->and($result->redirectUrl)->toContain('prepay_id=PREPAY_123')
        ->and($result->redirectUrl)->toContain('sign=');
});

it('initiates kbzpay qr and returns a qr code', function () {
    Http::fake([
        '*/precreate' => Http::response([
            'Response' => ['code' => '0', 'qrCode' => 'QR_DATA_STRING'],
        ]),
    ]);

    $result = app('myanmar-payments')->driver('kbzpay.qr')->initiate(new KbzPayPaymentData(
        orderId: 'ORD-001',
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));

    expect($result->status)->toBe(PaymentStatus::Initiated)
        ->and($result->qrCode)->toBe('QR_DATA_STRING')
        ->and($result->requiresRedirect())->toBeFalse();
});

it('initiates kbzpay app and returns app data', function () {
    Http::fake([
        '*/precreate' => Http::response([
            'Response' => ['code' => '0', 'prepay_id' => 'PREPAY_APP_456'],
        ]),
    ]);

    $result = app('myanmar-payments')->driver('kbzpay.app')->initiate(new KbzPayPaymentData(
        orderId: 'ORD-001',
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));

    expect($result->status)->toBe(PaymentStatus::Initiated)
        ->and($result->appData)->toBeArray()
        ->and($result->appData)->toHaveKeys(['orderInfo', 'sign', 'signType'])
        ->and($result->appData['signType'])->toBe('SHA256');
});

it('returns failed status when kbzpay precreate response code is not 0', function () {
    Http::fake([
        '*/precreate' => Http::response(['Response' => ['code' => '1', 'msg' => 'error']], 200),
    ]);

    $result = app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new KbzPayPaymentData(
        orderId: 'ORD-001',
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->orderId)->toBe('ORD-001')
        ->and($result->raw)->toBe(['Response' => ['code' => '1', 'msg' => 'error']]);
});

it('throws when wrong data class is passed to kbzpay driver', function () {
    app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new WaveMoneyPaymentData(
        orderId: 'ORD-001',
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(PaymentException::class, 'KbzPayDriver expects KbzPayPaymentData');

it('throws validation error for empty orderId', function () {
    Http::fake();

    app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new KbzPayPaymentData(
        orderId: '',
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(PaymentException::class, 'orderId is required');

it('throws validation error for invalid callback url', function () {
    Http::fake();

    app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new KbzPayPaymentData(
        orderId: 'ORD-001',
        amount: 1000,
        callbackUrl: 'not-a-url',
    ));
})->throws(PaymentException::class, 'callbackUrl must be a valid URL');

it('verifies a kbzpay order and returns successful status', function () {
    Http::fake([
        '*/queryorder' => Http::response([
            'Response' => [
                'code' => '0',
                'order_status' => 'SUCCESS',
                'kbz_tran_no' => 'KBZ_TXN_789',
            ],
        ]),
    ]);

    $result = app('myanmar-payments')->driver('kbzpay.pwa')->verify('ORD-001');

    expect($result->status)->toBe(PaymentStatus::Successful);
});

it('handles a valid kbzpay callback', function () {
    $appKey = 'TEST_APP_KEY';
    $payload = [
        'merch_order_id' => 'ORD-001',
        'kbz_tran_no' => 'KBZ_TXN_001',
        'trade_status' => 'PAY_SUCCESS',
        'sign_type' => 'SHA256',
    ];

    $params = $payload;
    unset($params['sign_type']);
    ksort($params);
    $sign = strtoupper(hash('SHA256', http_build_query($params)."&key=$appKey"));
    $payload['sign'] = $sign;

    $result = app('myanmar-payments')->driver('kbzpay.pwa')->handleCallback(['Request' => $payload]);

    expect($result->status)->toBe(PaymentStatus::Successful)
        ->and($result->orderId)->toBe('ORD-001');
});

it('throws on invalid kbzpay callback signature', function () {
    app('myanmar-payments')->driver('kbzpay.pwa')->handleCallback([
        'Request' => [
            'merch_order_id' => 'ORD-001',
            'trade_status' => 'PAY_SUCCESS',
            'sign' => 'INVALID_SIGNATURE',
            'sign_type' => 'SHA256',
        ],
    ]);
})->throws(PaymentException::class, 'signature verification failed');
