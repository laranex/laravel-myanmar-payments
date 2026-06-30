<?php

namespace Laranex\LaravelMyanmarPayments\Data;

use Laranex\LaravelMyanmarPayments\Contracts\PaymentData;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

class CyberSourcePaymentData implements PaymentData
{
    public function __construct(
        public readonly string $orderId,
        public readonly int $amount,
        public readonly string $callbackUrl,
        public readonly string $currency = 'MMK',
        public readonly string $frontendUrl = '',
        public readonly string $transactionType = 'sale',
        public readonly string $cancelUrl = '',
        public readonly string $transactionUuid = '',
        public readonly string $referenceNumber = '',
    ) {}

    public function validate(): void
    {
        if ($this->orderId === '') {
            throw new PaymentException('CyberSource: orderId is required.');
        }

        if (! filter_var($this->callbackUrl, FILTER_VALIDATE_URL)) {
            throw new PaymentException('CyberSource: callbackUrl must be a valid URL.');
        }
    }
}
