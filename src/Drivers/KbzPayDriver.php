<?php

namespace Laranex\LaravelMyanmarPayments\Drivers;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Contracts\RequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\HandlePaymentResult;
use Laranex\LaravelMyanmarPayments\Data\Request\KbzPayRequestPaymentData;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\HandlePaymentStatus;
use Laranex\LaravelMyanmarPayments\Enums\KbzPayTradeType;
use Laranex\LaravelMyanmarPayments\Enums\PaymentFlow;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;
use Laranex\LaravelMyanmarPayments\Exceptions\SignatureVerificationException;

class KbzPayDriver implements PaymentDriver
{
    public function __construct(
        private readonly KbzPayTradeType $tradeType,
        private readonly array $config,
    ) {}

    public function initiate(RequestPaymentData $data): RequestPaymentResult
    {
        if (! $data instanceof KbzPayRequestPaymentData) {
            throw new InvalidArgumentException('expects '.KbzPayRequestPaymentData::class.', got '.get_class($data));
        }

        $data->validate();

        $nonceStr = $data->nonceStr ?: bin2hex(random_bytes(16));
        $appId = $this->config['app_id'];
        $appKey = $this->config['app_key'];
        $merchantCode = $this->config['merchant_code'];
        $baseUrl = $this->config['base_url'];
        $method = 'kbz.payment.precreate';
        $timestamp = (string) time();
        $totalAmount = (string) $data->amount;
        $version = '1.0';
        $tradeType = $this->tradeType->value;

        $params = [
            'appid' => $appId,
            'merch_code' => $merchantCode,
            'merch_order_id' => $data->transactionId,
            'method' => $method,
            'nonce_str' => $nonceStr,
            'notify_url' => $data->callbackUrl,
            'timestamp' => $timestamp,
            'total_amount' => $totalAmount,
            'trade_type' => $tradeType,
            'trans_currency' => $data->currency,
            'version' => $version,
        ];

        $hash = strtoupper(hash('SHA256', $this->buildSignString($params, $appKey)));

        $response = Http::post("$baseUrl/precreate", [
            'Request' => [
                'timestamp' => $timestamp,
                'notify_url' => $data->callbackUrl,
                'method' => $method,
                'nonce_str' => $nonceStr,
                'sign_type' => 'SHA256',
                'sign' => $hash,
                'version' => $version,
                'biz_content' => [
                    'appid' => $appId,
                    'merch_code' => $merchantCode,
                    'merch_order_id' => $data->transactionId,
                    'total_amount' => $totalAmount,
                    'trade_type' => $tradeType,
                    'trans_currency' => $data->currency,
                ],
            ],
        ])->throw();

        if (($response->json()['Response']['code'] ?? null) !== '0') {
            return new RequestPaymentResult(
                flow: $this->getPaymentFlow(),
                transactionId: $data->transactionId,
                raw: $response->json() ?? [],
            );
        }

        $responseData = $response->json()['Response'];

        return match ($this->tradeType) {
            KbzPayTradeType::Pwa => $this->buildPwaResult($responseData, $appId, $merchantCode, $nonceStr, $timestamp, $appKey, $data->transactionId),
            KbzPayTradeType::Qr => new RequestPaymentResult(
                flow: PaymentFlow::QrBased,
                value: $responseData['qrCode'],
                originalValue: $responseData['qrCode'],
                transactionId: $data->transactionId,
                raw: $responseData,
            ),
            KbzPayTradeType::App => $this->buildAppResult($responseData, $appId, $merchantCode, $nonceStr, $timestamp, $appKey, $data->transactionId),
        };
    }

    public function getPaymentFlow(): PaymentFlow
    {
        return match ($this->tradeType) {
            KbzPayTradeType::Pwa => PaymentFlow::RedirectBased,
            KbzPayTradeType::Qr => PaymentFlow::QrBased,
            KbzPayTradeType::App => PaymentFlow::AppBased,
        };
    }

    public function getPaymentStatus(string $status): HandlePaymentStatus
    {
        return match ($status) {
            'PAY_SUCCESS' => HandlePaymentStatus::Successful,
            'PAY_FAIL' => HandlePaymentStatus::Failed,
            default => throw new PaymentException("unknown status: $status"),
        };
    }

    public function handleCallback(array $payload): HandlePaymentResult
    {
        $data = $payload['Request'] ?? $payload;
        $sign = $data['sign'] ?? '';

        $params = $data;
        unset($params['sign'], $params['sign_type']);
        ksort($params);

        $expectedSign = strtoupper(hash('SHA256', http_build_query($params).'&key='.$this->config['app_key']));

        if (! hash_equals($expectedSign, $sign)) {
            throw new SignatureVerificationException('KBZ Pay callback signature verification failed.', raw: $payload);
        }

        $status = $this->getPaymentStatus($data['trade_status']);

        return new HandlePaymentResult(
            status: $status,
            transactionId: $data['kbz_tran_no'],
            raw: $data,
        );
    }

    private function buildSignString(array $params, string $appKey): string
    {
        ksort($params);
        $parts = array_map(fn ($k, $v) => "$k=$v", array_keys($params), array_values($params));

        return implode('&', $parts)."&key=$appKey";
    }

    private function buildPwaResult(array $response, string $appId, string $merchantCode, string $nonceStr, string $timestamp, string $appKey, string $orderId): RequestPaymentResult
    {
        $pwaBaseUrl = $this->config['pwa']['base_redirect_url'];
        $prePayId = $response['prepay_id'];
        $signString = "appid=$appId&merch_code=$merchantCode&nonce_str=$nonceStr&prepay_id=$prePayId&timestamp=$timestamp&key=$appKey";
        $screenHash = strtoupper(hash('SHA256', $signString));
        $redirectUrl = "$pwaBaseUrl/?appid=$appId&merch_code=$merchantCode&nonce_str=$nonceStr&prepay_id=$prePayId&timestamp=$timestamp&sign=$screenHash";

        return new RequestPaymentResult(
            flow: PaymentFlow::RedirectBased,
            value: $redirectUrl,
            originalValue: $redirectUrl,
            transactionId: $orderId,
            raw: $response,
        );
    }

    private function buildAppResult(array $response, string $appId, string $merchantCode, string $nonceStr, string $timestamp, string $appKey, string $orderId): RequestPaymentResult
    {
        $params = [
            'appid' => $appId,
            'merch_code' => $merchantCode,
            'nonce_str' => $nonceStr,
            'prepay_id' => $response['prepay_id'],
            'timestamp' => $timestamp,
        ];
        ksort($params);
        $parts = array_map(fn ($k, $v) => "$k=$v", array_keys($params), array_values($params));
        $orderInfo = implode('&', $parts);
        $sign = strtoupper(hash('SHA256', $orderInfo."&key=$appKey"));

        $appPayload = [
            'orderInfo' => $orderInfo,
            'sign' => $sign,
            'signType' => 'SHA256',
        ];

        return new RequestPaymentResult(
            flow: PaymentFlow::AppBased,
            value: $appPayload,
            originalValue: $appPayload,
            transactionId: $orderId,
            raw: $response,
        );
    }
}
