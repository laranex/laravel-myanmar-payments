---
name: laravel-myanmar-payments-development
description: >
  Integrate Myanmar payment gateways (KBZ Pay, Wave Money, AYA Pay, Yoma MMQR, CyberSource) in a Laravel app with laranex/laravel-myanmar-payments.
license: MIT
metadata:
  author: Nay Thu Khant
---

# Laravel Myanmar Payments

Use this skill when a Laravel application takes payments through KBZ Pay, Wave Money, AYA Payment Gateway, Yoma MMQR or CyberSource.

## Primary Goal

- start payments and verify callbacks with the package's typed API, never with hand-built signatures

## Workflow

### 1. Configure the gateway

- set only the env keys of the gateways in use (`KBZ_PAY_*`, `WAVE_MONEY_*`, `AYA_PAY_*`, `YOMA_MMQR_*`, `CYBER_SOURCE_*`); `*_SANDBOX=false` for production
- publish the config only when it must change: `php artisan vendor:publish --tag="myanmar-payments-config"`

### 2. Start a payment

- build the gateway's data object, e.g. `new KbzPayPaymentData(orderId:, amount:, callbackUrl:)`; amounts are `int` or `Laranex\PhpMyanmarPayments\Amount` (`Amount::kyat(1000)`, `Amount::parse('1000.50')`), never floats — only KBZ Pay (≤2 decimals) and CyberSource accept decimals; it throws `InvalidPaymentDataException` with `errors()` on bad input
- call the gateway through the facade `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments` and act on the typed result:
  - `RedirectPayment` (`kbzPay()->pwa()`, `waveMoney()->initiate()`): `redirect($payment->url)`
  - `FormPayment` (`ayaPay()->initiate()`, `cyberSource()->initiate()`): `redirect($payment->autoSubmitUrl)`
  - `QrPayment`: KBZ gives `qrString` to encode; Yoma gives `qrImage` (base64) and `expiresAt`, renew with `yomaMmqr()->renewQr($orderId)`
  - `AppPayment` (`kbzPay()->app()`): return `toArray()` to the mobile app
- store the order id; for Wave also store `$data->merchantReferenceId`

### 3. Handle the callback

- register a POST route without CSRF middleware
- `$callback = MyanmarPayments::<gateway>()->handleCallback($request)` verifies the signature and throws `SignatureVerificationException` when it fails
- check `$callback->status` (`PaymentStatus`), compare `$callback->amount` with the order, make fulfilment idempotent
- `return MyanmarPayments::acknowledge($callback);` so the gateway stops retrying

## Rules, References, and Templates

- no additional resource files for this skill

## Examples

- KBZ Pay PWA checkout: `MyanmarPayments::kbzPay()->pwa(new KbzPayPaymentData('ORDER_1', 1000, route('payments.kbz.callback')))` then `redirect($payment->url)`
- AYA checkout with a chosen wallet: list channels with `ayaPay()->services()`, then `ayaPay()->initiate(new AyaPayPaymentData('ORDER123', 1000, 'kbz_pay', AyaPayMethod::Qr))`

## Anti-patterns

- do not trust return URLs or query strings as proof of payment; fulfil from the verified callback or a status check
- do not treat `PaymentStatus::Pending` or `Unknown` as paid
- do not reuse a Wave `merchantReferenceId` or re-run Yoma `initiate()` for the same order
