<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laranex\PhpMyanmarPayments\Results\PaymentCallback;

/**
 * The response a gateway expects after delivering a callback, e.g. KBZ Pay's plain `success`.
 */
class CallbackResponse implements Responsable
{
    public function __construct(private readonly PaymentCallback $callback) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $acknowledgement = $this->callback->acknowledgement();

        return new Response($acknowledgement->body, $acknowledgement->status, $acknowledgement->headers);
    }
}
