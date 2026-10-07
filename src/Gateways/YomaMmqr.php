<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Gateways;

use Illuminate\Http\Request;
use Laranex\LaravelMyanmarPayments\Http\CallbackRequestFactory;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;
use Laranex\PhpMyanmarPayments\YomaMmqr\YomaMmqr as BaseYomaMmqr;

/**
 * {@see BaseYomaMmqr} that also accepts Laravel requests.
 */
class YomaMmqr extends BaseYomaMmqr
{
    public function handleCallback(CallbackRequest|Request $request): PaymentCallback
    {
        return parent::handleCallback(CallbackRequestFactory::make($request));
    }
}
