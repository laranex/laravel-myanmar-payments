<?php

namespace Laranex\LaravelMyanmarPayments;

class LaravelMyanmarPayments
{
    /**
     * @throws \Exception
     */
    public function channel($channel): WaveMoney | TwoCTwoP
    {
        switch ($channel) {
            case "wave_money":
                return new WaveMoney();
            case "2c2p":
                return new TwoCTwoP();
            default:
                throw new \Exception("Unsupported Payment Channel");
        }
    }
}
