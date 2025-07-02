<?php

namespace Laranex\LaravelMyanmarPayments;

class CyberSourceSecureAcceptance extends CyberSource
{
    public function getPaymentData(string $transactionId, string $referenceNumber, int $amount, string $currencyCode = "MMK", string $transactionType = "sale", string $frontendUrl = "", string $backendUrl = "", string $cancelUrl = ""): array
    {
        return $this->processTransaction($transactionId, $referenceNumber, $amount, $currencyCode, $transactionType, $frontendUrl, $backendUrl, $cancelUrl);
    }
}
