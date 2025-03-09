<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
use Illuminate\Support\Facades\Http;

class AyaPgw
{
    /**
     * @throws Exception
     */
    public function getPaymentServices(): array
    {
        $timestamp = now()->timestamp;
        $config = config("laravel-myanmar-payments.aya_pgw");
        $appKey = $config["app_key"];
        $appSecret = $config["app_secret"];
        $checkSum = hash_hmac('sha256', "$appKey:$appSecret:$timestamp", $appSecret);
        $data = [
            "appKey" => $config["app_key"],
            "timestamp" => $timestamp,
            "checkSum" => $checkSum,
        ];

        $baseUrl = $config["base_url"];
        $response = Http::withHeaders([
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ])->post("{$baseUrl}/v1/payment/services", $data);
        if ($response->json()["status"] === "00") {
            return $response->json()["data"];
        }

        throw new Exception("Something went wrong in requesting payment services. Status code - {$response->status()}");
    }

    public function paymentRequest(string $merchantOrderId, string $amount, string $channel, string $method, int $currencyCode = 104, string $description = "", string $overrideFrontendRedirectUrl = "", array $userRefs = []): array
    {
        $config = config("laravel-myanmar-payments.aya_pgw");
        $timestamp = now()->timestamp;

        // throw if the userRefs is more than 5
        if (count($userRefs) > 5) {
            throw new Exception("only 5 User defined values can be existed");
        }
        // add empty string to the userRefs if the userRefs is less than 5
        $keys = range(1,5);
        for($index = 0; $index < count($keys); $index++) {
            $userRefs[$index] = $userRefs[$index]?? "";
        }

        $data = [
            "merchOrderId" => $merchantOrderId,
            "amount" => $amount,
            "appKey" => $config["app_key"],
            "timestamp" => (int) $timestamp,
            "userRef1" => $userRefs[0],
            "userRef2" => $userRefs[1],
            "userRef3" => $userRefs[2],
            "userRef4" => $userRefs[3],
            "userRef5" => $userRefs[4],
            "description" => $description,
            "currencyCode" => $currencyCode,
            "channel" => $channel,
            "method" => $method,
            "overrideFrontendRedirectUrl" => $overrideFrontendRedirectUrl,
        ];

        $checkSum = Helper::hashAyaPgw($data);
        $data["checkSum"] = $checkSum;
        return [
            "url" => "{$config["base_url"]}/v1/payment/request",
            "data" => $data,
        ];
    }

    public function verifySignature(string $payload, string $checkSum): array|bool
    {
        $payload = json_decode(base64_decode($payload), true);
        $hash = Helper::hashAyaPgw($payload);
        if ($hash === $checkSum) return $payload;

        return false;
    }

    public function paymentEnquiry(string $merchantOrderId): array
    {
        $timestamp = now()->timestamp;
        $config = config("laravel-myanmar-payments.aya_pgw");
        $appKey = $config["app_key"];
        $appSecret = $config["app_secret"];
        $checkSum = hash_hmac('sha256', "$merchantOrderId:$timestamp:$appKey", $appSecret);
        $data = [
            "merchOrderId" => $merchantOrderId,
            "appKey" => $config["app_key"],
            "timestamp" => (int) $timestamp,
            "checkSum" => $checkSum,
        ];

        $baseUrl = $config["base_url"];
        $response = Http::withHeaders([
            "Accept" => "application/json",
            "Content-Type" => "application/json",
        ])->post("{$baseUrl}/v1/payment/enquiry", $data);
        if ($response->json()["status"] === "00") {
            return $response->json()["data"];
        }

        throw new Exception("Something went wrong in requesting payment enquiry. Status code - {$response->status()}");
    }
}
