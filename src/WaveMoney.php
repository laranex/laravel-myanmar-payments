<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class WaveMoney
{
    /**
     * @throws Exception
     */
    public function getPaymentScreenUrl(array $items, string $orderId, int $amount, string $merchantReferenceId, string $backendResultUrl, string $frontendResultUrl = "", string $paymentDescription = ""): string
    {
        $waveMoneyConfig = config("laravel-myanmar-payments.wave_money");
        $baseUrl = $waveMoneyConfig["base_url"];
        $timeToLiveInSeconds = $waveMoneyConfig["time_to_live_in_seconds"];

        $merchantName = $waveMoneyConfig["merchant_name"];
        $merchantId = $waveMoneyConfig["merchant_id"];
        $secretKey = $waveMoneyConfig["secret_key"];

        $frontendResultUrl = $frontendResultUrl ?: config("app.url");
        $paymentDescription = $paymentDescription ?: "Payment for " . config("app.name");

        $this->validateData($items, $amount, $backendResultUrl, $secretKey, $merchantId);

        $items = json_encode($items);
        $hash = hash_hmac("sha256", implode("", [$timeToLiveInSeconds, $merchantId, $orderId, $amount, $backendResultUrl, $merchantReferenceId]), $secretKey);

        $response = Http::acceptJson()->withOptions(["verify" => false, "http_errors" => false])
            ->post("$baseUrl/payment", [
                "time_to_live_in_seconds" => $timeToLiveInSeconds,
                "merchant_id" => $merchantId,
                "order_id" => $orderId,
                "merchant_reference_id" => $merchantReferenceId,
                "frontend_result_url" => $frontendResultUrl,
                "backend_result_url" => $backendResultUrl,
                "amount" => $amount,
                "payment_description" => $paymentDescription,
                "merchant_name" => $merchantName,
                "items" => $items,
                "hash" => $hash
            ]);


        if ($response->successful()) {
            $transactionId = $response->json()["transaction_id"];
            return "$baseUrl/authenticate?transaction_id=$transactionId";
        }
        throw new Exception("Something went wrong in requesting payment screen for Wave Money with the status code of " . $response->status(). ". See more at https://github.com/DigitalMoneyMyanmar/wppg-documentation?tab=readme-ov-file#response-code-and-message");
    }


    /**
     * @throws Exception
     */
    private function validateData($items, $amount, $backendResultUrl, $secretKey, $merchantId): void
    {

        if (!$secretKey || !$merchantId) {
            throw new Exception("Invalid Wave Money Secret Key OR Invalid Wave Merchant Id");
        }

        if ($amount < 0) {
            throw new Exception("Amount cannot be less than 0");
        }

        if (!count($items)) {
            throw new Exception("Invalid items structure");
        }

        /**
         *   [
         *      ["name" => "Product 1", "amount" => 100],
         *      ["name" => "Product 2", "amount" => 150]
         *   ]
         */
        foreach ($items as $item) {
            if (!array_key_exists("name", $item) || !array_key_exists("amount", $item) || !is_string($item["name"]) || !is_numeric($item["amount"])) {
                throw new Exception("Invalid items structure");
            }
        }

        if (!filter_var($backendResultUrl, FILTER_VALIDATE_URL)) {
            throw  new Exception("Invalid backend URL, Be careful, this might lead to wrong data");
        }
    }

    public function verifyWaveSignature(Request $request): bool
    {
        $secretKey = config("laravel-myanmar-payments.wave_money.secret_key");

        return $request->get("status") === "PAYMENT_CONFIRMED" && hash_hmac('sha256', implode("", [

                $request->get("status"),

                $request->get("timeToLiveSeconds"),

                $request->get("merchantId"),

                $request->get("orderId"),

                $request->get("amount"),

                $request->get("backendResultUrl"),

                $request->get("merchantReferenceId"),

                $request->get("initiatorMsisdn"),

                $request->get("transactionId"),

                $request->get("paymentRequestId"),

                $request->get("requestTime"),

            ]), $secretKey) === $request->get("hashValue");
    }

}
