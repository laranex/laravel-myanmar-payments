<?php

namespace Laranex\LaravelMyanmarPayments\Exceptions;

use Throwable;

class SignatureVerificationException extends PaymentException
{
    public function __construct(string $message = 'Signature Verification Failed.', public readonly array $raw = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("$message ".'HTTP Request Body: '.json_encode($raw), $code, $previous);
    }
}
