<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KbzPayPwa
{
    public function getPaymentScreenUrl(string $orderId, int $amount, string $backendResultUrl): string
    {
        return $this->preCreate($orderId, $amount, $backendResultUrl);
    }

    /**
     * @throws Exception
     */
    private function preCreate(string $orderId, int $amount, string $backendResultUrl): string
    {
        $kbzPayConfig = config("laravel-myanmar-payments.kbz_pay");
        $baseUrl = $kbzPayConfig["base_url"];
        $pwaUrl = $kbzPayConfig["pwa_url"];

        $merchantCode = $kbzPayConfig["merchant_code"];
        $appId = $kbzPayConfig["app_id"];
        $appKey = $kbzPayConfig["app_key"];
        $nonceStr = strtoupper(Str::random(32));
        $method = "kbz.payment.precreate";

        $timestamp = (string)now()->timestamp;
        $tradeType = "PWAAPP";
        $tranCurrency = "MMK";
        $totalAmount = (string)$amount;
        $version = "1.0";

        $string = "appid=$appId&merch_code=$merchantCode&merch_order_id=$orderId&method=$method&nonce_str=$nonceStr&notify_url=$backendResultUrl&timestamp=$timestamp&total_amount=$totalAmount&trade_type=$tradeType&trans_currency=$tranCurrency&version=$version&key=$appKey";
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
            $prePayId = $response['prepay_id'];
            $paymentScreenString = "appid=$appId&merch_code=$merchantCode&nonce_str=$nonceStr&prepay_id=$prePayId&timestamp=$timestamp&key=$appKey";
            $paymentScreenHash = strtoupper(hash('SHA256', $paymentScreenString));
            return "$pwaUrl/?appid=$appId&merch_code=$merchantCode&nonce_str=$nonceStr&prepay_id=$prePayId&timestamp=$timestamp&sign=$paymentScreenHash";
        }
        throw new Exception("Something went wrong in requesting payment screen for KBZ Pay PWA with the status code of " . $response->status() . ". See more at https://wap.kbzpay.com/pgw/uat/api/#/en/docs/PWA/api-precreate-en");
    }

	public function verifySignature(Request $request): bool {
		$payload = $request->json()->get('Request');
		$payloadCollection = collect($payload);
		$payloadWithoutSign = $payloadCollection->except(['sign', 'sign_type'])->sortKeys()->all();
		$stringToHash = http_build_query($payloadWithoutSign) . "&key=" . config("laravel-myanmar-payments.kbz_pay.app_key");
		return hash_equals(strtoupper(hash('SHA256', $stringToHash)), $payloadCollection->get('sign'));
	}
}
