<?php

namespace Laranex\LaravelMyanmarPayments\Data;

use Laranex\LaravelMyanmarPayments\Contracts\PaymentData;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

class KbzPayPaymentData implements PaymentData
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
            throw new PaymentException('KBZ Pay: orderId is required.');
        }

        if ($this->amount < 0) {
            throw new PaymentException('KBZ Pay: amount cannot be negative.');
        }

        if (! filter_var($this->callbackUrl, FILTER_VALIDATE_URL)) {
            throw new PaymentException('KBZ Pay: callbackUrl must be a valid URL.');
        }
    }
}
