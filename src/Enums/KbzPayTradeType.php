<?php

namespace Laranex\LaravelMyanmarPayments\Enums;

enum KbzPayTradeType: string
{
    case Pwa = 'PWAAPP';
    case Qr = 'PAY_BY_QRCODE';
    case App = 'APP';
}
