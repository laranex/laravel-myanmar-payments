<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http;

use Illuminate\Http\Request;
use Laranex\PhpMyanmarPayments\Http\CallbackRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Turns a Laravel request into the framework-agnostic request the gateways verify.
 */
class CallbackRequestFactory
{
    /**
     * The largest callback body accepted (64 KiB), like the other framework packages.
     */
    public const MAX_CALLBACK_BODY_BYTES = 65536;

    /**
     * @throws HttpException `413` when the declared or actual body is larger than {@see MAX_CALLBACK_BODY_BYTES}.
     */
    public static function make(CallbackRequest|Request $request): CallbackRequest
    {
        if ($request instanceof CallbackRequest) {
            return $request;
        }

        $declared = trim((string) $request->headers->get('Content-Length'));

        if (preg_match('/^[0-9]+\z/', $declared) === 1 && (int) $declared > self::MAX_CALLBACK_BODY_BYTES) {
            throw self::tooLarge();
        }

        $body = $request->getContent();

        if (strlen($body) > self::MAX_CALLBACK_BODY_BYTES) {
            throw self::tooLarge();
        }

        /** @var array<string, mixed> $query */
        $query = $request->query->all();

        return new CallbackRequest(
            body: $body,
            headers: array_map(fn (array $values): string => implode(', ', array_map(strval(...), $values)), array_filter($request->headers->all(), is_array(...))),
            query: $query,
        );
    }

    private static function tooLarge(): HttpException
    {
        return new HttpException(413, 'The callback request body is larger than '.self::MAX_CALLBACK_BODY_BYTES.' bytes.');
    }
}
