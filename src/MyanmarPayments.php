<?php

namespace Laranex\LaravelMyanmarPayments;

use Illuminate\Support\Manager;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;

/**
 * @method PaymentDriver driver(string $driver)
 */
class MyanmarPayments extends Manager
{
    public function getDefaultDriver(): string
    {
        throw new InvalidArgumentException(
            'No default Myanmar payment driver configured. Specify one via ::driver().'
        );
    }
}
