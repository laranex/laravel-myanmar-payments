<?php

namespace Laranex\LaravelMyanmarPayments\Data\Request;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;

class AyaPayRequestPaymentData implements RequestPaymentData
{
    public function __construct(
        public readonly string $transactionId,
        public readonly int $amount,
        public readonly string $method,
        public readonly int $currencyCode = 104,
        public readonly string $frontendUrl = '',
        public readonly string $description = '',
        public readonly array $userRefs = [],
    ) {
        $this->validate();
    }

    public function validate(): void
    {
        $validator = Validator::make([
            'transactionId' => $this->transactionId,
            'method' => $this->method,
            'userRefs' => $this->userRefs,
        ], [
            'transactionId' => ['required'],
            'method' => ['required'],
            'userRefs' => ['array', 'max:5'],
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(implode(PHP_EOL, $validator->errors()->all()));
        }
    }
}
