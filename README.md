# Laravel Myanmar Payments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-myanmar-payments)
[![Total Downloads](https://img.shields.io/packagist/dt/laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/laranex/laravel-myanmar-payments)

A Laravel package for Myanmar payment gateways with a unified API. Supports PHP 8.1+ and Laravel 10–13.

**Supported gateways:**
- KBZ Pay (PWA, QR, In-App)
- Wave Money
- AYA Payment Gateway
- CyberSource Secure Acceptance

## Installation

```bash
composer require laranex/laravel-myanmar-payments
```

Publish the config file:

```bash
php artisan vendor:publish --tag="myanmar-payments-config"
```

## Configuration

Add the relevant keys to your `.env`:

```env
# KBZ Pay
KBZ_PAY_BASE_URL=http://api.kbzpay.com/payment/gateway/uat
KBZ_PAY_MERCHANT_NAME=
KBZ_PAY_MERCHANT_CODE=
KBZ_PAY_APP_ID=
KBZ_PAY_APP_KEY=
KBZ_PAY_PWA_BASE_REDIRECT_URL=https://static.kbzpay.com/pgw/uat/pwa/#

# Wave Money
WAVE_MONEY_BASE_URL=https://testpayments.wavemoney.io:8107
WAVE_MONEY_MERCHANT_NAME=
WAVE_MONEY_MERCHANT_ID=
WAVE_MONEY_SECRET_KEY=
WAVE_MONEY_TIME_TO_LIVE_IN_SECONDS=300

# AYA PGW
AYA_PGW_BASE_URL=
AYA_PGW_APP_KEY=
AYA_PGW_APP_SECRET=

# CyberSource
CYBER_SOURCE_BASE_URL=
CYBER_SOURCE_PROFILE_ID=
CYBER_SOURCE_ACCESS_KEY=
CYBER_SOURCE_SECRET_KEY=
```

## Usage

All drivers share the same three methods: `initiate()`, `verify()`, and `handleCallback()`. Each driver has its own typed data class with built-in validation.

### Initiating a Payment

```php
use Laranex\LaravelMyanmarPayments\Data\KbzPayPaymentData;
use Laranex\LaravelMyanmarPayments\MyanmarPaymentsFacade as MyanmarPayments;

$result = MyanmarPayments::driver('kbzpay.pwa')->initiate(new KbzPayPaymentData(
    orderId: 'ORD-001',
    amount: 10000,        // in MMK
    callbackUrl: route('payment.callback'),
));

if ($result->requiresRedirect()) {
    return redirect($result->redirectUrl);
}

if ($result->requiresFormPost()) {
    // render an auto-submit HTML form posting $result->formData to $result->formUrl
}
```

### Checking Payment Status

```php
$result = MyanmarPayments::driver('kbzpay.pwa')->verify('ORD-001');

if ($result->isSuccessful()) {
    // fulfill the order
}
```

### Handling Callbacks

```php
Route::post('/payment/callback', function (Request $request) {
    $result = MyanmarPayments::driver('kbzpay.pwa')->handleCallback($request->all());

    if ($result->isSuccessful()) {
        // fulfill the order using $result->orderId / $result->transactionId
    }
});
```

### PaymentResult Reference

| Property | Type | Description |
|---|---|---|
| `status` | `PaymentStatus` | `Initiated`, `Pending`, `Successful`, `Failed`, `Cancelled` |
| `redirectUrl` | `?string` | URL to redirect the user to (PWA / Wave Money) |
| `qrCode` | `?string` | QR code data string (KBZ Pay QR) |
| `appData` | `?array` | Signed payload for mobile SDK (KBZ Pay App) |
| `formUrl` | `?string` | POST target URL (AYA PGW / CyberSource) |
| `formData` | `?array` | Fields for the form POST |
| `transactionId` | `?string` | Gateway transaction ID |
| `orderId` | `?string` | Your order ID echoed back |
| `raw` | `array` | Raw gateway response |

Helper methods: `isSuccessful()`, `isPending()`, `isInitiated()`, `isFailed()`, `isCancelled()`, `requiresRedirect()`, `requiresFormPost()`

---

## Driver Reference

### KBZ Pay

Available drivers: `kbzpay.pwa`, `kbzpay.qr`, `kbzpay.app`

```php
use Laranex\LaravelMyanmarPayments\Data\KbzPayPaymentData;

// PWA — returns redirectUrl
MyanmarPayments::driver('kbzpay.pwa')->initiate(new KbzPayPaymentData(
    orderId: 'ORD-001',
    amount: 10000,
    callbackUrl: route('payment.callback'),
));

// QR — returns qrCode string to render as a QR image
MyanmarPayments::driver('kbzpay.qr')->initiate(new KbzPayPaymentData(...));

// In-App — returns appData array for mobile SDK
MyanmarPayments::driver('kbzpay.app')->initiate(new KbzPayPaymentData(...));

// Query order status
MyanmarPayments::driver('kbzpay.pwa')->verify('ORD-001');

// Callback — pass $request->all(); driver extracts the nested Request key
MyanmarPayments::driver('kbzpay.pwa')->handleCallback($request->all());
```

---

### Wave Money

Driver: `wave_money`

Requires `items` — an array of `['name' => string, 'amount' => int]` entries.

```php
use Laranex\LaravelMyanmarPayments\Data\WaveMoneyPaymentData;

MyanmarPayments::driver('wave_money')->initiate(new WaveMoneyPaymentData(
    orderId: 'ORD-001',
    amount: 5000,
    callbackUrl: route('payment.callback'),
    items: [
        ['name' => 'Product A', 'amount' => 3000],
        ['name' => 'Product B', 'amount' => 2000],
    ],
    frontendUrl: route('payment.success'),         // optional
    merchantReferenceId: 'REF-001',               // optional, defaults to orderId
));

// Callback
MyanmarPayments::driver('wave_money')->handleCallback($request->all());
```

> Wave Money does not expose an order query API — use `handleCallback()` to determine the final status.

---

### AYA Payment Gateway

Driver: `aya_pgw`

Returns a form POST result. Render an auto-submit form to `$result->formUrl` with `$result->formData`. The callback URL is configured in the AYA merchant portal, not in the request.

```php
use Laranex\LaravelMyanmarPayments\Data\AyaPgwPaymentData;

$result = MyanmarPayments::driver('aya_pgw')->initiate(new AyaPgwPaymentData(
    orderId: 'ORD-001',
    amount: 8000,
    channel: 'AYA_PAY',
    method: 'WALLET',
    currencyCode: 104,           // optional, 104 = MMK
    frontendUrl: route('payment.success'), // optional
    userRefs: ['ref1'],          // optional, up to 5 values
));

// Query
MyanmarPayments::driver('aya_pgw')->verify('ORD-001');

// Callback
MyanmarPayments::driver('aya_pgw')->handleCallback($request->only('payload', 'checkSum'));
```

---

### CyberSource Secure Acceptance

Driver: `cyber_source`

Returns a form POST result.

```php
use Laranex\LaravelMyanmarPayments\Data\CyberSourcePaymentData;

$result = MyanmarPayments::driver('cyber_source')->initiate(new CyberSourcePaymentData(
    orderId: 'ORD-001',
    amount: 20000,
    callbackUrl: route('payment.callback'),
    frontendUrl: route('payment.success'),  // optional
    transactionType: 'sale',                // optional, defaults to 'sale'
    cancelUrl: route('payment.cancel'),     // optional
));

// Callback
MyanmarPayments::driver('cyber_source')->handleCallback($request->all());
```

> CyberSource does not expose an order query API — use `handleCallback()`.

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover a security issue, please email naythukhant644@gmail.com instead of using the issue tracker.

## Contributors

- [Nay Thu Khant](https://github.com/naythukhant)
- [Thin Aung](https://github.com/makgsoewar)
- [Pai Soe Htike](https://github.com/paisoedev)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
