<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;

/**
 * Renders a signed payment form and submits it to the gateway from the customer's browser.
 */
class FormPaymentController
{
    public function __invoke(Request $request, FormPaymentUrl $formPaymentUrl): Response
    {
        $payment = $formPaymentUrl->resolve($request->string('payload')->toString());

        abort_if($payment === null, 410, 'This payment link is invalid or has expired.');

        return new Response($payment->toHtml(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer-when-downgrade',
        ]);
    }
}
