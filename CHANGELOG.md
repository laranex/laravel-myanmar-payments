# Release Notes

## [Unreleased](https://github.com/laranex/laravel-myanmar-payments/compare/v2.2.5...3.x)

### v3.0.0

- Rebuilt on the framework-agnostic `laranex/php-myanmar-payments` package
- Typed accessors per gateway: `MyanmarPayments::kbzPay()`, `waveMoney()`, `ayaPay()`, `yomaMmqr()`, `cyberSource()`
- One typed request class per gateway and one result class per flow, replacing `RequestPaymentResult::$value`
- Callbacks accept the Laravel `Request` directly and return `PaymentCallback` with a gateway-independent `PaymentStatus`; `MyanmarPayments::acknowledge()` returns the response each gateway expects
- Added Yoma MMQR, AYA `services()`, AYA `verifyRedirect()` and status checks for KBZ Pay, AYA and Yoma
- Fixed callback verification for KBZ Pay, Wave Money and AYA against their official specifications
- Removed 2C2P support
- The facade moved from `Laranex\LaravelMyanmarPayments\LaravelMyanmarPaymentsFacade` (alias `LaravelMyanmarPayments`) to `Laranex\LaravelMyanmarPayments\Facades\MyanmarPayments` (alias `MyanmarPayments`)
