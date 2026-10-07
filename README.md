<div align="center">
    <h1>Laravel Myanmar Payments</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/laranex/laravel-myanmar-payments"><img src="https://img.shields.io/packagist/v/laranex/laravel-myanmar-payments.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/laranex/laravel-myanmar-payments"><img src="https://img.shields.io/packagist/php-v/laranex/laravel-myanmar-payments.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/laranex/laravel-myanmar-payments"><img src="https://badge.laravel.cloud/badge/laranex/laravel-myanmar-payments?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/laranex/laravel-myanmar-payments/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/laranex/laravel-myanmar-payments/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/laranex/laravel-myanmar-payments"><img src="https://img.shields.io/packagist/dt/laranex/laravel-myanmar-payments.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Laravel integration for Myanmar payment gateways: KBZ Pay (PWA, QR, In-App), Wave Money, AYA Payment Gateway, Yoma MMQR and CyberSource Secure Acceptance.

Every gateway takes a typed request object and returns a typed result, so your IDE shows exactly what to pass and what comes back. Built on the framework-agnostic [`laranex/php-myanmar-payments`](https://github.com/laranex/php-myanmar-payments).

**Documentation:** [laranex.vercel.app](https://laranex.vercel.app/laravel-myanmar-payments)

> Requires PHP 8.1+ and Laravel 10 to 13.

## Installation

You can install the package via Composer:

```bash
composer require laranex/laravel-myanmar-payments
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="myanmar-payments"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="myanmar-payments-config"
```

## Usage

```php
use Illuminate\Http\Request;
use Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments;
use Laranex\PhpMyanmarPayments\KbzPay\KbzPayPaymentData;

// Start a payment: one result class per flow (RedirectPayment, FormPayment, QrPayment, AppPayment)
$payment = MyanmarPayments::kbzPay()->pwa(new KbzPayPaymentData(
    orderId: 'ORDER_1',
    amount: 1000,
    callbackUrl: route('payments.kbz.callback'),
));

return redirect($payment->url);

// Handle the callback: verified, with a gateway-independent PaymentStatus
Route::post('/payments/kbz/callback', function (Request $request) {
    $callback = MyanmarPayments::kbzPay()->handleCallback($request);

    if ($callback->isSuccessful()) {
        // compare $callback->amount with your order, then fulfil $callback->orderId
    }

    return MyanmarPayments::acknowledge($callback); // KBZ Pay expects a plain "success"
})->name('payments.kbz.callback'); // exclude this route from CSRF verification
```

| Gateway | Start a payment | Result | Status check |
|---|---|---|---|
| KBZ Pay | `kbzPay()->pwa()`, `->qr()`, `->app()` | `RedirectPayment`, `QrPayment`, `AppPayment` | `kbzPay()->status($orderId)` |
| Wave Money | `waveMoney()->initiate()` | `RedirectPayment` | (none, callback only) |
| AYA Payment Gateway | `ayaPay()->initiate()` | `FormPayment` | `ayaPay()->status($orderId)` |
| Yoma MMQR | `yomaMmqr()->initiate()`, `->renewQr()` | `QrPayment` | `yomaMmqr()->status($reference)` |
| CyberSource | `cyberSource()->initiate()` | `FormPayment` | (none, callback only) |

Every gateway verifies its callbacks with `handleCallback($request)` and returns a `PaymentCallback`.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Myanmar Payments! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Nay Thu Khant](https://github.com/laranex)
- [All Contributors](../../contributors)

## License

Laravel Myanmar Payments is open-sourced software licensed under the [MIT license](LICENSE.md).
