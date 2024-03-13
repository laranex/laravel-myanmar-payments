<?php

namespace Laranex\LaravelMyanmarPayments;

class LaravelMyanmarPayments
{
    /**
     * @throws \Exception
     */
    public function channel($channel): WaveMoney | TwoCTwoP | KbzPayPwa
    {
        switch ($channel) {
            case "wave_money":
                return new WaveMoney();
            case "2c2p":
                return new TwoCTwoP();
            case "kbz_pay.pwaapp":
                return new KbzPayPwa();
            default:
                throw new \Exception("Unsupported Payment Channel");
        }
    }
}
