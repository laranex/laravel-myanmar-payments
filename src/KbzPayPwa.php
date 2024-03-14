<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
