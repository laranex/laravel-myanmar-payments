<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\LaravelMyanmarPayments\Http\CallbackRequestFactory;
use Laranex\LaravelMyanmarPayments\Http\CallbackResponse;
use Laranex\PhpMyanmarPayments\CyberSource\CyberSourcePaymentData;
use Laranex\PhpMyanmarPayments\Enums\PaymentStatus;
use Laranex\PhpMyanmarPayments\Exceptions\SignatureVerificationException;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;
use Laranex\PhpMyanmarPayments\WaveMoney\WaveMoneyItem;
use Laranex\PhpMyanmarPayments\WaveMoney\WaveMoneyPaymentData;

/**
 * @param  array<string, mixed>  $payload
 */
function jsonCallback(string $uri, array $payload, array $headers = []): Request
{
    $server = ['CONTENT_TYPE' => 'application/json'];

    foreach ($headers as $name => $value) {
        $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
    }

    return Request::create($uri, 'POST', server: $server, content: (string) json_encode($payload));
}

/**
 * @param  array<string, mixed>  $payload
 */
function signedAyaInput(array $payload): array
{
    $encoded = base64_encode((string) json_encode($payload));
    $fields = ['merchOrderId', 'tranId', 'amount', 'currencyCode', 'statusCode', 'approvalCode', 'tranRef', 'description', 'dateTime'];
    $values = array_map(fn (string $field): string => (string) $payload[$field], array_values(array_filter($fields, fn (string $field): bool => array_key_exists($field, $payload))));

    return ['payload' => $encoded, 'checkSum' => hash_hmac('sha256', implode(':', $values), 'aya-secret')];
}

it('verifies a Wave Money callback from a Laravel request', function () {
    $payload = ['status' => 'PAYMENT_CONFIRMED', 'timeToLiveSeconds' => 300, 'merchantId' => 'wave', 'orderId' => 'ORDER_1', 'amount' => '1000', 'backendResultUrl' => 'https://shop.test/wave/callback', 'merchantReferenceId' => 'ref-1', 'initiatorMsisdn' => '9597', 'transactionId' => '360', 'paymentRequestId' => '12', 'requestTime' => '2024-01-01 00:00:00'];
    $fields = ['status', 'timeToLiveSeconds', 'merchantId', 'orderId', 'amount', 'backendResultUrl', 'merchantReferenceId', 'initiatorMsisdn', 'transactionId', 'paymentRequestId', 'requestTime'];
    $payload['hashValue'] = hash_hmac('sha256', implode('', array_map(fn (string $field): string => (string) $payload[$field], $fields)), 'wave-secret');

    $callback = MyanmarPayments::waveMoney()->handleCallback(jsonCallback('/wave/callback', $payload));

    expect($callback->isSuccessful())->toBeTrue()
        ->and($callback->orderId)->toBe('ORDER_1')
        ->and($callback->gatewayReference)->toBe('360')
        ->and($callback->amount)->toBe('1000');
});

it('verifies an AYA Pay callback and its browser redirect from Laravel requests', function () {
    $payload = ['merchOrderId' => 'ORDER123', 'tranId' => 'T1', 'amount' => '1000', 'currencyCode' => 'MMK', 'statusCode' => '00', 'approvalCode' => 'A1', 'tranRef' => 'R1', 'description' => 'Order', 'dateTime' => '2024-01-01 00:00:00'];
    $input = signedAyaInput($payload);

    $callback = MyanmarPayments::ayaPay()->handleCallback(jsonCallback('/aya/callback', $input));
    $redirect = MyanmarPayments::ayaPay()->verifyRedirect(Request::create('/aya/return', 'GET', $input));

    expect($callback->isSuccessful())->toBeTrue()
        ->and($callback->orderId)->toBe('ORDER123')
        ->and($callback->gatewayReference)->toBe('T1')
        ->and($redirect->isSuccessful())->toBeTrue()
        ->and($redirect->orderId)->toBe('ORDER123');
});

it('verifies a Yoma MMQR callback, including its secret header, from a Laravel request', function () {
    $payload = ['orderNumber' => 'ORDER_7', 'status' => 'SUCCESS'];
    $payload['hashValue'] = hash_hmac('sha256', 'orderNumber=ORDER_7&status=SUCCESS', 'ORDER_7hash-key');

    $callback = MyanmarPayments::yomaMmqr()->handleCallback(jsonCallback('/yoma/callback', $payload, ['X-Webhook-Secret' => 'hook-secret']));

    expect($callback->isSuccessful())->toBeTrue()
        ->and($callback->orderId)->toBe('ORDER_7');

    MyanmarPayments::yomaMmqr()->handleCallback(jsonCallback('/yoma/callback', $payload, ['X-Webhook-Secret' => 'wrong']));
})->throws(SignatureVerificationException::class, 'X-Webhook-Secret');

