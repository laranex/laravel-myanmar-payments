<?php

namespace Laranex\LaravelMyanmarPayments\Data\Request;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;

class KbzPayRequestPaymentData implements RequestPaymentData
{
    public function __construct(
        public readonly string $transactionId,
        public readonly int $amount,
        public readonly string $callbackUrl,
        public readonly string $currency = 'MMK',
        public readonly string $nonceStr = '',
    ) {
        $this->validate();
    }

    public function validate(): void
    {
        $validator = Validator::make([
            'transactionId' => $this->transactionId,
            'amount' => $this->amount,
            'callbackUrl' => $this->callbackUrl,
        ], [
            'transactionId' => ['required'],
            'amount' => ['integer', 'min:0'],
            'callbackUrl' => ['required', 'url'],
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(implode(PHP_EOL, $validator->errors()->all()));
        }
    }
}
