<?php

namespace Laranex\LaravelMyanmarPayments;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Laranex\LaravelMyanmarPayments\Skeleton\SkeletonClass
 */
class LaravelMyanmarPaymentsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-myanmar-payments';
    }
}
