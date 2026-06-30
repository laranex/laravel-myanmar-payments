<?php

namespace Laranex\LaravelMyanmarPayments\Contracts;

interface PaymentData
{
    public function validate(): void;
}
