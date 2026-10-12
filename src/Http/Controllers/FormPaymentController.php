<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http\Controllers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laranex\LaravelMyanmarPayments\Http\FormPaymentUrl;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

/**
 * Renders a signed payment form and submits it to the gateway from the customer's browser.
 *
 * The page needs no session or cookies (the link is encrypted), and sends only your origin as the referrer so the
 * link never leaks to the gateway. When the app sets a Content-Security-Policy nonce through Laravel's Vite
 * (`Vite::useCspNonce()`), the inline script carries it.
 */
class FormPaymentController
{
    public function __invoke(Request $request, FormPaymentUrl $formPaymentUrl, Container $container): Response
    {
        $payload = $request->query('payload');
        $payment = is_string($payload) ? $formPaymentUrl->resolve($payload) : null;

        if ($payment === null) {
            throw new GoneHttpException('This payment link is invalid or has expired.');
        }

        $html = $payment->toHtml();
        $nonce = $this->cspNonce($container);

        if ($nonce !== null) {
            $html = str_replace('<script>', '<script nonce="'.htmlspecialchars($nonce, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'">', $html);
        }

        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'strict-origin',
        ]);
    }

    /**
     * The nonce set with `Vite::useCspNonce()`, when the app runs on the full Laravel framework and set one.
     */
    private function cspNonce(Container $container): ?string
    {
        if (! $container->bound(Vite::class)) {
            return null;
        }

        $nonce = $container->make(Vite::class)->cspNonce();

        return is_string($nonce) && $nonce !== '' ? $nonce : null;
    }
}
