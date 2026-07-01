<?php

use Laranex\LaravelMyanmarPayments\Data\Request\CyberSourceRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\Request\KbzPayRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\HandlePaymentStatus;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\Exceptions\SignatureVerificationException;

it('initiates cybersource payment and returns redirect url', function () {
    $result = app('myanmar-payments')->driver('cyber_source')->initiate(new CyberSourceRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 5000,
        callbackUrl: 'https://example.com/callback',
        frontendUrl: 'https://example.com/success',
    ));

    expect($result->flow)->toBe(PaymentFlow::FormBased)
        ->and($result->value)->toContain('/myanmar-payments/form?payload=')
        ->and($result->originalValue)->toHaveKeys(['url', 'data']);
});

it('throws when wrong data class is passed to cybersource driver', function () {
    app('myanmar-payments')->driver('cyber_source')->initiate(new KbzPayRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 1000,
        callbackUrl: 'https://example.com/callback',
    ));
})->throws(InvalidArgumentException::class, 'expects');

it('throws validation error for invalid callback url', function () {
    app('myanmar-payments')->driver('cyber_source')->initiate(new CyberSourceRequestPaymentData(
        transactionId: fake()->uuid(),
        amount: 5000,
        callbackUrl: 'not-a-url',
    ));
})->throws(InvalidArgumentException::class, 'callbackUrl must be a valid URL');

it('handles a successful cybersource callback', function () {
    $orderId = fake()->uuid();
    $secretKey = 'TEST_CS_SECRET_KEY';
    $signedFieldNames = 'decision,req_reference_number,transaction_id,signed_field_names';
    $fields = [
        'decision' => 'ACCEPT',
        'req_reference_number' => $orderId,
        'transaction_id' => 'CS_TXN_001',
        'signed_field_names' => $signedFieldNames,
    ];
    $parts = array_map(fn ($k) => "$k={$fields[$k]}", explode(',', $signedFieldNames));
    $signature = base64_encode(hash_hmac('sha256', implode(',', $parts), $secretKey, true));

    $result = app('myanmar-payments')->driver('cyber_source')->handleCallback(
        array_merge($fields, ['signature' => $signature])
    );

    expect($result->status)->toBe(HandlePaymentStatus::Successful)
        ->and($result->transactionId)->toBe('CS_TXN_001');
});

it('throws SignatureVerificationException on invalid cybersource callback signature', function () {
    app('myanmar-payments')->driver('cyber_source')->handleCallback([
        'decision' => 'ACCEPT',
        'signed_field_names' => 'decision',
        'signature' => 'INVALID_SIGNATURE',
    ]);
})->throws(SignatureVerificationException::class, 'signature verification failed');

it('handles a declined cybersource callback', function () {
    $secretKey = 'TEST_CS_SECRET_KEY';
    $signedFieldNames = 'decision,signed_field_names';
    $fields = ['decision' => 'DECLINE', 'signed_field_names' => $signedFieldNames];
    $parts = array_map(fn ($k) => "$k={$fields[$k]}", explode(',', $signedFieldNames));
    $signature = base64_encode(hash_hmac('sha256', implode(',', $parts), $secretKey, true));

    $result = app('myanmar-payments')->driver('cyber_source')->handleCallback(
        array_merge($fields, ['signature' => $signature])
    );

    expect($result->status)->toBe(HandlePaymentStatus::Failed);
});
