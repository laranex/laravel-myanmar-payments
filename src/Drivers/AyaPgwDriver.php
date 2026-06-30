<?php

namespace Laranex\LaravelMyanmarPayments\Drivers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentData;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Data\AyaPgwPaymentData;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

class AyaPgwDriver implements PaymentDriver
{
    public function __construct(private readonly array $config) {}

    public function initiate(PaymentData $data): RequestPaymentResult
    {
        if (! $data instanceof AyaPgwPaymentData) {
            throw new PaymentException('AyaPgwDriver expects AyaPgwPaymentData, got '.get_class($data).'.');
        }

        $data->validate();

        $appKey = $this->config['app_key'];
        $appSecret = $this->config['app_secret'];
        $baseUrl = $this->config['base_url'];
        $timestamp = time();

        $userRefs = array_pad(array_values($data->userRefs), 5, '');

        $requestData = [
            'merchOrderId' => $data->orderId,
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
            'channel' => $data->channel,
            'method' => $data->method,
            'overrideFrontendRedirectUrl' => $data->frontendUrl,
        ];

        $checkSum = hash_hmac('sha256', implode(':', array_values($requestData)), $appSecret);
        $requestData['checkSum'] = $checkSum;

        $formUrl = "$baseUrl/v1/payment/request";

        $payload = Crypt::encryptString(json_encode([
            'formUrl' => $formUrl,
            'formData' => $requestData,
        ]));

        return new RequestPaymentResult(
            status: PaymentStatus::Initiated,
            redirectUrl: route('myanmar-payments.form', ['payload' => $payload]),
            formUrl: $formUrl,
            formData: $requestData,
            orderId: $data->orderId,
            raw: $requestData,
        );
    }

    public function verify(string $orderId): RequestPaymentResult
    {
        $appKey = $this->config['app_key'];
        $appSecret = $this->config['app_secret'];
        $baseUrl = $this->config['base_url'];
        $timestamp = time();

        $checkSum = hash_hmac('sha256', "$orderId:$timestamp:$appKey", $appSecret);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post("$baseUrl/v1/payment/enquiry", [
            'merchOrderId' => $orderId,
            'appKey' => $appKey,
            'timestamp' => $timestamp,
            'checkSum' => $checkSum,
        ])->throw();

        if (($response->json()['status'] ?? null) !== '00') {
            return new RequestPaymentResult(
                status: PaymentStatus::Failed,
                orderId: $orderId,
                raw: $response->json() ?? [],
            );
        }

        $responseData = $response->json()['data'];

        $paymentStatus = match ($responseData['transactionStatus'] ?? '') {
            'SUCCESS' => PaymentStatus::Successful,
            'FAILED' => PaymentStatus::Failed,
            'CANCELLED' => PaymentStatus::Cancelled,
            default => PaymentStatus::Pending,
        };

        return new RequestPaymentResult(
            status: $paymentStatus,
            orderId: $orderId,
            raw: $responseData,
        );
    }

    public function handleCallback(array $payload): RequestPaymentResult
    {
        $appSecret = $this->config['app_secret'];
        $encodedPayload = $payload['payload'] ?? '';
        $checkSum = $payload['checkSum'] ?? '';

        if (! $encodedPayload) {
            throw new PaymentException('AYA PGW callback missing payload.');
        }

        $decoded = json_decode(base64_decode($encodedPayload), true);

        if (! is_array($decoded)) {
            throw new PaymentException('AYA PGW callback payload could not be decoded.');
        }

        $expectedCheckSum = hash_hmac('sha256', implode(':', array_values($decoded)), $appSecret);

        if (! hash_equals($expectedCheckSum, $checkSum)) {
            throw new PaymentException('AYA PGW callback checksum verification failed.');
        }

        $paymentStatus = match ($decoded['transactionStatus'] ?? '') {
            'SUCCESS' => PaymentStatus::Successful,
            'FAILED' => PaymentStatus::Failed,
            'CANCELLED' => PaymentStatus::Cancelled,
            default => PaymentStatus::Pending,
        };

        return new RequestPaymentResult(
            status: $paymentStatus,
            orderId: $decoded['merchOrderId'] ?? null,
            raw: $decoded,
        );
    }
}
