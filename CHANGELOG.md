# Release Notes

## v4.0.0 - Unreleased

The rewrite that was developed as v3 was never released; it ships as v4.0.0.

### Changed
- Requires PHP 8.1+ and supports Laravel 10 through 13.
- Rebuilt on the official Laravel package skeleton (Pest, PHPStan, Pint, Testbench workbench, GitHub Actions matrix).
- Rebuilt on the framework-agnostic `laranex/php-myanmar-payments` package.
- Requires `laranex/php-myanmar-payments` ^4.0, `guzzlehttp/guzzle` ^7.4 or ^8 and `guzzlehttp/psr7` ^2.1 or ^3 (Laravel 10 only suggests Guzzle; the PSR-17 factories come from `guzzlehttp/psr7` 2+)
- Typed accessors per gateway: `MyanmarPayments::kbzPay()`, `wavePay()`, `ayaPay()`, `yomaMmqr()`, `cyberSource()`
- One typed request class per gateway and one result class per flow, replacing `RequestPaymentResult::$value`
- Callbacks accept the Laravel `Request` directly and return `PaymentCallback` with a gateway-independent `PaymentStatus`; `MyanmarPayments::acknowledge()` returns the response each gateway expects (an empty 200 without a callback)
- `MyanmarPayments::gateway($name)` and `MyanmarPayments::handleCallback($gateway, $request)` resolve a gateway by its callback route name (`kbz-pay`, `wave-pay`, `aya-pay`, `yoma-mmqr`, `cyber-source`, listed by `gateways()`), so one route can serve every gateway, like goravel-myanmar-payments and nestjs-myanmar-payments
- Added AYA `services()`, AYA `verifyRedirect()` and status checks for KBZ Pay, AYA and Yoma MMQR
- Callback, return and cancel URLs only need to be valid absolute http or https URLs (no HTTPS-only or port-443 rule); gateways may still require HTTPS in production
- Wave Pay requests payments at `WAVE_PAY_BASE_URL`'s `/payment` and sends the customer to the new `WAVE_PAY_BASE_REDIRECT_URL`'s `/authenticate` (UAT: `https://preprodpayments.wavemoney.io:8107` for both) as JSON, following the Pay with Wave API spec v1.3; `WavePayPaymentData` takes the optional `additionalField3` to `additionalField5`
- Wave Money is now called Wave Pay: the `wave_money` config key is `wave_pay`, `waveMoney()` is `wavePay()` and its callback route name is `wave-pay`
- Fixed callback verification for KBZ Pay, Wave Pay and AYA against their official specifications
- The facade moved from `Laranex\LaravelMyanmarPayments\LaravelMyanmarPaymentsFacade` (alias `LaravelMyanmarPayments`) to `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments` (alias `MyanmarPayments`)
- The service provider is now `Laranex\LaravelMyanmarPayments\MyanmarPaymentsServiceProvider` and the config file is `config/myanmar-payments.php` (publish tag `myanmar-payments-config`)
- Renamed the `aya_pgw` config key to `aya_pay` (env `AYA_PAY_*`; the `AYA_PGW_*` variables are no longer read) and KBZ Pay's `base_url` / `pwa.base_redirect_url` keys to `api_url` / `pwa_url` (env `KBZ_PAY_BASE_URL` and `KBZ_PAY_PWA_BASE_REDIRECT_URL` are unchanged)
- Removed KBZ Pay's refund query (`queryOrder()` with `$refundRequestNo`); refunds are out of scope, and `kbzPay()->status($orderId)` replaces the order query
- AYA Pay and CyberSource return a `FormPayment` with an `autoSubmitUrl` served by the package's form route (`myanmar-payments/form`, configurable under `form_route`)
- The form route runs without middleware by default (no session is needed), sends `Referrer-Policy: strict-origin` and adds the `Vite::useCspNonce()` nonce to its script; gateway calls return redirects instead of following them
- Every gateway URL is required, as AYA's and CyberSource's were in v2: `KBZ_PAY_BASE_URL`, `KBZ_PAY_PWA_BASE_REDIRECT_URL`, `WAVE_PAY_BASE_URL`, `WAVE_PAY_BASE_REDIRECT_URL`, `AYA_PAY_BASE_URL`, `YOMA_MMQR_BASE_URL` and `CYBER_SOURCE_BASE_URL`, set to production's or the gateway's UAT URLs
- `MYANMAR_PAYMENTS_HTTP_TIMEOUT` (optional, 30 seconds by default), `MYANMAR_PAYMENTS_CACHE_STORE` (optional), `MYANMAR_PAYMENTS_FORM_TTL_MINUTES` (required while the form route is enabled) and the `form_route` options are new
- `KBZ_PAY_TIME_TO_LIVE_IN_MINUTES` (new, 1 to 120) and `WAVE_PAY_TIME_TO_LIVE_IN_SECONDS` are required: how long a customer has to pay; a payment's own `timeToLiveMinutes` (KBZ) or `timeToLiveSeconds` (Wave) overrides them. A Wave Pay payment can also override `WAVE_PAY_MERCHANT_NAME` with its own `merchantName`
- Every other setting of the gateways you use is required and has no default too, including `WAVE_PAY_MERCHANT_NAME` (no `APP_NAME` fallback), `YOMA_MMQR_API_VERSION` and `YOMA_MMQR_WEBHOOK_SECRET` (every Yoma callback must carry it in `X-Webhook-Secret`); CyberSource payments take `currency`, `locale` and `transactionType`. A missing or invalid setting throws a `ConfigurationException` naming it (public `gateway` and `key`)
- Every exception extends `PaymentException`: `InvalidPaymentDataException`, `ApiException`, `SignatureVerificationException` and `ConfigurationException`

### Upgrading
- Require PHP 8.1+ and Laravel 10+, then `composer require laranex/laravel-myanmar-payments:^4.0`.
- Replace the `LaravelMyanmarPayments` facade (and alias) with `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments`; call gateways through `MyanmarPayments::kbzPay()`, `wavePay()`, `ayaPay()`, `yomaMmqr()` and `cyberSource()` (`waveMoney()` becomes `wavePay()`).
- Build payments from the typed data classes in `Laranex\PhpMyanmarPayments\<Gateway>\<Gateway>PaymentData` and read the typed results (`RedirectPayment`, `FormPayment`, `QrPayment`, `AppPayment`) instead of `RequestPaymentResult::$value`.
- Pass the incoming `Illuminate\Http\Request` to `handleCallback()`, branch on `$callback->status` (`PaymentStatus`) and return `MyanmarPayments::acknowledge($callback)` from the callback route.
- Back up `config/laravel-myanmar-payments.php`, delete it and publish the new one with `php artisan vendor:publish --tag="myanmar-payments-config"`; rename the `AYA_PGW_*` environment variables to `AYA_PAY_*` and the `WAVE_MONEY_*` environment variables to `WAVE_PAY_*`.
- Replace `channel('kbz_pay.*')->queryOrder()` calls with `MyanmarPayments::kbzPay()->status($orderId)`; refund lookups are no longer provided.
- Set the newly required settings: `MYANMAR_PAYMENTS_FORM_TTL_MINUTES`, `WAVE_PAY_MERCHANT_NAME`, `YOMA_MMQR_API_VERSION`, `YOMA_MMQR_WEBHOOK_SECRET`, the time to live settings and every gateway URL (production's, or the UAT URLs listed in the docs).
