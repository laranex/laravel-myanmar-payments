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

Use this skill when a Laravel application starts a payment, handles a gateway callback or checks a payment status with KBZ Pay, Wave Money, AYA Payment Gateway, Yoma MMQR or CyberSource. Start payments and verify callbacks with the package's typed API; never build gateway signatures by hand. The package wraps the PHP SDK `laranex/php-myanmar-payments`: payment data, results, statuses and exceptions are the SDK's classes (`Laranex\PhpMyanmarPayments\...`).

## Install

```bash
composer require laranex/laravel-myanmar-payments
```

Requires PHP 8.1+ and Laravel 10 to 13. The service provider and the `MyanmarPayments` facade are auto-discovered. Gateway calls go through Laravel's `Http` client, Yoma MMQR tokens through the cache, and auto-submit form links are encrypted with `APP_KEY`.

## Configure

Set the env keys of the gateways you use. Every gateway uses its production URLs unless you set the URL overrides (`*_BASE_URL`, `KBZ_PAY_PWA_BASE_REDIRECT_URL`, `WAVE_MONEY_AUTHENTICATE_URL`) to the gateway's UAT URLs. Every other setting of those gateways is required and has no default.

- KBZ Pay: `KBZ_PAY_APP_ID`, `KBZ_PAY_APP_KEY`, `KBZ_PAY_MERCHANT_CODE`
- Wave Money: `WAVE_MONEY_MERCHANT_ID`, `WAVE_MONEY_SECRET_KEY`, `WAVE_MONEY_MERCHANT_NAME`, `WAVE_MONEY_TIME_TO_LIVE_IN_SECONDS`
- AYA Pay: `AYA_PAY_APP_KEY`, `AYA_PAY_APP_SECRET` (`AYA_PGW_*` also read)
- Yoma MMQR: `YOMA_MMQR_MERCHANT_ID`, `YOMA_MMQR_CLIENT_ID`, `YOMA_MMQR_CLIENT_SECRET`, `YOMA_MMQR_WEBHOOK_HASHKEY`, `YOMA_MMQR_API_VERSION` (e.g. `v1rc`), optional `YOMA_MMQR_WEBHOOK_SECRET`
- CyberSource: `CYBER_SOURCE_PROFILE_ID`, `CYBER_SOURCE_ACCESS_KEY`, `CYBER_SOURCE_SECRET_KEY`
- Shared: `MYANMAR_PAYMENTS_HTTP_TIMEOUT` (seconds, required by every gateway except CyberSource), `MYANMAR_PAYMENTS_FORM_TTL_MINUTES` (minutes an auto-submit form link stays valid, required for AYA Pay and CyberSource while the form route is enabled), optional `MYANMAR_PAYMENTS_CACHE_STORE` (store for the Yoma access token, default store when empty)

Publish `config/myanmar-payments.php` only to change it, for example the auto-submit form route (`form_route.enabled`, `path`, `middleware`, `ttl_minutes`):

```bash
php artisan vendor:publish --tag="myanmar-payments-config"
```

An unconfigured gateway throws `ConfigurationException` (`gateway`, `key`) naming the missing key when first used, not at boot; so does a time setting that is not a whole number greater than 0.

## Use

Every gateway is reached through `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments`: `kbzPay()`, `waveMoney()`, `ayaPay()`, `yomaMmqr()` and `cyberSource()`, or `gateway('kbz-pay')` by name (`gateways()` lists `kbz-pay`, `wave-money`, `aya-pay`, `yoma-mmqr`, `cyber-source`). Each gateway is built once and reused.

### Amounts

Pass an `int` (whole kyat) or a `Laranex\PhpMyanmarPayments\Amount` (`Amount::kyat(10000)`, `Amount::parse('10000.50')`), never a float. Only KBZ Pay (up to 2 decimals) and CyberSource accept decimals. Invalid data throws `InvalidPaymentDataException`; read the messages with `errors()`.

### Start a payment

```php
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\PhpMyanmarPayments\KbzPay\KbzPayPaymentData;

$data = new KbzPayPaymentData(
    orderId: 'ORDER_'.$order->id,
    amount: 10000,
    callbackUrl: route('payments.kbz.callback'),
);

$payment = MyanmarPayments::kbzPay()->pwa($data);

return redirect()->away($payment->url);
```