it('verifies a CyberSource result post from a Laravel request and attaches the form link on initiate', function () {
    $payment = MyanmarPayments::cyberSource()->initiate(new CyberSourcePaymentData(orderId: 'ORDER-1', amount: 1000, callbackUrl: 'https://shop.test/cs/callback'));

    expect($payment->autoSubmitUrl)->toStartWith('http://localhost/myanmar-payments/form?payload=')
        ->and($payment->fields['reference_number'])->toBe('ORDER-1');

    $fields = ['decision' => 'ACCEPT', 'req_reference_number' => 'ORDER-1', 'req_amount' => '1000.00', 'auth_amount' => '1000.00', 'transaction_id' => '7000', 'signed_field_names' => 'decision,req_reference_number,req_amount,auth_amount,transaction_id,signed_field_names'];
    $pairs = array_map(fn (string $name): string => $name.'='.$fields[$name], explode(',', $fields['signed_field_names']));
    $fields['signature'] = base64_encode(hash_hmac('sha256', implode(',', $pairs), 'cs-secret', true));

    $callback = MyanmarPayments::cyberSource()->handleCallback(Request::create('/cs/callback', 'POST', server: ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], content: http_build_query($fields)));

    expect($callback->status)->toBe(PaymentStatus::Successful)
        ->and($callback->orderId)->toBe('ORDER-1')
        ->and($callback->gatewayReference)->toBe('7000')
        ->and($callback->amount)->toBe('1000.00');
});

it('rejects a tampered callback', function () {
    MyanmarPayments::waveMoney()->handleCallback(jsonCallback('/wave/callback', ['status' => 'PAYMENT_CONFIRMED', 'orderId' => 'ORDER_1', 'hashValue' => 'nope']));
})->throws(SignatureVerificationException::class);

it('passes framework-agnostic callback requests straight through', function () {
    $request = CallbackRequest::fromArray(['orderNumber' => 'ORDER_7']);
    $laravel = jsonCallback('/callback?source=yoma', ['a' => 1], ['X-Webhook-Secret' => 'hook-secret']);
    $converted = CallbackRequestFactory::make($laravel);

    expect(CallbackRequestFactory::make($request))->toBe($request)
        ->and($converted->body)->toBe('{"a":1}')
        ->and($converted->query)->toBe(['source' => 'yoma'])
        ->and($converted->header('x-webhook-secret'))->toBe('hook-secret')
        ->and($converted->header('Content-Type'))->toBe('application/json');
});

it('acknowledges callbacks with the response each gateway expects, directly from a route', function () {
    $payload = ['orderNumber' => 'ORDER_7', 'status' => 'SUCCESS', 'hashValue' => hash_hmac('sha256', 'orderNumber=ORDER_7&status=SUCCESS', 'ORDER_7hash-key')];

    Route::post('/yoma/callback', fn (Request $request): CallbackResponse => MyanmarPayments::acknowledge(MyanmarPayments::yomaMmqr()->handleCallback($request)));

    $response = $this->postJson('/yoma/callback', $payload, ['X-Webhook-Secret' => 'hook-secret']);

    $response->assertOk();

    expect(strtolower((string) $response->headers->get('Content-Type')))->toBe('text/plain; charset=utf-8')
        ->and($response->getContent())->toBe('')
        ->and(MyanmarPayments::acknowledge(MyanmarPayments::yomaMmqr()->handleCallback(jsonCallback('/yoma/callback', $payload, ['X-Webhook-Secret' => 'hook-secret']))))->toBeInstanceOf(CallbackResponse::class);
});

it('forwards form encoded gateway requests with their headers through the HTTP client', function () {
    Http::fake(['*/payment' => Http::response(['message' => 'success', 'transaction_id' => 'tx-1'])]);

    $payment = MyanmarPayments::waveMoney()->initiate(new WaveMoneyPaymentData(
        orderId: 'ORDER_1',
        callbackUrl: 'https://shop.test/wave/callback',
        returnUrl: 'https://shop.test/done',
        description: 'Order 1',
        items: [new WaveMoneyItem('Shoes', 1000)],
        merchantReferenceId: 'ref-1',
    ));

    expect($payment->gatewayReference)->toBe('tx-1');
    Http::assertSent(fn (HttpRequest $request): bool => $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
        && $request['merchant_id'] === 'wave'
        && $request['order_id'] === 'ORDER_1');
});
