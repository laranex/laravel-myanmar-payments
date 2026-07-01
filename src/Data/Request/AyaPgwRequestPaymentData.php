<?php

namespace Laranex\LaravelMyanmarPayments\Data\Request;

use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;

class AyaPgwRequestPaymentData implements RequestPaymentData
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
            throw new InvalidArgumentException('orderId is required.');
        }

        if ($this->channel === '') {
            throw new InvalidArgumentException('channel is required.');
        }

        if ($this->method === '') {
            throw new InvalidArgumentException('method is required.');
        }

        if (count($this->userRefs) > 5) {
            throw new InvalidArgumentException('a maximum of 5 user reference fields are allowed.');
        }
    }
}
