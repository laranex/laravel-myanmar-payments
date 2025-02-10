# Laravel Myanmar Payments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/Laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/Laranex/laravel-myanmar-payments)
[![Total Downloads](https://img.shields.io/packagist/dt/Laranex/laravel-myanmar-payments.svg?style=flat-square)](https://packagist.org/packages/Laranex/laravel-myanmar-payments)

A Laravel Package to deal with Payment Providers from Myanmar. This package can take care of PGW payments.

Supported Payments are as follows.

- Wave Money
- 2C2P
- KBZPay
- Cybersource

### Workflows
- Client App - Server Workflow
<br>
<img src="docs/ClientAppServerPGW.png" alt="Client App - Server Workflow">

- Server Side only Workflow
<br>
<img src="docs/ServerSideOnlyPGW.png" alt="Server Side only Workflow">

## Installation

You can install the package via composer:

```bash
composer require laranex/laravel-myanmar-payments
```

## Configuration

```bash
  php artisan vendor:publish --tag="laravel-myanmar-payments"
```

## Upgrade Guide

- v1 -> v2
    - Backup & Delete the existing config/laravel-myanmar-payments.php (if only published before)
    - Publish the new config/laravel-myanmar-payments, and re-merge the old config/laravel-myanmar-payments.php
    - Update .env (KBZ Pay is supported now)

[Wave Money Configuration](https://github.com/DigitalMoneyMyanmar/wppg-documentation#23-environment)
[2c2P Configuration](https://developer.2c2p.com/docs/redirect-api-integrate-with-payment)
[KBZ Pay Configuration](https://wap.kbzpay.com/pgw/uat/api/#/en/dashboard)

## Usage

```php
use Laranex\LaravelMyanmarPayments\LaravelMyanmarPaymentsFacade;


# WAVEMONEY
# Payment Screen
LaravelMyanmarPaymentsFacade::channel('wave_money')
    ->getPaymentScreenUrl($items, $orderId, $amount, $merchantReferenceId, $backendResultUrl, $frontendResultUrl, $paymentDescription)
# Validate Response Signature
Laranex\LaravelMyanmarPayments\LaravelMyanmarPaymentsFacade::channel("wave_money")
    ->verifyWaveSignature($request)


# 2C2P
# Payment Screen
LaravelMyanmarPaymentsFacade::channel('2c2p')
    ->getPaymentScreenUrl($orderId, $amount, $noneStr, $backendResultUrl,$currencyCode, $frontendResultUrl, $paymentDescription, $userDefined)
# Parse Response Payload
Laranex\LaravelMyanmarPayments\LaravelMyanmarPaymentsFacade::channel('2c2p')
->parseJWT('jwtTokenFrom2c2cServer', $currencyCode)
# $frontendResultUrl & $paymentDescription are optional and the rest are mandatory.


#KBZ PAY
# PWA URL
LaravelMyanmarPaymentsFacade::channel("kbz_pay.pwaapp")
    ->getPaymentScreenUrl($orderId, $amount, $nonceStr,  $backendResultUrl)
# QR Code
LaravelMyanmarPaymentsFacade::channel("kbz_pay.qr")
    ->getPaymentScreenUrl($orderId, $amount, $nonceStr,  $backendResultUrl)

# In App
LaravelMyanmarPaymentsFacade::channel("kbz_pay.app")->getPaymentData($orderId, $amount, $nonceStr, $backendResultUrl);

# $nonceStr should be at least 32 characters long, uppercase & numbers according to KbzPay Documentation

# Validate Response Signature
LaravelMyanmarPaymentsFacade::channel("kbz_pay.qr")
    verifySignature($request)

#Cybersource
# Secure Acceptance
LaravelMyanmarPaymentsFacade::channel("cyber_source.secure_acceptance")
    ->getPaymentData($transactionId, $referenceNumber, $amount)
    
# Validate Request Signature
LaravelMyanmarPaymentsFacade::channel("cyber_source.secure_acceptance")
    ->verifySignature($request)
```


For more api options, you can read the composition of the
- Wave Money function [here](src/WaveMoney.php)
- 2c2P [here](src/TwoCTwoP.php)
- KBZ Pay
  - [PWA](src/KbzPayPwa.php)
  - [QR](src/KbzPayQr.php)
  - [InApp](src/KbzPayApp.php)
- Cybersource 
  - [Secure Acceptance](src/CyberSourceSecureAcceptance.php)

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email naythukhant644@gmail.com instead of using the issue tracker.

## Contributors

- [Nay Thu Khant](https://github.com/naythukhant)
- [Thin Aung](https://github.com/makgsoewar)
- [Pai Soe Htike](https://github.com/paisoedev)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


