# Laravel Myanmar Payments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-myanmar-payments)
[![Tests](https://github.com/laranex/laravel-myanmar-payments/actions/workflows/tests.yml/badge.svg)](https://github.com/laranex/laravel-myanmar-payments/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-myanmar-payments)
[![License](https://img.shields.io/packagist/l/laranex/laravel-myanmar-payments.svg?style=flat-square)](LICENSE.md)

Laravel integration for Myanmar payment gateways: KBZ Pay, Wave Pay, Wave Pay Mini App, AYA Pay, Yoma MMQR and CyberSource. Built on PHP Myanmar Payments. Facade and config, verified callbacks and test fakes. Built for humans and AI agents.

## Why Laravel Myanmar Payments

Every Myanmar payment gateway signs, sends and acknowledges payments its own way, with its own rules for amounts. Laravel Myanmar Payments puts KBZ Pay, Wave Pay, AYA Pay, Yoma MMQR and CyberSource behind one facade. All gateway logic lives in [PHP Myanmar Payments](https://laranex.vercel.app/php-myanmar-payments/introduction); this package wires it into Laravel.

- **One facade, every gateway.** `MyanmarPayments::kbzPay()`, `wavePay()`, `ayaPay()`, `yomaMmqr()` and `cyberSource()`, configured from environment variables.
- **A typed result for each flow.** Every payment returns a `RedirectPayment`, `FormPayment`, `QrPayment` or `AppPayment`, so you always know what to do next. See [Payment Flows](https://laranex.vercel.app/laravel-myanmar-payments/payment-flows).
- **Exact amounts.** Plain integers or `Amount` values, never floats, checked against each gateway's documented rules before any request is sent.
- **Verified callbacks.** `handleCallback($request)` checks the gateway's signature against the exact body it sent and maps its status onto one `PaymentStatus` enum; `acknowledge($callback)` returns the response the gateway expects.
- **Laravel all the way down.** Gateway calls go through the `Http` client, Yoma MMQR tokens are shared through your cache store, and AYA Pay and CyberSource checkout forms open from an encrypted link.
- **Easy to test.** `Http::fake()` sees every gateway call, the facade can be mocked, and signed callbacks can be posted to your own routes. See [Testing](https://laranex.vercel.app/laravel-myanmar-payments/testing).
- **Your webhooks, your storage.** The package never stores webhooks; the docs show the recommended store, acknowledge and process-once flow as sample code. See [Handling Webhooks](https://laranex.vercel.app/laravel-myanmar-payments/webhooks).
- **Ready for AI agents.** An agent skill, installed by Laravel Boost or `npx skills add`, teaches coding agents to use the package correctly.

## Installation

> **Requires** PHP 8.1+ and Laravel 10 to 13.

```bash
composer require laranex/laravel-myanmar-payments
```

## Documentation

The full documentation lives at **[laranex.vercel.app/laravel-myanmar-payments](https://laranex.vercel.app/laravel-myanmar-payments)**:

- [Introduction](https://laranex.vercel.app/laravel-myanmar-payments/introduction): what Laravel Myanmar Payments does and why to use it
- [Installation](https://laranex.vercel.app/laravel-myanmar-payments/installation): auto-discovery, publishing the config and what the package provides
- [AI Agent Skill](https://laranex.vercel.app/laravel-myanmar-payments/ai-agent-skill): installing the agent skill with Laravel Boost or `npx skills add`
- [Configuration](https://laranex.vercel.app/laravel-myanmar-payments/configuration): environment variables, endpoints, UAT testing, the HTTP client, the auto-submit form route and the cache
- [Amounts](https://laranex.vercel.app/laravel-myanmar-payments/amounts): exact amounts and each gateway's rules for decimals and minimums
- [Payment Flows](https://laranex.vercel.app/laravel-myanmar-payments/payment-flows): redirect, form, QR and app payments and the typed result each returns
- [Callbacks & Status](https://laranex.vercel.app/laravel-myanmar-payments/callbacks): verifying callbacks, `PaymentStatus`, acknowledging and status checks
- [Handling Webhooks](https://laranex.vercel.app/laravel-myanmar-payments/webhooks): the recommended verify, store, acknowledge and process-once flow
- [Testing](https://laranex.vercel.app/laravel-myanmar-payments/testing): `Http::fake()`, signed callbacks, mocking the facade and following form links
- [Upgrading](https://laranex.vercel.app/laravel-myanmar-payments/upgrading): upgrading from v2.2.7 to v4.0.0
- [KBZ Pay](https://laranex.vercel.app/laravel-myanmar-payments/drivers/kbz-pay): PWA redirect, QR and in-app payments, status checks and callbacks
- [Wave Pay](https://laranex.vercel.app/laravel-myanmar-payments/drivers/wave-pay): redirect payments with line items and callbacks
- [Wave Pay Mini App](https://laranex.vercel.app/laravel-myanmar-payments/drivers/wave-pay-mini-app): payments inside the WavePay app, access tokens, the signed `makePayment` payload and callbacks
- [AYA Pay](https://laranex.vercel.app/laravel-myanmar-payments/drivers/aya-pay): the AYA Payment Gateway hosted checkout, channel discovery, status checks and callbacks
- [Yoma MMQR](https://laranex.vercel.app/laravel-myanmar-payments/drivers/yoma-mmqr): QR images, QR renewal, status checks and callbacks
- [CyberSource](https://laranex.vercel.app/laravel-myanmar-payments/drivers/cyber-source): Secure Acceptance card payments with a signed hosted checkout form
- [Results](https://laranex.vercel.app/laravel-myanmar-payments/references/results): every field of `RedirectPayment`, `FormPayment`, `QrPayment` and `AppPayment`
- [PaymentCallback & Status](https://laranex.vercel.app/laravel-myanmar-payments/references/payment-callback): every field of `PaymentCallback`, `PaymentStatusResult` and `PaymentStatus`
- [Errors](https://laranex.vercel.app/laravel-myanmar-payments/references/errors): validation, API, signature and configuration errors

## License

MIT. See [LICENSE.md](LICENSE.md).
