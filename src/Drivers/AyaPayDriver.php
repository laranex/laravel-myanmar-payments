<?php

namespace Laranex\LaravelMyanmarPayments\Drivers;

use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\HandlePaymentResult;
use Laranex\LaravelMyanmarPayments\Data\Request\AyaPayRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;
use Laranex\LaravelMyanmarPayments\Exceptions\SignatureVerificationException;

class AyaPayDriver implements PaymentDriver
{
    public function __construct(private readonly array $config) {}

    public function initiate(RequestPaymentData $data): RequestPaymentResult
    {
        if (! $data instanceof AyaPayRequestPaymentData) {
            throw new InvalidArgumentException('expects '.AyaPayRequestPaymentData::class.', got '.get_class($data));
        }

        $data->validate();

        $appKey = $this->config['app_key'];
        $appSecret = $this->config['app_secret'];
        $baseUrl = $this->config['base_url'];
        $timestamp = time();

        $userRefs = array_pad(array_values($data->userRefs), 5, '');

        $requestData = [
            'merchOrderId' => $data->transactionId,
            'amount' => (string) $data->amount,
            'appKey' => $appKey,
            'timestamp' => $timestamp,
            'userRef1' => $userRefs[0],
            'userRef2' => $userRefs[1],
            'userRef3' => $userRefs[2],
            'userRef4' => $userRefs[3],
            'userRef5' => $userRefs[4],
            'description' => $data->description,
            'currencyCode' => $data->currencyCode,
            'channel' => 'AYA_PAY',
            'method' => $data->method,
            'overrideFrontendRedirectUrl' => $data->frontendUrl,
        ];

        $checkSum = hash_hmac('sha256', implode(':', array_values($requestData)), $appSecret);
        $requestData['checkSum'] = $checkSum;

        $formUrl = "$baseUrl/v1/payment/request";

        $payload = Crypt::encryptString(json_encode(['formUrl' => $formUrl, 'formData' => $requestData]));

        return new RequestPaymentResult(
            flow: PaymentFlow::FormBased,
            value: route('myanmar-payments.form', ['payload' => $payload]),
            originalValue: ['url' => $formUrl, 'data' => $requestData],
            transactionId: $data->transactionId,
            raw: $requestData,
        );
    }

    public function getPaymentFlow(): PaymentFlow
    {
        return PaymentFlow::FormBased;
    }

    public function getPaymentStatus(string $status): bool
    {
        return match ($status) {
            'SUCCESS' => true,
            'FAILED' => false,
            default => throw new PaymentException("unknown status: $status"),
        };
    }

    public function handleCallback(array $payload): HandlePaymentResult
    {
        $appSecret = $this->config['app_secret'];
        $encodedPayload = $payload['payload'] ?? '';
        $checkSum = $payload['checkSum'] ?? '';

        if (! $encodedPayload) {
            throw new PaymentException('AYA Pay callback missing payload.');
        }

        $decoded = json_decode(base64_decode($encodedPayload), true);

        if (! is_array($decoded)) {
            throw new PaymentException('AYA Pay callback payload could not be decoded.');
        }

        $expectedCheckSum = hash_hmac('sha256', implode(':', array_values($decoded)), $appSecret);

        if (! hash_equals($expectedCheckSum, $checkSum)) {
            throw new SignatureVerificationException('AYA Pay callback checksum verification failed.', raw: $payload);
        }

        return new HandlePaymentResult(
            successful: $this->getPaymentStatus($decoded['transactionStatus']),
            transactionId: $decoded['transactionId'],
            raw: $decoded,
        );
    }
}
