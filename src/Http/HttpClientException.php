<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * The gateway could not be reached.
 */
class HttpClientException extends RuntimeException implements ClientExceptionInterface {}
