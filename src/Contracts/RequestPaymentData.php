<?php

namespace Laranex\LaravelMyanmarPayments\Contracts;

interface RequestPaymentData
{
    public function validate(): void;
}
