<?php

namespace Laranex\LaravelMyanmarPayments\Data\Request;

use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;

class KbzPayRequestPaymentData implements RequestPaymentData
{
    public function __construct(
        public readonly string $orderId,
        public readonly int $amount,
        public readonly string $callbackUrl,
        public readonly string $currency = 'MMK',
        public readonly string $nonceStr = '',
    ) {}

    public function validate(): void
    {
        if ($this->orderId === '') {
            throw new InvalidArgumentException('orderId is required.');
        }

        if ($this->amount < 0) {
            throw new InvalidArgumentException('amount cannot be negative.');
        }

        if (! filter_var($this->callbackUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('callbackUrl must be a valid URL.');
        }
    }
}
