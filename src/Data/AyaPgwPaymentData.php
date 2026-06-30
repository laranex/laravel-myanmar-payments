<?php

namespace Laranex\LaravelMyanmarPayments\Data;

use Laranex\LaravelMyanmarPayments\Contracts\PaymentData;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

class AyaPgwPaymentData implements PaymentData
{
    public function __construct(
        public readonly string $orderId,
        public readonly int $amount,
        public readonly string $channel,
        public readonly string $method,
        public readonly int $currencyCode = 104,
        public readonly string $frontendUrl = '',
        public readonly string $description = '',
        public readonly array $userRefs = [],
    ) {}

    public function validate(): void
    {
        if ($this->orderId === '') {
            throw new PaymentException('AYA PGW: orderId is required.');
        }

        if ($this->channel === '') {
            throw new PaymentException('AYA PGW: channel is required.');
        }

        if ($this->method === '') {
            throw new PaymentException('AYA PGW: method is required.');
        }

        if (count($this->userRefs) > 5) {
            throw new PaymentException('AYA PGW: a maximum of 5 user reference fields are allowed.');
        }
    }
}
