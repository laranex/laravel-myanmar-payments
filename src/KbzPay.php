<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KbzPay
{
    /**
     * @throws Exception
     */
    protected function preCreate(string $tradeType, string $orderId, string $amount, string $nonceStr, string $backendResultUrl): string|array
    {
        $kbzPayConfig = config("laravel-myanmar-payments.kbz_pay");
        $baseUrl = $kbzPayConfig["base_url"];

        $merchantCode = $kbzPayConfig["merchant_code"];
        $appId = $kbzPayConfig["app_id"];
        $appKey = $kbzPayConfig["app_key"];
        $method = "kbz.payment.precreate";

        $timestamp = (string)now()->timestamp;
        $tranCurrency = "MMK";
        $totalAmount = (string)$amount;
        $version = "1.0";

        $collection = collect([
            "appid" => $appId,
            "merch_code" => $merchantCode,
            "merch_order_id" => $orderId,
            "method" => $method,
            "nonce_str" => $nonceStr,
            "notify_url" => $backendResultUrl,
            "timestamp" => $timestamp,
            "total_amount" => $totalAmount,
            "trade_type" => $tradeType,
            "trans_currency" => $tranCurrency,
            "version" => $version,
        ]);

        $string = Helper::generateQueryString($collection, $appKey);
        $hash = strtoupper(hash('SHA256', $string));

        $bizContent = [
            "appid" => $appId,
            "merch_code" => $merchantCode,
            "merch_order_id" => $orderId,
            "total_amount" => $totalAmount,
            "trade_type" => $tradeType,
            "trans_currency" => $tranCurrency
        ];

        $response = Http::post("$baseUrl/precreate", [
            "Request" => [
                "timestamp" => $timestamp,
                "notify_url" => $backendResultUrl,
                "method" => $method,
                "nonce_str" => $nonceStr,
                "sign_type" => "SHA256",
                "sign" => $hash,
                "version" => $version,
                "biz_content" => $bizContent
            ]
        ]);

        if ($response->successful() && $response->json()['Response']['code'] === "0") {
            $response = $response->json()['Response'];

            switch ($tradeType) {
                case "PWAAPP":
                    $pwaUrl = $kbzPayConfig["pwa"]["base_redirect_url"];
                    $prePayId = $response['prepay_id'];
                    $paymentScreenString = "appid=$appId&merch_code=$merchantCode&nonce_str=$nonceStr&prepay_id=$prePayId&timestamp=$timestamp&key=$appKey";
                    $paymentScreenHash = strtoupper(hash('SHA256', $paymentScreenString));
                    return "$pwaUrl/?appid=$appId&merch_code=$merchantCode&nonce_str=$nonceStr&prepay_id=$prePayId&timestamp=$timestamp&sign=$paymentScreenHash";

                case "PAY_BY_QRCODE":
                    return $response['qrCode'];
                case "APP":
                    $collection = collect([
                        "prepay_id" => $response["prepay_id"],
                        "merch_code" => $merchantCode,
                        "appid" => $appId,
                        "timestamp" => $timestamp,
                        "nonce_str" => $nonceStr,
                    ]);

                    $orderInfo = $collection->sortKeys()->map(function ($value, $key) {
                        return "$key=$value";
                    })->implode("&");

                    return [
                        "orderInfo" => $orderInfo,
                        "sign" => strtoupper(hash('SHA256', $orderInfo . "&key=$appKey")),
                        "signType" => "SHA256"
                    ];

                default:
                    throw new Exception("Invalid trade type");
            }
        }
        throw new Exception("Something went wrong in requesting payment screen for KBZ Pay PWA with the status code of " . $response->status() . ". See more at https://wap.kbzpay.com/pgw/uat/api/#/en/docs/PWA/api-precreate-en");
    }

    public function verifySignature(Request $request): bool
    {
        $payload = $request->get('Request');
        $sign = $payload['sign'];
        $payload = collect($payload)->except(['sign', 'sign_type'])->sortKeys()->all();
        $string = http_build_query($payload) . "&key=" . config("laravel-myanmar-payments.kbz_pay.app_key");

        return hash_equals(strtoupper(hash('SHA256', $string)), $sign);
    }

    /**
     * @throws Exception
     */
    public function queryOrder(string $orderId, string $nonceStr, string $refundRequestNo = null)
    {
        $kbzPayConfig = config("laravel-myanmar-payments.kbz_pay");
        $baseUrl = $kbzPayConfig["base_url"];

        $merchantCode = $kbzPayConfig["merchant_code"];
        $appId = $kbzPayConfig["app_id"];
        $appKey = $kbzPayConfig["app_key"];
        $method = "kbz.payment.queryorder";

        $timestamp = (string)now()->timestamp;
        $version = "3.0";

        $collection = collect([
            "appid" => $appId,
            "merch_code" => $merchantCode,
            "merch_order_id" => $orderId,
            "method" => $method,
            "nonce_str" => $nonceStr,
            "timestamp" => $timestamp,
            "version" => $version
        ]);
        if ($refundRequestNo) {
            $collection->put("refund_request_no", $refundRequestNo);
        }

        $string = Helper::generateQueryString($collection, $appKey);
        $hash = strtoupper(hash('SHA256', $string));

        $bizContent = [
            "appid" => $appId,
            "merch_code" => $merchantCode,
            "merch_order_id" => $orderId
        ];
        if ($refundRequestNo) {
            $bizContent["refund_request_no"] = $refundRequestNo;
        }

        $response = Http::post("$baseUrl/queryorder", [
            "Request" => [
                "timestamp" => $timestamp,
                "method" => $method,
                "nonce_str" => $nonceStr,
                "sign_type" => "SHA256",
                "sign" => $hash,
                "version" => $version,
                "biz_content" => $bizContent
            ]
        ]);

        if ($response->successful() && $response->json()['Response']['code'] === "0") {
            return $response->json()['Response'];
        }
        throw new Exception("Something went wrong in requesting order query for KBZ Pay with the status code of " . $response->status() . ". See more at https://wap.kbzpay.com/pgw/uat/api/#/en/docs/PWA/api-queryOrder-en");
    }
}
