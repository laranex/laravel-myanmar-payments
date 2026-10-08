# Release Notes

## v4.0.0 - Unreleased

The rewrite that was developed as v3 was never released; it ships as v4.0.0.

### Changed
- Requires PHP 8.1+ and supports Laravel 10 through 13.
- Rebuilt on the official Laravel package skeleton (Pest, PHPStan, Pint, Testbench workbench, GitHub Actions matrix).
- Rebuilt on the framework-agnostic `laranex/php-myanmar-payments` package
- Typed accessors per gateway: `MyanmarPayments::kbzPay()`, `waveMoney()`, `ayaPay()`, `yomaMmqr()`, `cyberSource()`
- One typed request class per gateway and one result class per flow, replacing `RequestPaymentResult::$value`
- Callbacks accept the Laravel `Request` directly and return `PaymentCallback` with a gateway-independent `PaymentStatus`; `MyanmarPayments::acknowledge()` returns the response each gateway expects
- Added Yoma MMQR, AYA `services()`, AYA `verifyRedirect()` and status checks for KBZ Pay, AYA and Yoma
- Fixed callback verification for KBZ Pay, Wave Money and AYA against their official specifications
- Removed 2C2P support
- The facade moved from `Laranex\LaravelMyanmarPayments\LaravelMyanmarPaymentsFacade` (alias `LaravelMyanmarPayments`) to `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments` (alias `MyanmarPayments`)
- The service provider is now `Laranex\LaravelMyanmarPayments\MyanmarPaymentsServiceProvider` and the config file is `config/myanmar-payments.php` (publish tag `myanmar-payments-config`)
- AYA Pay and CyberSource return a `FormPayment` with an `autoSubmitUrl` served by the package's form route (`myanmar-payments/form`, configurable under `form_route`)

### Upgrading
- Require PHP 8.1+ and Laravel 10+, then `composer require laranex/laravel-myanmar-payments:^4.0`.
- Replace the `LaravelMyanmarPayments` facade (and alias) with `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments`; call gateways through `MyanmarPayments::kbzPay()`, `waveMoney()`, `ayaPay()`, `yomaMmqr()` and `cyberSource()`.
- Build payments from the typed data classes in `Laranex\PhpMyanmarPayments\<Gateway>\<Gateway>PaymentData` and read the typed results (`RedirectPayment`, `FormPayment`, `QrPayment`, `AppPayment`) instead of `RequestPaymentResult::$value`.
- Pass the incoming `Illuminate\Http\Request` to `handleCallback()`, branch on `$callback->status` (`PaymentStatus`) and return `MyanmarPayments::acknowledge($callback)` from the callback route.
- Re-publish the configuration with `php artisan vendor:publish --tag="myanmar-payments-config"` and move your credentials to the new `kbz_pay`, `wave_money`, `aya_pay`, `yoma_mmqr` and `cyber_source` keys. `AYA_PGW_*` environment variables are still read as a fallback.
- Remove any 2C2P integration; it is no longer provided.
