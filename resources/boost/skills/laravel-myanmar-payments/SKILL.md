---
name: laravel-myanmar-payments
description: >
  Integrate Myanmar payment gateways (KBZ Pay, Wave Money, AYA Pay, Yoma MMQR, CyberSource) in a Laravel app with laranex/laravel-myanmar-payments.
license: MIT
metadata:
  author: Nay Thu Khant
---

# Laravel Myanmar Payments

## When to use

Use this skill when a Laravel application takes payments through KBZ Pay, Wave Money, AYA Payment Gateway, Yoma MMQR or CyberSource. Start payments and verify callbacks with the package's typed API; never build gateway signatures by hand. Outside Laravel, use `laranex/php-myanmar-payments`, which this package wraps.

## Install

```bash
composer require laranex/laravel-myanmar-payments
```

Requires PHP 8.1+ and Laravel 10 to 13. The service provider and the `MyanmarPayments` facade are auto-discovered.

## Configure

Set only the env keys of the gateways you use. Every gateway runs against its sandbox until `*_SANDBOX=false`.

- KBZ Pay: `KBZ_PAY_APP_ID`, `KBZ_PAY_APP_KEY`, `KBZ_PAY_MERCHANT_CODE`, `KBZ_PAY_SANDBOX`
- Wave Money: `WAVE_MONEY_MERCHANT_ID`, `WAVE_MONEY_SECRET_KEY`, `WAVE_MONEY_MERCHANT_NAME` (defaults to `APP_NAME`), `WAVE_MONEY_TIME_TO_LIVE_IN_SECONDS`, `WAVE_MONEY_SANDBOX`
- AYA Pay: `AYA_PAY_APP_KEY`, `AYA_PAY_APP_SECRET`, `AYA_PAY_SANDBOX`
- Yoma MMQR: `YOMA_MMQR_MERCHANT_ID`, `YOMA_MMQR_CLIENT_ID`, `YOMA_MMQR_CLIENT_SECRET`, `YOMA_MMQR_WEBHOOK_HASHKEY`, `YOMA_MMQR_WEBHOOK_SECRET`, `YOMA_MMQR_SANDBOX`
- CyberSource: `CYBER_SOURCE_PROFILE_ID`, `CYBER_SOURCE_ACCESS_KEY`, `CYBER_SOURCE_SECRET_KEY`, `CYBER_SOURCE_SANDBOX`
- Shared: `MYANMAR_PAYMENTS_HTTP_TIMEOUT` (seconds, default 30), `MYANMAR_PAYMENTS_CACHE_STORE` (store for the Yoma access token, default store when empty)

Publish `config/myanmar-payments.php` only when you need to change it, for example the auto-submit form route (`form_route.enabled`, `path`, `middleware`, `ttl_minutes`):

```bash
php artisan vendor:publish --tag="myanmar-payments-config"
```

## Use

Every gateway is reached through `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments`: `kbzPay()`, `waveMoney()`, `ayaPay()`, `yomaMmqr()` and `cyberSource()`.

### Amounts

Pass an `int` (whole kyat) or a `Laranex\PhpMyanmarPayments\Amount` (`Amount::kyat(1000)`, `Amount::parse('1000.50')`), never a float. Only KBZ Pay (up to 2 decimals) and CyberSource accept decimals. Invalid data throws `InvalidPaymentDataException`; read the messages with `errors()`.

### Start a payment

Each flow returns a typed result:

```php
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\PhpMyanmarPayments\KbzPay\KbzPayPaymentData;

$payment = MyanmarPayments::kbzPay()->pwa(new KbzPayPaymentData(
    orderId: 'ORDER_1',
    amount: 1000,
    callbackUrl: route('payments.kbz.callback'),
));

return redirect($payment->url);
```

- `RedirectPayment` from `kbzPay()->pwa()` and `waveMoney()->initiate()`: `redirect($payment->url)`. For Wave, store `$data->merchantReferenceId` with the order.
- `FormPayment` from `ayaPay()->initiate()` and `cyberSource()->initiate()`: `redirect($payment->autoSubmitUrl)`; the package serves the auto-submitting form.
- `QrPayment` from `kbzPay()->qr()` (encode `qrString`) and `yomaMmqr()->initiate()` (`qrImage` as base64, `qrImageDataUri()`, `expiresAt`, `reference`). Renew an expired Yoma QR with `yomaMmqr()->renewQr($orderId)`.
- `AppPayment` from `kbzPay()->app()`: return `$payment->toArray()` to the mobile app.

AYA Pay needs a channel: list them with `MyanmarPayments::ayaPay()->services()` (each `AyaPayService` has `key` and `supports(AyaPayMethod $method)`), then:

```php
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayMethod;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayPaymentData;

$payment = MyanmarPayments::ayaPay()->initiate(new AyaPayPaymentData('ORDER123', 1000, 'kbz_pay', AyaPayMethod::Qr));

return redirect($payment->autoSubmitUrl);
```

### Handle the callback

Register a POST route and exclude it from CSRF verification. `handleCallback()` takes the Laravel `Request`, verifies the signature and returns a `PaymentCallback`, or throws `SignatureVerificationException`:

```php
use Illuminate\Http\Request;

Route::post('/payments/kbz/callback', function (Request $request) {
    $callback = MyanmarPayments::kbzPay()->handleCallback($request);

    if ($callback->isSuccessful()) {
        // compare $callback->amount with the order, then fulfill $callback->orderId once
    }

    return MyanmarPayments::acknowledge($callback);
})->name('payments.kbz.callback');
```

`MyanmarPayments::acknowledge($callback)` returns the reply each gateway expects so it stops retrying. Check AYA's browser return URL with `MyanmarPayments::ayaPay()->verifyRedirect($request)`.

### Check status and handle errors

- `kbzPay()->status($orderId)`, `ayaPay()->status($orderId)` and `yomaMmqr()->status($reference)` return a `PaymentStatusResult` with `status` and `isSuccessful()`.
- Statuses are the `PaymentStatus` enum: `Successful`, `Pending`, `Failed`, `Cancelled`, `Expired`, `Unknown`.
- Gateway errors throw `ApiException` (`gatewayCode`, `gatewayMessage`, `httpStatus`, `raw`); catch `PaymentException` for every package error.

## Test your app

Gateway calls go through Laravel's HTTP client, so fake them with `Http::fake()` (add `Http::preventStrayRequests()` so nothing reaches a real gateway):

```php
use Illuminate\Support\Facades\Http;

Http::preventStrayRequests();
Http::fake(['*/precreate' => Http::response(['Response' => ['result' => 'SUCCESS', 'code' => '0', 'prepay_id' => 'PREPAY1']])]);

$payment = MyanmarPayments::kbzPay()->pwa(new KbzPayPaymentData('ORDER_1', 1000, 'https://shop.test/kbz/callback'));
```

To test your own callback handling without signed payloads, mock the facade, for example `MyanmarPayments::shouldReceive('kbzPay->handleCallback')->andReturn($callback)` with a `PaymentCallback` you build yourself.

Follow `$payment->autoSubmitUrl` with `$this->get()` to assert the auto-submitting form; tampered or expired links answer `410`.

## Avoid

- Fulfilling orders from return URLs or query strings; fulfill only from a verified callback or a status check.
- Treating `PaymentStatus::Pending` or `Unknown` as paid.
- Passing floats as amounts.
- Reusing a Wave `merchantReferenceId`, or calling Yoma `initiate()` twice for the same order (use `renewQr()`).
- Leaving the callback route behind CSRF verification.
