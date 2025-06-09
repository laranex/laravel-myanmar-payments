<?php

namespace Laranex\LaravelMyanmarPayments;

class CyberSourceSecureAcceptance extends CyberSource
{
    public function getPaymentData(string $transactionId, string $referenceNumber, float $amount, string $currencyCode = "MMK", string $transactionType = "sale"): array
    {
        return $this->processTransaction($transactionId, $referenceNumber, $amount, $currencyCode, $transactionType);
    }
}