- `RedirectPayment` from `kbzPay()->pwa()` and `waveMoney()->initiate()`: redirect to `$payment->url`. For Wave, store `$data->merchantReferenceId` with the order.
- `QrPayment` from `kbzPay()->qr()` (encode `qrString`) and `yomaMmqr()->initiate()` (`qrImage` as base64, `qrImageDataUri()`, `expiresAt`, `reference`). Renew an expired Yoma QR with `yomaMmqr()->renewQr($orderId)`.
- `AppPayment` from `kbzPay()->app()`: return `$payment->toArray()` to the mobile app.

### Form payments (AYA Pay, CyberSource)

`ayaPay()->initiate()` and `cyberSource()->initiate()` return a `FormPayment` the customer's browser must POST. Redirect to `$payment->autoSubmitUrl`: a link to the package's `GET myanmar-payments/form` route, encrypted with `APP_KEY` and valid for `form_route.ttl_minutes`; a tampered or expired link answers 410. Or return `$payment->toHtml()` yourself.

AYA Pay needs a channel: list them with `MyanmarPayments::ayaPay()->services()` (each `AyaPayService` has `key` and `supports(AyaPayMethod $method)`), then:

```php
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayMethod;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayPaymentData;

$data = new AyaPayPaymentData(
    orderId: 'ORDER_'.$order->id,
    amount: 10000,
    channel: 'kbz_pay',
    method: AyaPayMethod::Qr,
);

$payment = MyanmarPayments::ayaPay()->initiate($data);

return redirect($payment->autoSubmitUrl);
```

`CyberSourcePaymentData` requires `currency`, `transactionType` and `locale`, e.g. `'MMK'`, `CyberSourceTransactionType::Sale` and `'en-us'`.

### Handle the callback

Register a POST route and exclude it from CSRF verification. `handleCallback()` takes the Laravel `Request`, verifies the signature and returns a `PaymentCallback`, or throws `SignatureVerificationException`:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;

Route::post('/payments/kbz/callback', function (Request $request) {
    $callback = MyanmarPayments::kbzPay()->handleCallback($request);

    if ($callback->isSuccessful()) {
        // compare $callback->amount with the order, then fulfill $callback->orderId once
    }

    return MyanmarPayments::acknowledge($callback); // KBZ Pay: plain "success"
})->name('payments.kbz.callback');
```

- `MyanmarPayments::acknowledge($callback)` returns the reply each gateway expects so it stops retrying; without a callback it is an empty 200.
- One route for every gateway: `MyanmarPayments::handleCallback($gateway, $request)` with a name from `gateways()`.
- Check AYA's browser return with `MyanmarPayments::ayaPay()->verifyRedirect($request)`; it is never proof of payment.
- For production, store the verified call, acknowledge immediately and process it once in a queued job; the docs show this flow as app code (the package stores nothing).

### Check status and handle errors

- `kbzPay()->status($orderId)`, `ayaPay()->status($orderId)` and `yomaMmqr()->status($reference)` return a `PaymentStatusResult` with `status` and `isSuccessful()`. Wave Money and CyberSource have no status API.
- Statuses are the `PaymentStatus` enum: `Successful`, `Pending`, `Failed`, `Canceled`, `Expired`, `Unknown`.
- Gateway errors throw `ApiException` (`gatewayCode`, `gatewayMessage`, `httpStatus`, `raw`); catch `PaymentException` for every package error.

## Test your app

- Gateway calls go through Laravel's HTTP client: fake them with `Http::fake()` and add `Http::preventStrayRequests()` so nothing reaches a real gateway.

```php
use Illuminate\Support\Facades\Http;

Http::preventStrayRequests();
Http::fake(['*/precreate' => Http::response(['Response' => [
    'result' => 'SUCCESS', 'code' => '0', 'prepay_id' => 'PREPAY1',
]])]);
```

- Post correctly signed payloads to your callback route, signed with the secret from your test configuration as each gateway page describes.
- To test your own handling without signed payloads, mock the facade: `MyanmarPayments::shouldReceive('kbzPay->handleCallback')->andReturn($callback)` with a `PaymentCallback` you build yourself.
- Follow `$payment->autoSubmitUrl` with `$this->get()` to assert the auto-submitting form; tampered or expired links answer 410.

## Avoid

- Fulfilling orders from return pages or query strings; fulfill only from a verified callback or a status check.
- Treating `Pending` or `Unknown` as paid, or skipping the amount check.
- Passing floats as amounts.
- Calling Yoma `initiate()` twice for one order (use `renewQr()`), or reusing a Wave `merchantReferenceId`.
- Putting callback routes behind CSRF verification or authentication, or the form route behind authentication; gateways and redirected browsers have no session.
