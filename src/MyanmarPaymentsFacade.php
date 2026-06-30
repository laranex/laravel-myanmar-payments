<?php

namespace Laranex\LaravelMyanmarPayments;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver driver(string $driver = null)
 *
 * @see MyanmarPayments
 */
class MyanmarPaymentsFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'myanmar-payments';
    }
}
