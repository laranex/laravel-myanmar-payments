<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
class KbzPayPwa extends KbzPay
{
    /**
     * @throws Exception
     */
    public function getPaymentScreenUrl(string $orderId, int $amount, string $backendResultUrl): string
    {
        return $this->preCreate("PWAAPP", $orderId, $amount, $backendResultUrl);
    }
}
