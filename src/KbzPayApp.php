<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
class KbzPayApp extends KbzPay
{
    /**
     * @throws Exception
     */
    public function getPaymentData(string $orderId, int $amount,string $nonceStr, string $backendResultUrl): string|array
    {
        return $this->preCreate("APP", $orderId, $amount, $nonceStr, $backendResultUrl);
    }
}
