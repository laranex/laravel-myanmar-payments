<?php

declare(strict_types=1);

namespace Laranex\LaravelMyanmarPayments\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 client backed by Laravel's HTTP client, so `Http::fake()` and request events work for gateway calls.
 */
class LaravelHttpClient implements ClientInterface
{
    public function __construct(
        private readonly Factory $http,
        private readonly int $timeout,
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $headers = array_map(fn (array $values): string => implode(', ', $values), $request->getHeaders());

        try {
            return $this->http
                ->withHeaders($headers)
                ->withBody((string) $request->getBody(), $request->getHeaderLine('Content-Type') ?: 'application/octet-stream')
                ->timeout($this->timeout)
                ->send($request->getMethod(), (string) $request->getUri())
                ->toPsrResponse();
        } catch (ConnectionException $e) {
            throw new HttpClientException($e->getMessage(), 0, $e);
        }
    }
}
