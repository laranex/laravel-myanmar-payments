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
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayMethod;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayPaymentData;
use Laranex\PhpMyanmarPayments\Exceptions\ApiException;
use Laranex\PhpMyanmarPayments\Exceptions\ConfigurationException;
use Laranex\PhpMyanmarPayments\KbzPay\KbzPayPaymentData;

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
    Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'http://api-uat.kbzpay.com/payment/gateway/uat/precreate'
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
        ->assertSee('action="https://uat-pgw.ayainnovation.com/v1/payment/request"', false)
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

    MyanmarPayments::waveMoney();
})->throws(ConfigurationException::class, '[merchant_id]');
