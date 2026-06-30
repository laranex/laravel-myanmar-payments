<?php

namespace Laranex\LaravelMyanmarPayments\Contracts;

use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;

interface PaymentDriver
{
    public function initiate(PaymentData $data): RequestPaymentResult;

    public function verify(string $orderId): RequestPaymentResult;

    public function handleCallback(array $payload): RequestPaymentResult;
}
