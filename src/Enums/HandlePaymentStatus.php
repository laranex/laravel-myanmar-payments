<?php

namespace Laranex\LaravelMyanmarPayments\Enums;

enum HandlePaymentStatus: string
{
    case Successful = 'successful';
    case Pending = 'pending';
    case Failed = 'failed';
}
