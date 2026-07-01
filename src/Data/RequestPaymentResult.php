<?php

namespace Laranex\LaravelMyanmarPayments\Data;

use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;

class RequestPaymentResult
{
    public function __construct(
        public readonly PaymentFlow $flow,
        public readonly mixed $value = null,
        public readonly mixed $originalValue = null,
        public readonly string $transactionId = '',
        public readonly array $raw = [],
    ) {}

    public function isRedirectBased(): bool
    {
        return $this->flow === PaymentFlow::RedirectBased;
    }

    public function isFormBased(): bool
    {
        return $this->flow === PaymentFlow::FormBased;
    }

    public function isQrBased(): bool
    {
        return $this->flow === PaymentFlow::QrBased;
    }

    public function isAppBased(): bool
    {
        return $this->flow === PaymentFlow::AppBased;
    }
}
