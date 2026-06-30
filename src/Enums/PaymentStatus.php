<?php

namespace Laranex\LaravelMyanmarPayments\Enums;

enum PaymentStatus: string
{
    case Initiated = 'initiated';
    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}
