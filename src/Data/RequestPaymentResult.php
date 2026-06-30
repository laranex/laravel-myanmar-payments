<?php

namespace Laranex\LaravelMyanmarPayments\Data;

use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;

class RequestPaymentResult
{
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $formUrl = null,
        public readonly ?array $formData = null,
        public readonly ?string $qrCode = null,
        public readonly ?array $appData = null,
        public readonly ?string $orderId = null,
        public readonly array $raw = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Successful;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function isInitiated(): bool
    {
        return $this->status === PaymentStatus::Initiated;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::Failed;
    }

    public function isCancelled(): bool
    {
        return $this->status === PaymentStatus::Cancelled;
    }

    public function requiresRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }
}
