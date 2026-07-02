<?php

namespace Laranex\LaravelMyanmarPayments\Data\Request;

use Illuminate\Support\Facades\Validator;
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
    ) {
        $this->validate();
    }

    public function validate(): void
    {
        $validator = Validator::make([
            'transactionId' => $this->transactionId,
            'callbackUrl' => $this->callbackUrl,
        ], [
            'transactionId' => ['required'],
            'callbackUrl' => ['required', 'url'],
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(implode(PHP_EOL, $validator->errors()->all()));
        }
    }
}
