<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;

class KbzPayPwa extends KbzPay
{
    /**
     * @throws Exception
     */
    public function getPaymentScreenUrl(string $orderId, float $amount, string $nonceStr, string $backendResultUrl): string
    {
        return $this->preCreate("PWAAPP", $orderId, $amount, $nonceStr, $backendResultUrl);
    }
}
