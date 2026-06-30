<?php

namespace Laranex\LaravelMyanmarPayments\Drivers;

use Illuminate\Support\Facades\Http;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentData;
use Laranex\LaravelMyanmarPayments\Contracts\PaymentDriver;
use Laranex\LaravelMyanmarPayments\Data\KbzPayPaymentData;
use Laranex\LaravelMyanmarPayments\Data\RequestPaymentResult;
use Laranex\LaravelMyanmarPayments\Enums\KbzPayTradeType;
use Laranex\LaravelMyanmarPayments\Enums\PaymentStatus;
use Laranex\LaravelMyanmarPayments\Exceptions\PaymentException;

class KbzPayDriver implements PaymentDriver
{
    public function __construct(
        private readonly KbzPayTradeType $tradeType,
        private readonly array $config,
    ) {}

    public function initiate(PaymentData $data): RequestPaymentResult
    {
        if (! $data instanceof KbzPayPaymentData) {
            throw new PaymentException('KbzPayDriver expects KbzPayPaymentData, got '.get_class($data).'.');
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
            'merch_order_id' => $data->orderId,
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
                    'merch_order_id' => $data->orderId,
                    'total_amount' => $totalAmount,
                    'trade_type' => $tradeType,
                    'trans_currency' => $data->currency,
                ],
            ],
        ])->throw();

        if (($response->json()['Response']['code'] ?? null) !== '0') {
            return new RequestPaymentResult(
                status: PaymentStatus::Failed,
                orderId: $data->orderId,
                raw: $response->json() ?? [],
            );
        }

        $responseData = $response->json()['Response'];

        return match ($this->tradeType) {
            KbzPayTradeType::Pwa => $this->buildPwaResult($responseData, $appId, $merchantCode, $nonceStr, $timestamp, $appKey, $data->orderId),
            KbzPayTradeType::Qr => new RequestPaymentResult(
                status: PaymentStatus::Initiated,
                qrCode: $responseData['qrCode'],
                orderId: $data->orderId,
                raw: $responseData,
            ),
            KbzPayTradeType::App => $this->buildAppResult($responseData, $appId, $merchantCode, $nonceStr, $timestamp, $appKey, $data->orderId),
        };
    }

    public function verify(string $orderId): RequestPaymentResult
    {
        $nonceStr = bin2hex(random_bytes(16));
        $appId = $this->config['app_id'];
        $appKey = $this->config['app_key'];
        $merchantCode = $this->config['merchant_code'];
        $baseUrl = $this->config['base_url'];
        $method = 'kbz.payment.queryorder';
        $timestamp = (string) time();
        $version = '3.0';

        $params = [
            'appid' => $appId,
            'merch_code' => $merchantCode,
            'merch_order_id' => $orderId,
            'method' => $method,
            'nonce_str' => $nonceStr,
            'timestamp' => $timestamp,
            'version' => $version,
        ];

        $hash = strtoupper(hash('SHA256', $this->buildSignString($params, $appKey)));

        $response = Http::post("$baseUrl/queryorder", [
            'Request' => [
                'timestamp' => $timestamp,
                'method' => $method,
                'nonce_str' => $nonceStr,
                'sign_type' => 'SHA256',
                'sign' => $hash,
                'version' => $version,
                'biz_content' => [
                    'appid' => $appId,
                    'merch_code' => $merchantCode,
                    'merch_order_id' => $orderId,
                ],
            ],
        ])->throw();

        if (($response->json()['Response']['code'] ?? null) !== '0') {
            return new RequestPaymentResult(
                status: PaymentStatus::Failed,
                orderId: $orderId,
                raw: $response->json() ?? [],
            );
        }

        $data = $response->json()['Response'];

        $status = match ($data['order_status'] ?? '') {
            'SUCCESS' => PaymentStatus::Successful,
            'FAIL' => PaymentStatus::Failed,
            'CANCELLED' => PaymentStatus::Cancelled,
            default => PaymentStatus::Pending,
        };

        return new RequestPaymentResult(
            status: $status,
            orderId: $orderId,
            raw: $data,
        );
    }

    public function handleCallback(array $payload): RequestPaymentResult
    {
        $data = $payload['Request'] ?? $payload;
        $sign = $data['sign'] ?? '';

        $params = $data;
        unset($params['sign'], $params['sign_type']);
        ksort($params);

        $expectedSign = strtoupper(hash('SHA256', http_build_query($params).'&key='.$this->config['app_key']));

        if (! hash_equals($expectedSign, $sign)) {
            throw new PaymentException('KBZ Pay callback signature verification failed.');
        }

        $status = match ($data['trade_status'] ?? '') {
            'PAY_SUCCESS' => PaymentStatus::Successful,
            'PAY_FAIL' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };

        return new RequestPaymentResult(
            status: $status,
            orderId: $data['merch_order_id'] ?? null,
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
            status: PaymentStatus::Initiated,
            redirectUrl: $redirectUrl,
            orderId: $orderId,
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

        return new RequestPaymentResult(
            status: PaymentStatus::Initiated,
            appData: [
                'orderInfo' => $orderInfo,
                'sign' => $sign,
                'signType' => 'SHA256',
            ],
            orderId: $orderId,
            raw: $response,
        );
    }
}
