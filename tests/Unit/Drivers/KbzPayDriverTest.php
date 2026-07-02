<?php

use Illuminate\Support\Facades\Http;
use Laranex\LaravelMyanmarPayments\Data\Request\KbzPayRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\Request\WaveMoneyRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\Exceptions\SignatureVerificationException;

it('initiates kbzpay pwa and returns a redirect url', function () {
    Http::fake([
        '*/precreate' => Http::response([
            'Response' => ['code' => '0', 'prepay_id' => 'PREPAY_123'],
        ]),
    ]);

    $result = app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new KbzPayRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));

    expect($result->flow)->toBe(PaymentFlow::RedirectBased)
        ->and($result->value)->toContain('prepay_id=PREPAY_123')
        ->and($result->value)->toContain('sign=');
});

it('initiates kbzpay qr and returns a qr code', function () {
    Http::fake([
        '*/precreate' => Http::response([
            'Response' => ['code' => '0', 'qrCode' => 'QR_DATA_STRING'],
        ]),
    ]);

    $result = app('myanmar-payments')->driver('kbzpay.qr')->initiate(new KbzPayRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));

    expect($result->flow)->toBe(PaymentFlow::QrBased)
        ->and($result->value)->toBe('QR_DATA_STRING');
});

it('initiates kbzpay app and returns app data', function () {
    Http::fake([
        '*/precreate' => Http::response([
            'Response' => ['code' => '0', 'prepay_id' => 'PREPAY_APP_456'],
        ]),
    ]);

    $result = app('myanmar-payments')->driver('kbzpay.app')->initiate(new KbzPayRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));

    expect($result->flow)->toBe(PaymentFlow::AppBased)
        ->and($result->value)->toBeArray()
        ->and($result->value)->toHaveKeys(['orderInfo', 'sign', 'signType'])
        ->and($result->value['signType'])->toBe('SHA256');
});

it('returns result with raw when kbzpay precreate response code is not 0', function () {
    $transactionId = fake()->uuid();

    Http::fake([
        '*/precreate' => Http::response(['Response' => ['code' => '1', 'msg' => 'error']], 200),
    ]);

    $result = app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new KbzPayRequestPaymentData(
        transactionId: $transactionId,
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));

    expect($result->transactionId)->toBe($transactionId)
        ->and($result->raw)->toBe(['Response' => ['code' => '1', 'msg' => 'error']]);
});

it('throws when wrong data class is passed to kbzpay driver', function () {
    app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new WaveMoneyRequestPaymentData(
        orderId: fake()->uuid(),
        backendResultUrl: 'https://example.com/callback',
        frontendResultUrl: 'https://example.com/success',
        description: 'Test payment',
        items: [['name' => 'Product A', 'amount' => 5000]],
    ));
})->throws(InvalidArgumentException::class, 'expects');

it('throws validation error for empty transactionId', function () {
    Http::fake();

    app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new KbzPayRequestPaymentData(
        transactionId: '',
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(InvalidArgumentException::class);

it('throws validation error for invalid callback url', function () {
    Http::fake();

    app('myanmar-payments')->driver('kbzpay.pwa')->initiate(new KbzPayRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 1000,
        callbackUrl: 'not-a-url',
    ));
})->throws(InvalidArgumentException::class);

it('handles a valid kbzpay callback', function () {
    $orderId = fake()->uuid();
    $appKey = 'TEST_APP_KEY';
    $payload = [
        'merch_order_id' => $orderId,
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

    expect($result->successful)->toBeTrue()
        ->and($result->transactionId)->toBe('KBZ_TXN_001');
});

it('throws SignatureVerificationException on invalid kbzpay callback signature', function () {
    app('myanmar-payments')->driver('kbzpay.pwa')->handleCallback([
        'Request' => [
            'merch_order_id' => fake()->uuid(),
            'trade_status' => 'PAY_SUCCESS',
            'sign' => 'INVALID_SIGNATURE',
            'sign_type' => 'SHA256',
        ],
    ]);
})->throws(SignatureVerificationException::class, 'signature verification failed');
