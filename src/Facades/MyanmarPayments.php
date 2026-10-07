<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Facades;

use Illuminate\Support\Facades\Facade;
use Laranex\LaravelMyanmarPayments\MyanmarPayments as MyanmarPaymentsManager;

/**
 * @method static \Laranex\LaravelMyanmarPayments\Gateways\KbzPay kbzPay()
 * @method static \Laranex\LaravelMyanmarPayments\Gateways\WaveMoney waveMoney()
 * @method static \Laranex\LaravelMyanmarPayments\Gateways\AyaPay ayaPay()
 * @method static \Laranex\LaravelMyanmarPayments\Gateways\YomaMmqr yomaMmqr()
 * @method static \Laranex\LaravelMyanmarPayments\Gateways\CyberSource cyberSource()
 * @method static \Laranex\LaravelMyanmarPayments\Http\CallbackResponse acknowledge(\Laranex\PhpMyanmarPayments\Results\PaymentCallback $callback)
 *
 * @see MyanmarPaymentsManager
 */
class MyanmarPayments extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MyanmarPaymentsManager::class;
    }
}
