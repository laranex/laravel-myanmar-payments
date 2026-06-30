<?php

use Laranex\LaravelMyanmarPayments\Data\CyberSourcePaymentData;
use Laranex\LaravelMyanmarPayments\Data\KbzPayPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

it('initiates cybersource payment and returns redirect url', function () {
    $result = app('myanmar-payments')->driver('cyber_source')->initiate(new CyberSourcePaymentData(
        orderId: 'ORD-001',
        amount: 5000,
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'https://example.com/success',
    ));

    expect($result->status)->toBe(PaymentStatus::Initiated)
        ->and($result->requiresRedirect())->toBeTrue()
        ->and($result->redirectUrl)->toContain('/myanmar-payments/form?payload=');
});

it('throws when wrong data class is passed to cybersource driver', function () {
    app('myanmar-payments')->driver('cyber_source')->initiate(new KbzPayPaymentData(
        orderId: 'ORD-001',
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(PaymentException::class, 'CyberSourceDriver expects CyberSourcePaymentData');

it('throws validation error for invalid callback url', function () {
    app('myanmar-payments')->driver('cyber_source')->initiate(new CyberSourcePaymentData(
        orderId: 'ORD-001',
        amount: 5000,
        callbackUrl: 'not-a-url',
    ));
})->throws(PaymentException::class, 'callbackUrl must be a valid URL');

it('handles a successful cybersource callback', function () {
    $secretKey = 'TEST_CS_SECRET_KEY';
    $signedFieldNames = 'decision,req_reference_number,transaction_id,signed_field_names';
    $fields = [
        'decision' => 'ACCEPT',
        'req_reference_number' => 'ORD-001',
        'transaction_id' => 'CS_TXN_001',
        'signed_field_names' => $signedFieldNames,
    ];
    $parts = array_map(fn ($k) => "$k={$fields[$k]}", explode(',', $signedFieldNames));
    $signature = base64_encode(hash_hmac('sha256', implode(',', $parts), $secretKey, true));

    $result = app('myanmar-payments')->driver('cyber_source')->handleCallback(
        array_merge($fields, ['signature' => $signature])
    );

    expect($result->status)->toBe(PaymentStatus::Successful)
        ->and($result->orderId)->toBe('ORD-001');
});

it('throws on invalid cybersource callback signature', function () {
    app('myanmar-payments')->driver('cyber_source')->handleCallback([
        'decision' => 'ACCEPT',
        'signed_field_names' => 'decision',
        'signature' => 'INVALID_SIGNATURE',
    ]);
})->throws(PaymentException::class, 'signature verification failed');

it('handles a declined cybersource callback', function () {
    $secretKey = 'TEST_CS_SECRET_KEY';
    $signedFieldNames = 'decision,signed_field_names';
    $fields = ['decision' => 'DECLINE', 'signed_field_names' => $signedFieldNames];
    $parts = array_map(fn ($k) => "$k={$fields[$k]}", explode(',', $signedFieldNames));
    $signature = base64_encode(hash_hmac('sha256', implode(',', $parts), $secretKey, true));

    $result = app('myanmar-payments')->driver('cyber_source')->handleCallback(
        array_merge($fields, ['signature' => $signature])
    );

    expect($result->status)->toBe(PaymentStatus::Failed);
});

it('throws when verify is called', function () {
    app('myanmar-payments')->driver('cyber_source')->verify('ORD-001');
})->throws(PaymentException::class);
