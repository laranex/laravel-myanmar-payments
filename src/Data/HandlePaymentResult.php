<?php

namespace Laranex\LaravelMyanmarPayments\Data;

use Laranex\LaravelMyanmarPayments\Enums\HandlePaymentStatus;

class HandlePaymentResult
{
    public function __construct(
        public readonly HandlePaymentStatus $status,
        public readonly ?string $transactionId = null,
        public readonly array $raw = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === HandlePaymentStatus::Successful;
    }

    public function isPending(): bool
    {
        return $this->status === HandlePaymentStatus::Pending;
    }

    public function isFailed(): bool
    {
        return $this->status === HandlePaymentStatus::Failed;
    }
}
