<?php

namespace Laranex\LaravelMyanmarPayments\Data\Request;

use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;

class WaveMoneyRequestPaymentData implements RequestPaymentData
{
    public function __construct(
        public readonly string $transactionId,
        public readonly string $callbackUrl,
        public readonly string $frontendUrl,
        public readonly string $description,
        /** @var array<array{name: string, amount: int}> */
        public readonly array $items = [],
    ) {}

    public function validate(): void
    {
        if ($this->transactionId === '') {
            throw new InvalidArgumentException('transactionId is required.');
        }

        if (! filter_var($this->callbackUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('callbackUrl must be a valid URL.');
        }

        if (! filter_var($this->frontendUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('frontendUrl must be a valid URL.');
        }

        if ($this->description === '') {
            throw new InvalidArgumentException('description is required.');
        }

        if (empty($this->items)) {
            throw new InvalidArgumentException('at least one item is required.');
        }

        foreach ($this->items as $index => $item) {
            if (! isset($item['name'], $item['amount']) || ! is_string($item['name']) || ! is_int($item['amount'])) {
                throw new InvalidArgumentException('$items['.$index.'] is invalid, must be ["name" => "string", "amount" => "integer"]');
            }
        }
    }
}
