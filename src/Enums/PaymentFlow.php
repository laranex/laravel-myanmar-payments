<?php

namespace Laranex\LaravelMyanmarPayments\Enums;

enum PaymentFlow: string
{
    case RedirectBased = 'redirect';
    case FormBased = 'form';
    case QrBased = 'qr';
    case AppBased = 'app';
}
