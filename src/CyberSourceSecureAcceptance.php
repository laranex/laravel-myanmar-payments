<?php

namespace Laranex\LaravelMyanmarPayments;

class CyberSourceSecureAcceptance extends CyberSource
{
    public function getPaymentData(string $transactionId, string $referenceNumber, int $amount, string $currencyCode = "MMK"): array
    {
        return $this->authorizePayment($transactionId, $referenceNumber, $amount, $currencyCode);
    }
}
