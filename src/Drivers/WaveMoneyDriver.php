<?php

namespace Laranex\LaravelMyanmarPayments\Drivers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentData;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Data\WaveMoneyPaymentData;
use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

class WaveMoneyDriver implements PaymentDriver
{
    public function __construct(private readonly array $config) {}

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function initiate(PaymentData $data): RequestPaymentResult
    {
        if (! $data instanceof WaveMoneyPaymentData) {
            throw new PaymentException('WaveMoneyDriver expects WaveMoneyPaymentData, got '.get_class($data).'.');
        }

        $data->validate();

        $merchantId = $this->config['merchant_id'];
        $secretKey = $this->config['secret_key'];
        $baseUrl = $this->config['base_url'];
        $timeToLive = $this->config['time_to_live_in_seconds'];
        $merchantName = $this->config['merchant_name'];

        $frontendUrl = $data->frontendUrl ?: config('app.url', '');
        $description = $data->description ?: 'Payment for '.config('app.name', 'App');
        $merchantReferenceId = $data->merchantReferenceId ?: $data->orderId;
        $amount = array_sum(array_column($data->items, 'amount'));

        $hash = hash_hmac('sha256', implode('', [
            $timeToLive,
            $merchantId,
            $data->orderId,
            $amount,
            $data->callbackUrl,
            $merchantReferenceId,
        ]), $secretKey);

        $response = Http::acceptJson()->post("$baseUrl/payment", [
            'time_to_live_in_seconds' => $timeToLive,
            'merchant_id' => $merchantId,
            'order_id' => $data->orderId,
            'merchant_reference_id' => $merchantReferenceId,
            'frontend_result_url' => $frontendUrl,
            'backend_result_url' => $data->callbackUrl,
            'amount' => $amount,
            'payment_description' => $description,
            'merchant_name' => $merchantName,
            'items' => json_encode($data->items),
            'hash' => $hash,
        ])->throw();

        return new RequestPaymentResult(
            status: PaymentStatus::Initiated,
            redirectUrl: "$baseUrl/authenticate?transaction_id=".$response->json()['transaction_id'],
            orderId: $data->orderId,
            raw: $response->json(),
        );
    }

    public function verify(string $orderId): RequestPaymentResult
    {
        throw new PaymentException('Wave Money does not support order verification via API. Use handleCallback() instead.');
    }

    public function handleCallback(array $payload): RequestPaymentResult
    {
        $secretKey = $this->config['secret_key'];
        $status = $payload['status'] ?? '';

        $fields = [
            $payload['status'] ?? '',
            $payload['timeToLiveSeconds'] ?? '',
            $payload['merchantId'] ?? '',
            $payload['orderId'] ?? '',
            $payload['amount'] ?? '',
            $payload['backendResultUrl'] ?? '',
            $payload['merchantReferenceId'] ?? '',
            $payload['initiatorMsisdn'] ?? '',
            $payload['transactionId'] ?? '',
            $payload['paymentRequestId'] ?? '',
            $payload['requestTime'] ?? '',
        ];

        $expectedHash = hash_hmac('sha256', implode('', $fields), $secretKey);

        if (! hash_equals($expectedHash, $payload['hashValue'] ?? '')) {
            throw new PaymentException('Wave Money callback signature verification failed.');
        }

        $paymentStatus = $status === 'PAYMENT_CONFIRMED'
            ? PaymentStatus::Successful
            : PaymentStatus::Failed;

        return new RequestPaymentResult(
            status: $paymentStatus,
            orderId: $payload['orderId'] ?? null,
            raw: $payload,
        );
    }
}
