<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http;

use Illuminate\Http\Request;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;

/**
 * Turns a Laravel request into the framework-agnostic request the gateways verify.
 */
class CallbackRequestFactory
{
    public static function make(CallbackRequest|Request $request): CallbackRequest
    {
        if ($request instanceof CallbackRequest) {
            return $request;
        }

        /** @var array<string, mixed> $query */
        $query = $request->query->all();

        return new CallbackRequest(
            body: $request->getContent(),
            headers: array_map(fn (array $values): string => implode(', ', array_map(strval(...), $values)), array_filter($request->headers->all(), is_array(...))),
            query: $query,
        );
    }
}
