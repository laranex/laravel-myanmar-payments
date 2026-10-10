<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\LaravelMyanmarPayments\Gateways\AyaPay;
use Laranex\LaravelMyanmarPayments\Gateways\CyberSource;
use Laranex\LaravelMyanmarPayments\Gateways\KbzPay;
use Laranex\LaravelMyanmarPayments\Gateways\WaveMoney;
use Laranex\LaravelMyanmarPayments\Gateways\YomaMmqr;
use Laranex\LaravelMyanmarPayments\MyanmarPayments as MyanmarPaymentsManager;
use Laranex\PhpMyanmarPayments\Amount;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayMethod;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayPaymentData;
use Laranex\PhpMyanmarPayments\Enums\PaymentStatus;
use Laranex\PhpMyanmarPayments\Exceptions\ApiException;
use Laranex\PhpMyanmarPayments\Exceptions\ConfigurationException;
use Laranex\PhpMyanmarPayments\KbzPay\KbzPayPaymentData;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;
use Laranex\PhpMyanmarPayments\YomaMmqr\YomaMmqrPaymentData;

it('resolves every gateway through the facade', function () {
    expect(app(MyanmarPaymentsManager::class))->toBe(app(MyanmarPaymentsManager::class))
        ->and(MyanmarPayments::kbzPay())->toBeInstanceOf(KbzPay::class)
        ->and(MyanmarPayments::waveMoney())->toBeInstanceOf(WaveMoney::class)
        ->and(MyanmarPayments::ayaPay())->toBeInstanceOf(AyaPay::class)
        ->and(MyanmarPayments::yomaMmqr())->toBeInstanceOf(YomaMmqr::class)
        ->and(MyanmarPayments::cyberSource())->toBeInstanceOf(CyberSource::class);
});

it('sends gateway calls through the Laravel HTTP client', function () {
    Http::fake(['*/precreate' => Http::response(['Response' => ['result' => 'SUCCESS', 'code' => '0', 'prepay_id' => 'PREPAY1']])]);

    $payment = MyanmarPayments::kbzPay()->pwa(new KbzPayPaymentData('ORDER_1', 1000, 'https://shop.test/kbz/callback'));

    expect($payment->gatewayReference)->toBe('PREPAY1');
    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://api.kbzpay.com/payment/gateway/precreate'
        && $request['Request']['biz_content']['merch_order_id'] === 'ORDER_1'
        && $request->hasHeader('Content-Type', 'application/json'));
});

it('turns connection failures into an ApiException', function () {
    Http::fake(fn () => throw new ConnectionException('Connection refused'));

    MyanmarPayments::kbzPay()->status('ORDER_1');
})->throws(ApiException::class, 'Connection refused');

it('verifies a callback straight from a Laravel request and acknowledges it', function () {
    $fields = ['merch_order_id' => 'ORDER_1', 'mm_order_id' => 'MM1', 'total_amount' => '1000', 'trade_status' => 'PAY_SUCCESS', 'nonce_str' => 'n'];
    ksort($fields);
    $fields['sign_type'] = 'SHA256';
    $fields['sign'] = strtoupper(hash('sha256', urldecode(http_build_query(array_diff_key($fields, ['sign_type' => 1]))).'&key=secret-key'));

    $request = Request::create('/kbz/callback', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: (string) json_encode(['Request' => $fields]));

    $callback = MyanmarPayments::kbzPay()->handleCallback($request);
    $response = MyanmarPayments::acknowledge($callback)->toResponse($request);

    expect($callback->isSuccessful())->toBeTrue()
        ->and($callback->orderId)->toBe('ORDER_1')
        ->and($response->getContent())->toBe('success')
        ->and($response->getStatusCode())->toBe(200);
});

it('serves an auto-submitting form for form based gateways', function () {
    $payment = MyanmarPayments::ayaPay()->initiate(new AyaPayPaymentData('ORDER123', 1000, 'aya_pay', AyaPayMethod::Qr));

    expect($payment->autoSubmitUrl)->toStartWith('http://localhost/myanmar-payments/form?payload=');

    $this->get($payment->autoSubmitUrl)
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('action="https://pgw.ayainnovation.com/v1/payment/request"', false)
        ->assertSee('name="checkSum" value="'.$payment->fields['checkSum'].'"', false);
});

it('rejects tampered or expired form links', function () {
    $this->get('/myanmar-payments/form?payload=tampered')->assertStatus(410);

    $payment = MyanmarPayments::ayaPay()->initiate(new AyaPayPaymentData('ORDER123', 1000, 'aya_pay', AyaPayMethod::Qr));
    $this->travel(31)->minutes();

    $this->get((string) $payment->autoSubmitUrl)->assertStatus(410);
});

it('keeps the Yoma token in the Laravel cache between requests', function () {
    Http::fake([
        '*/token' => Http::response(['access_token' => 'token-1', 'expires_in' => 28800]),
        '*/check-status' => Http::response(['refLabel' => '1', 'paymentStatus' => 'PENDING', 'errorCode' => null]),
    ]);

    MyanmarPayments::yomaMmqr()->status('1');
    app()->forgetInstance(MyanmarPaymentsManager::class);
    MyanmarPayments::clearResolvedInstances();
    MyanmarPayments::yomaMmqr()->status('1');

    Http::assertSentCount(3);
});

