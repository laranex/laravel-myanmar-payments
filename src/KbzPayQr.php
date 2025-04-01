<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;

class KbzPayQr extends KbzPay
{
    /**
     * @throws Exception
     */
    public function getPaymentQr(string $orderId, string $amount, string $nonceStr, string $backendResultUrl): string
    {
        return $this->preCreate("PAY_BY_QRCODE", $orderId, $amount, $nonceStr, $backendResultUrl);
    }
}
