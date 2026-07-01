<?php

namespace Laranex\LaravelMyanmarPayments\Data\Request;

use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;

class CyberSourceRequestPaymentData implements RequestPaymentData
{
    public function __construct(
        public readonly string $transactionId,
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
        if ($this->transactionId === '') {
            throw new InvalidArgumentException('transactionId is required.');
        }

        if (! filter_var($this->callbackUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('callbackUrl must be a valid URL.');
        }
    }
}
