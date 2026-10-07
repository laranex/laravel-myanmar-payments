<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Gateways;

use Illuminate\Http\Request;
use Laranex\LaravelMyanmarPayments\Http\CallbackRequestFactory;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPay as BaseAyaPay;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayConfig;
use Laranex\PhpMyanmarPayments\AyaPay\AyaPayPaymentData;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;
use Laranex\PhpMyanmarPayments\Results\FormPayment;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;
use Psr\Http\Client\ClientInterface;

/**
 * {@see BaseAyaPay} that accepts Laravel requests and adds an auto-submit link to the form.
 */
class AyaPay extends BaseAyaPay
{
    public function __construct(
        AyaPayConfig $config,
        ?ClientInterface $httpClient,
        private readonly FormPaymentUrl $formPaymentUrl,
    ) {
        parent::__construct($config, $httpClient);
    }

    /**
     * Sign the order. Redirect the customer to `$payment->autoSubmitUrl`.
     */
    public function initiate(AyaPayPaymentData $data): FormPayment
    {
        return $this->formPaymentUrl->attach(parent::initiate($data));
    }

    public function handleCallback(CallbackRequest|Request $request): PaymentCallback
    {
        return parent::handleCallback(CallbackRequestFactory::make($request));
    }

    public function verifyRedirect(CallbackRequest|Request $request): PaymentCallback
    {
        return parent::verifyRedirect(CallbackRequestFactory::make($request));
    }
}
