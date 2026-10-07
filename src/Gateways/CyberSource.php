<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Gateways;

use Illuminate\Http\Request;
use Laranex\LaravelMyanmarPayments\Http\CallbackRequestFactory;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;
use Laranex\PhpMyanmarPayments\CyberSource\CyberSource as BaseCyberSource;
use Laranex\PhpMyanmarPayments\CyberSource\CyberSourceConfig;
use Laranex\PhpMyanmarPayments\CyberSource\CyberSourcePaymentData;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;
use Laranex\PhpMyanmarPayments\Results\FormPayment;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;

/**
 * {@see BaseCyberSource} that accepts Laravel requests and adds an auto-submit link to the form.
 */
class CyberSource extends BaseCyberSource
{
    public function __construct(
        CyberSourceConfig $config,
        private readonly FormPaymentUrl $formPaymentUrl,
    ) {
        parent::__construct($config);
    }

    /**
     * Sign the payment. Redirect the customer to `$payment->autoSubmitUrl`.
     */
    public function initiate(CyberSourcePaymentData $data): FormPayment
    {
        return $this->formPaymentUrl->attach(parent::initiate($data));
    }

    public function handleCallback(CallbackRequest|Request $request): PaymentCallback
    {
        return parent::handleCallback(CallbackRequestFactory::make($request));
    }
}
