<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Gateways;

use Illuminate\Http\Request;
use Laranex\LaravelMyanmarPayments\Http\CallbackRequestFactory;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;
use Laranex\PhpMyanmarPayments\KbzPay\KbzPay as BaseKbzPay;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;

/**
 * {@see BaseKbzPay} that also accepts Laravel requests.
 */
class KbzPay extends BaseKbzPay
{
    public function handleCallback(CallbackRequest|Request $request): PaymentCallback
    {
        return parent::handleCallback(CallbackRequestFactory::make($request));
    }
}
