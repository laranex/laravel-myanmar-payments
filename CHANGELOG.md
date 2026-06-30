# Changelog

All notable changes to `laravel-myanmar-payments` will be documented in this file.

## 2.0.0

- Complete rewrite with a unified API (`initiate`, `verify`, `handleCallback`) across all drivers
- Dedicated data classes per driver (`KbzPayPaymentData`, `WaveMoneyPaymentData`, `AyaPgwPaymentData`, `CyberSourcePaymentData`) each with built-in `validate()`
- `KbzPayTradeType` enum replaces raw trade type strings
- Removed 2C2P support
- Removed `firebase/php-jwt` dependency (native PHP JWT dropped alongside 2C2P)
- Requires PHP ^8.1 and Laravel 10–13
- Full Pest test suite added

## 1.0.6

- Optional userDefined fields for 2c2p supported

## 1.0.5

- Response validator for Wave Money supported

## 1.0.1

- Initial KBZ Pay and Wave Money support
