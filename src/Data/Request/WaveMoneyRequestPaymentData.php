<?php

namespace Laranex\LaravelMyanmarPayments\Data\Request;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;

class WaveMoneyRequestPaymentData implements RequestPaymentData
{
    public readonly string $merchantReferenceId;

    public readonly int $amount;

    public function __construct(
        public readonly string $orderId,
        public readonly string $backendResultUrl,
        public readonly string $frontendResultUrl,
        public readonly string $description,
        public readonly array $items = [],
        ?int $amount = null,
        ?string $merchantReferenceId = null,
    ) {
        $this->validate();
        $this->merchantReferenceId = $merchantReferenceId ?? $this->orderId;
        $this->amount = $amount ?? array_sum(array_column($this->items, 'amount'));
    }

    public function validate(): void
    {
        $validator = Validator::make([
            'orderId' => $this->orderId,
            'backendResultUrl' => $this->backendResultUrl,
            'frontendResultUrl' => $this->frontendResultUrl,
            'description' => $this->description,
            'items' => $this->items,
        ], [
            'orderId' => ['required'],
            'backendResultUrl' => ['required', 'url'],
            'frontendResultUrl' => ['required', 'url'],
            'description' => ['required'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string'],
            'items.*.amount' => ['required', 'integer', 'gt:0'],
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(implode(PHP_EOL, $validator->errors()->all()));
        }
    }
}
