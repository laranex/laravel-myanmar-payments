# Laravel Myanmar Payments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-myanmar-payments)
[![Tests](https://img.shields.io/github/actions/workflow/status/laranex/laravel-myanmar-payments/tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/laranex/laravel-myanmar-payments/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-myanmar-payments)
[![License](https://img.shields.io/packagist/l/laranex/laravel-myanmar-payments.svg?style=flat-square)](LICENSE.md)

Laravel integration for Myanmar payment gateways: KBZ Pay (PWA, QR, In-App), Wave Money, AYA Payment Gateway, Yoma MMQR and CyberSource Secure Acceptance. Every gateway takes a typed request object and returns a typed result, callbacks are verified straight from the Laravel `Request`, and the auto-submitting payment form for AYA Pay and CyberSource is served for you. Built on the framework-agnostic [`laranex/php-myanmar-payments`](https://github.com/laranex/php-myanmar-payments) for Laravel developers who need to accept payments in Myanmar.

## Documentation

Full documentation lives at **[laranex.vercel.app/laravel-myanmar-payments](https://laranex.vercel.app/laravel-myanmar-payments)**.

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11, 12 or 13

## Installation

```bash
composer require laranex/laravel-myanmar-payments
```

Publish the configuration file to change gateway credentials, the HTTP timeout, the cache store or the form route:

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

The other gateways work the same way through `waveMoney()`, `ayaPay()`, `yomaMmqr()` and `cyberSource()`; see the [documentation](https://laranex.vercel.app/laravel-myanmar-payments) for every flow, status check and callback.

## Built for humans and AI agents

The documentation is written for developers, and the package ships an agent skill so AI coding agents use it the way it's meant to be used.

- **Laravel Boost** installs the skill automatically: run `php artisan boost:install` (or `boost:update`).
- **Any other agent** (Claude Code, Codex, Cursor and others): `npx skills add laranex/laravel-myanmar-payments`.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Nay Thu Khant](https://github.com/NayThuKhant)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
