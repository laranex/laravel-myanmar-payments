<?php

namespace Laranex\LaravelMyanmarPayments\Data;

class HandlePaymentResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $transactionId = '',
        public readonly array $raw = [],
    ) {}
}
