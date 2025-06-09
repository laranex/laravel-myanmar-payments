<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;

class LaravelMyanmarPayments
{
    /**
     * @throws \Exception
     */
    public function channel($channel): WaveMoney|KbzPayPwa|KbzPayQr|KbzPayApp|CyberSourceSecureAcceptance|AyaPgw
    {
        return match ($channel) {
            "wave_money" => new WaveMoney(),
            "kbz_pay.pwaapp" => new KbzPayPwa(),
            "kbz_pay.qr" => new KbzPayQr(),
            "kbz_pay.app" => new KbzPayApp(),
            "cyber_source.secure_acceptance" => new CyberSourceSecureAcceptance(),
            "aya_pgw" => new AyaPgw(),
            default => throw new Exception("Unsupported Payment Channel"),
        };
    }
}
