<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

/**
 * Renders a signed payment form and submits it to the gateway from the customer's browser.
 */
class FormPaymentController
{
    public function __invoke(Request $request, FormPaymentUrl $formPaymentUrl): Response
    {
        $payload = $request->query('payload');
        $payment = is_string($payload) ? $formPaymentUrl->resolve($payload) : null;

        if ($payment === null) {
            throw new GoneHttpException('This payment link is invalid or has expired.');
        }

        return new Response($payment->toHtml(), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer-when-downgrade',
        ]);
    }
}
