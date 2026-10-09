# Release Notes

## v4.0.0 - Unreleased

The rewrite that was developed as v3 was never released; it ships as v4.0.0.

### Changed
- Requires PHP 8.1+ and supports Laravel 10 through 13.
- Rebuilt on the official Laravel package skeleton (Pest, PHPStan, Pint, Testbench workbench, GitHub Actions matrix).
- Rebuilt on the framework-agnostic `laranex/php-myanmar-payments` package.
- Requires `laranex/php-myanmar-payments` ^4.0, `guzzlehttp/guzzle` ^7.4 or ^8 and `guzzlehttp/psr7` ^2.1 or ^3 (Laravel 10 only suggests Guzzle; the PSR-17 factories come from `guzzlehttp/psr7` 2+)
- Typed accessors per gateway: `MyanmarPayments::kbzPay()`, `waveMoney()`, `ayaPay()`, `yomaMmqr()`, `cyberSource()`
- One typed request class per gateway and one result class per flow, replacing `RequestPaymentResult::$value`
- Callbacks accept the Laravel `Request` directly and return `PaymentCallback` with a gateway-independent `PaymentStatus`; `MyanmarPayments::acknowledge()` returns the response each gateway expects (an empty 200 without a callback)
- `MyanmarPayments::gateway($name)` and `MyanmarPayments::handleCallback($gateway, $request)` resolve a gateway by its callback route name (`kbz-pay`, `wave-money`, `aya-pay`, `yoma-mmqr`, `cyber-source`, listed by `gateways()`), so one route can serve every gateway, like goravel-myanmar-payments and nestjs-myanmar-payments
- Added AYA `services()`, AYA `verifyRedirect()` and status checks for KBZ Pay, AYA and Yoma MMQR
- Callback, return and cancel URLs only need to be valid absolute http or https URLs (no HTTPS-only or port-443 rule); gateways may still require HTTPS in production
- Wave Money's sandbox (`WAVE_MONEY_SANDBOX=true`) uses `https://preprodpayments.wavemoney.io:8107`, with checkout at `https://preprodpayments.wavemoney.io/authenticate`
- Fixed callback verification for KBZ Pay, Wave Money and AYA against their official specifications
- The facade moved from `Laranex\LaravelMyanmarPayments\LaravelMyanmarPaymentsFacade` (alias `LaravelMyanmarPayments`) to `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments` (alias `MyanmarPayments`)
- The service provider is now `Laranex\LaravelMyanmarPayments\MyanmarPaymentsServiceProvider` and the config file is `config/myanmar-payments.php` (publish tag `myanmar-payments-config`)
- Renamed the `aya_pgw` config key to `aya_pay` (env `AYA_PAY_*`, with `AYA_PGW_*` still read as a fallback) and KBZ Pay's `base_url` / `pwa.base_redirect_url` keys to `api_url` / `pwa_url` (env `KBZ_PAY_BASE_URL` and `KBZ_PAY_PWA_BASE_REDIRECT_URL` are unchanged)
- Removed KBZ Pay's refund query (`queryOrder()` with `$refundRequestNo`); refunds are out of scope, and `kbzPay()->status($orderId)` replaces the order query
- AYA Pay and CyberSource return a `FormPayment` with an `autoSubmitUrl` served by the package's form route (`myanmar-payments/form`, configurable under `form_route`)
- `PaymentStatus::Cancelled` (`'cancelled'`) from the v4 pre-releases is now `PaymentStatus::Canceled` (`'canceled'`), with no alias; gateway status literals such as Wave Money's `PAYMENT_REQUEST_CANCELLED` are unchanged
- The service provider and the form route file use the application's `configPath()` and the `Config` facade instead of the `config_path()` and `config()` helpers, which only `laravel/framework` defines, so the package runs on the `illuminate/*` components it requires.

### Upgrading
- Require PHP 8.1+ and Laravel 10+, then `composer require laranex/laravel-myanmar-payments:^4.0`.
- Replace the `LaravelMyanmarPayments` facade (and alias) with `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments`; call gateways through `MyanmarPayments::kbzPay()`, `waveMoney()`, `ayaPay()`, `yomaMmqr()` and `cyberSource()`.
- Build payments from the typed data classes in `Laranex\PhpMyanmarPayments\<Gateway>\<Gateway>PaymentData` and read the typed results (`RedirectPayment`, `FormPayment`, `QrPayment`, `AppPayment`) instead of `RequestPaymentResult::$value`.
- Pass the incoming `Illuminate\Http\Request` to `handleCallback()`, branch on `$callback->status` (`PaymentStatus`) and return `MyanmarPayments::acknowledge($callback)` from the callback route.
- Re-publish the configuration with `php artisan vendor:publish --tag="myanmar-payments-config"` and move your credentials to the new `kbz_pay`, `wave_money`, `aya_pay`, `yoma_mmqr` and `cyber_source` keys. `AYA_PGW_*` environment variables are still read as a fallback.
- Rename `aya_pgw` to `aya_pay`, and `kbz_pay.base_url` / `kbz_pay.pwa.base_redirect_url` to `kbz_pay.api_url` / `kbz_pay.pwa_url`, in any published or overridden config.
- Replace `channel('kbz_pay.*')->queryOrder()` calls with `MyanmarPayments::kbzPay()->status($orderId)`; refund lookups are no longer provided.
- Coming from `v4.0.0-alpha.1`: rename `PaymentStatus::Cancelled` to `PaymentStatus::Canceled` and update any stored `'cancelled'` status values to `'canceled'`.
