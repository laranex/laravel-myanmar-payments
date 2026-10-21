<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Gateways;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Http\CallbackRequestFactory;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;
use Laranex\PhpMyanmarPayments\WavePayMiniApp\WavePayMiniApp as BaseWavePayMiniApp;

/**
 * {@see BaseWavePayMiniApp} that also accepts Laravel requests.
 */
class WavePayMiniApp extends BaseWavePayMiniApp
{
    /**
     * Verify Wave's callback against the callback URL the payment was signed with. It defaults to the Laravel
     * request's own URL (scheme, host and path, no query string, `$request->url()`); pass it explicitly when the
     * app sees a different URL than Wave posted to, e.g. behind a proxy that is not trusted.
     *
     * @throws InvalidArgumentException When a framework-agnostic request is given without a callback URL.
     */
    public function handleCallback(CallbackRequest|Request $request, ?string $callbackUrl = null): PaymentCallback
    {
        if ($callbackUrl === null) {
            if (! $request instanceof Request) {
                throw new InvalidArgumentException('Pass the callback URL the Wave Pay Mini App payment was signed with.');
            }

            $callbackUrl = $request->url();
        }

        return parent::handleCallback(CallbackRequestFactory::make($request), $callbackUrl);
    }
}