it('names the missing setting when a gateway is not configured', function () {
    config()->set('myanmar-payments.wave_money', []);
    app()->forgetInstance(MyanmarPaymentsManager::class);
    MyanmarPayments::clearResolvedInstances();

    expect(fn () => MyanmarPayments::waveMoney())->toThrow(function (ConfigurationException $exception) {
        expect($exception->getMessage())->toContain('[merchant_id]')
            ->and($exception->gateway)->toBe('wave_money')
            ->and($exception->key)->toBe('merchant_id');
    });
});

it('sends exact decimal amounts where the gateway allows them', function () {
    Http::fake(['*/precreate' => Http::response(['Response' => ['result' => 'SUCCESS', 'code' => '0', 'prepay_id' => 'PREPAY1', 'qrCode' => 'qr']])]);

    MyanmarPayments::kbzPay()->qr(new KbzPayPaymentData('ORDER_1', Amount::parse('1000.50'), 'https://shop.test/kbz/callback'));

    Http::assertSent(fn (HttpRequest $request): bool => $request['Request']['biz_content']['total_amount'] === '1000.50');
});

it('sends every gateway API call through the Laravel HTTP client', function () {
    $enquiry = ['merchOrderId' => 'ORDER123', 'tranId' => 'T1', 'amount' => '1000', 'statusCode' => '00'];

    Http::preventStrayRequests();
    Http::fake([
        '*/precreate' => Http::response(['Response' => ['result' => 'SUCCESS', 'code' => '0', 'prepay_id' => 'PREPAY1', 'qrCode' => 'kbz-qr']]),
        '*/queryorder' => Http::response(['Response' => ['result' => 'SUCCESS', 'code' => '0', 'merch_order_id' => 'ORDER_1', 'mm_order_id' => 'MM1', 'trade_status' => 'PAY_SUCCESS', 'total_amount' => '1000']]),
        '*/v1/payment/services' => Http::response(['status' => '00', 'data' => [['name' => 'AYA Pay', 'key' => 'aya_pay', 'methods' => ['QR', 'NOTI']]]]),
        '*/v1/payment/enquiry' => Http::response(['status' => '00', 'data' => [
            'payload' => base64_encode((string) json_encode($enquiry)),
            'checkSum' => hash_hmac('sha256', implode(':', $enquiry), 'aya-secret'),
        ]]),
        '*/token' => Http::response(['access_token' => 'token-1', 'expires_in' => 28800]),
        '*/payment/checkout' => Http::response(['checkOutStatus' => true]),
        '*/qr/generate' => Http::response(['refLabel' => 'REF1', 'qrString' => base64_encode('png')]),
        '*/payment/check-status' => Http::response(['refLabel' => 'REF1', 'paymentStatus' => 'SUCCESS']),
    ]);

    $kbz = new KbzPayPaymentData('ORDER_1', 1000, 'https://shop.test/kbz/callback');
    $services = MyanmarPayments::ayaPay()->services();
    $yoma = MyanmarPayments::yomaMmqr()->initiate(new YomaMmqrPaymentData('ORDER_7', 1000, 'Order 7'));

    expect(MyanmarPayments::kbzPay()->qr($kbz)->qrString)->toBe('kbz-qr')
        ->and(MyanmarPayments::kbzPay()->app($kbz)->toArray()['orderId'])->toBe('ORDER_1')
        ->and(MyanmarPayments::kbzPay()->status('ORDER_1')->isSuccessful())->toBeTrue()
        ->and($services)->toHaveCount(1)
        ->and($services[0]->key)->toBe('aya_pay')
        ->and($services[0]->supports(AyaPayMethod::Qr))->toBeTrue()
        ->and(MyanmarPayments::ayaPay()->status('ORDER123')->gatewayReference)->toBe('T1')
        ->and($yoma->reference)->toBe('REF1')
        ->and($yoma->qrImageDataUri())->toBe('data:image/png;base64,'.base64_encode('png'))
        ->and(MyanmarPayments::yomaMmqr()->renewQr('ORDER_7')->reference)->toBe('REF1')
        ->and(MyanmarPayments::yomaMmqr()->status('REF1')->isSuccessful())->toBeTrue();

    Http::assertSent(fn (HttpRequest $request): bool => str_ends_with($request->url(), '/payment/checkout')
        && $request->hasHeader('Authorization', 'Bearer token-1')
        && $request['orderNumber'] === 'ORDER_7');
});

it('lets apps mock the facade to test their own callback handling', function () {
    $callback = new PaymentCallback(orderId: 'ORDER_1', status: PaymentStatus::Successful, gatewayStatus: 'PAY_SUCCESS', amount: '1000');

    MyanmarPayments::shouldReceive('kbzPay->handleCallback')->andReturn($callback);

    expect(MyanmarPayments::kbzPay()->handleCallback(Request::create('/kbz/callback', 'POST'))->isSuccessful())->toBeTrue();
});
