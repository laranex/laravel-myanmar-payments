<?php

namespace Laranex\LaravelMyanmarPayments\Contracts;

use Laranex\LaravelMyanmarPayments\Data\HandlePaymentResult;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;

interface PaymentDriver
{
    public function initiate(RequestPaymentData $data): RequestPaymentResult;

    public function handleCallback(array $payload): HandlePaymentResult;

    public function getPaymentFlow(): PaymentFlow;

    public function isSuccessful(string $status): bool;
}
