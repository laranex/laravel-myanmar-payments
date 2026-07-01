<?php

namespace Laranex\LaravelMyanmarPayments\Data;

use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;

class RequestPaymentResult
{
    /**
     * @param  array{url: string, data: array<string, mixed>}|null  $form
     */
    public function __construct(
        public readonly ?PaymentFlow $flow = null,
        public readonly mixed $value = null,
        public readonly ?array $form = null,
        public readonly ?string $transactionId = null,
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
