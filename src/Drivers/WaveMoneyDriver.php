<?php

namespace Laranex\LaravelMyanmarPayments\Drivers;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\HandlePaymentResult;
use Laranex\LaravelMyanmarPayments\Data\Request\WaveMoneyRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\HandlePaymentStatus;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\Exceptions\ApiException;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;
use Laranex\LaravelMyanmarPayments\Exceptions\SignatureVerificationException;

class WaveMoneyDriver implements PaymentDriver
{
    public function __construct(private readonly array $config) {}

    public function initiate(RequestPaymentData $data): RequestPaymentResult
    {
        if (! $data instanceof WaveMoneyRequestPaymentData) {
            throw new InvalidArgumentException('initiation failed. expects '.WaveMoneyRequestPaymentData::class.', got '.get_class($data));
        }

        $data->validate();

        $merchantId = $this->config['merchant_id'];
        $secretKey = $this->config['secret_key'];
        $baseUrl = $this->config['base_url'];
        $timeToLive = $this->config['time_to_live_in_seconds'];
        $merchantName = $this->config['merchant_name'];

        $frontendUrl = $data->frontendUrl;
        $description = $data->description;
        $merchantReferenceId = $data->transactionId;
        $amount = array_sum(array_column($data->items, 'amount'));

        $hash = hash_hmac('sha256', implode('', [
            $timeToLive,
            $merchantId,
            $data->transactionId,
            $amount,
            $data->callbackUrl,
            $merchantReferenceId,
        ]), $secretKey);

        $response = Http::acceptJson()->post("$baseUrl/payment", [
            'time_to_live_in_seconds' => $timeToLive,
            'merchant_id' => $merchantId,
            'order_id' => $data->transactionId,
            'merchant_reference_id' => $merchantReferenceId,
            'frontend_result_url' => $frontendUrl,
            'backend_result_url' => $data->callbackUrl,
            'amount' => $amount,
            'payment_description' => $description,
            'merchant_name' => $merchantName,
            'items' => json_encode($data->items),
            'hash' => $hash,
        ]);

        $responseData = $response->json() ?? [];

        if (! $response->successful() || ($responseData['message'] ?? null) !== 'success' || empty($responseData['transaction_id'])) {
            throw new ApiException('initiation failed.', raw: $responseData, code: $response->status());
        }

        $redirectUrl = "$baseUrl/authenticate?transaction_id=".$responseData['transaction_id'];

        return new RequestPaymentResult(
            flow: PaymentFlow::RedirectBased,
            value: $redirectUrl,
            originalValue: $redirectUrl,
            transactionId: $data->transactionId,
            raw: $responseData,
        );
    }

    public function getPaymentFlow(): PaymentFlow
    {
        return PaymentFlow::RedirectBased;
    }

    public function getPaymentStatus(string $status): HandlePaymentStatus
    {
        return match (true) {
            $status == 'PAYMENT_CONFIRMED' => HandlePaymentStatus::Successful,
            in_array($status, [
                'PAYMENT_FAILED',
                'TRANSACTION_TIMED_OUT',
                'SCHEDULER_TRANSACTION_TIMED_OUT',
            ]) => HandlePaymentStatus::Failed,
            default => throw new PaymentException("unknown status: $status"),
        };
    }

    public function handleCallback(array $payload): HandlePaymentResult
    {
        $secretKey = $this->config['secret_key'];
        $status = $payload['status'] ?? '';

        $fields = [
            $payload['status'] ?? null,
            $payload['timeToLiveSeconds'] ?? null,
            $payload['merchantId'] ?? null,
            $payload['orderId'] ?? null,
            $payload['amount'] ?? null,
            $payload['backendResultUrl'] ?? null,
            $payload['merchantReferenceId'] ?? null,
            $payload['initiatorMsisdn'] ?? null,
            $payload['transactionId'] ?? null,
            $payload['paymentRequestId'] ?? null,
            $payload['requestTime'] ?? null,
        ];

        $hashString = implode('', array_map(fn ($v) => $v ?? 'null', $fields));
        $expectedHash = hash_hmac('sha256', $hashString, $secretKey);

        if (! hash_equals($expectedHash, $payload['hashValue'] ?? '')) {
            throw new SignatureVerificationException(raw: $payload);
        }

        return new HandlePaymentResult(
            status: $this->getPaymentStatus($status),
            transactionId: $payload['transactionId'],
            raw: $payload,
        );
    }
}
