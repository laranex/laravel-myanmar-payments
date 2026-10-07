<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Gateways;

use Illuminate\Http\Request;
use Laranex\LaravelMyanmarPayments\Http\CallbackRequestFactory;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;
use Laranex\PhpMyanmarPayments\WaveMoney\WaveMoney as BaseWaveMoney;

/**
 * {@see BaseWaveMoney} that also accepts Laravel requests.
 */
class WaveMoney extends BaseWaveMoney
{
    public function handleCallback(CallbackRequest|Request $request): PaymentCallback
    {
        return parent::handleCallback(CallbackRequestFactory::make($request));
    }
}
