<?php

namespace Laranex\LaravelMyanmarPayments\Data;

use Laranex\LaravelMyanmarPayments\Contracts\PaymentData;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

class WaveMoneyPaymentData implements PaymentData
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $callbackUrl,
        /** @var array<array{name: string, amount: int}> */
        public readonly array $items = [],
        public readonly string $merchantReferenceId = '',
        public readonly string $frontendUrl = '',
        public readonly string $description = '',
    ) {}

    public function validate(): void
    {
        if ($this->orderId === '') {
            throw new PaymentException('Wave Money: orderId is required.');
        }

        if (! filter_var($this->callbackUrl, FILTER_VALIDATE_URL)) {
            throw new PaymentException('Wave Money: callbackUrl must be a valid URL.');
        }

        if (empty($this->items)) {
            throw new PaymentException('Wave Money: at least one item is required.');
        }

        foreach ($this->items as $item) {
            if (! isset($item['name'], $item['amount']) || ! is_string($item['name']) || ! is_numeric($item['amount'])) {
                throw new PaymentException('Wave Money: each item must have a string "name" and numeric "amount".');
            }
        }
    }
}
