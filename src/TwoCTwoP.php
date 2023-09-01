<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;


class TwoCTwoP
{
    /**
     * @throws Exception
     */
    public function getPaymentScreenUrl(string $orderId,$amount, string $nonceStr, string $backendResultUrl, string $currencyCode = "", string $frontendResultUrl = "", string $paymentDescription = "", array $userDefined = []): string
    {
        $config = config("laravel-myanmar-payments.2c2p");
        $merchantConfig = $config["merchants"];

        $currencyCode = $currencyCode ?? $merchantConfig["default"];

        $baseUrl = $config["base_url"];

        $merchantId = $merchantConfig[$currencyCode]["merchant_id"];
        $secretKey = $merchantConfig[$currencyCode]["secret_key"];

        $paymentDescription = $paymentDescription ?: "Payment for " . config("app.name");

        $paymentChannel = "";
        $promotion = "";
        $tokenize = "";
        $cardTokens = "";
        $tokenizeOnly = "";
        $interestType = "";
        $installmentPeriodFilter = "";
        $productCode = "";
        $recurring = "";
        $invoicePrefix = "";
        $recurringAmount = "";
        $allowAccumulate = "";
        $maxAccumulateAmount = "";
        $recurringInterval = "";
        $recurringCount = "";
        $chargeNextDate = "";
        $chargeOnDate = "";
        $paymentExpiry = "";
        $paymentRouteID = "";
        $statementDescriptor = "";
        $subMerchants = "";
        $locale = "";
        $frontendReturnUrl = $frontendResultUrl ?: config("app.url");
        $backendReturnUrl = $backendResultUrl;

        $userDefined = Helper::parseUserDefinedFields($userDefined);

        $payload = [
            //MANDATORY PARAMS
            "merchantID" => $merchantId,
            "invoiceNo" => $orderId,
            "description" => $paymentDescription,
            "amount" => $amount,
            "currencyCode" => $currencyCode,


            //OPTIONAL PARAMS
            "paymentChannel" => $paymentChannel,
            "promotion" => $promotion,
            "tokenize" => $tokenize,
            "cardTokens" => $cardTokens,
            "tokenizeOnly" => $tokenizeOnly,
            "interestType" => $interestType,
            "installmentPeriodFilter" => $installmentPeriodFilter,
            "productCode" => $productCode,
            "recurring" => $recurring,
            "invoicePrefix" => $invoicePrefix,
            "recurringAmount" => $recurringAmount,
            "allowAccumulate" => $allowAccumulate,
            "maxAccumulateAmount" => $maxAccumulateAmount,
            "recurringInterval" => $recurringInterval,
            "recurringCount" => $recurringCount,
            "chargeNextDate" => $chargeNextDate,
            "chargeOnDate" => $chargeOnDate,
            "paymentExpiry" => $paymentExpiry,
            "userDefined1" => $userDefined[0],
            "userDefined2" => $userDefined[1],
            "userDefined3" => $userDefined[2],
            "userDefined4" => $userDefined[3],
            "userDefined5" => $userDefined[4],
            "paymentRouteID" => $paymentRouteID,
            "statementDescriptor" => $statementDescriptor,
            "subMerchants" => $subMerchants,
            "locale" => $locale,
            "frontendReturnUrl" => $frontendReturnUrl,
            "backendReturnUrl" => $backendReturnUrl,

            //MANDATORY RANDOMIZER
            "nonceStr" => $nonceStr
        ];

        $payload = array_filter($payload);

        $this->validateData($backendResultUrl, $secretKey, $merchantId, $currencyCode);

        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        $data['payload'] = $jwt;

        $response = Http::post($baseUrl . "/PaymentToken", $data);

        if ($response->successful()) {
            $resData = $response->json();

            if (isset($resData["payload"])) {
                $response = explode(".", $resData["payload"]);
                $responseJson = json_decode(base64_decode($response[1]));
                return $responseJson->{"webPaymentUrl"};
            }
            throw new Exception("Something went wrong in requesting payment screen for 2C2P");
        }

        throw new Exception("Something went wrong in requesting payment screen for 2C2P");

    }


    /**
     * @throws Exception
     */
    private function validateData($backendResultUrl, $secretKey, $merchantId, $currencyCode): void
    {
        if (!$secretKey || !$merchantId) {
            throw new Exception("Invalid 2C2P Secret Key OR Invalid 2C2P Merchant Id");
        }

        if (!$currencyCode) {
            throw new Exception("Invalid Currency");
        }

        if (!filter_var($backendResultUrl, FILTER_VALIDATE_URL)) {
            throw  new Exception("Invalid backend URL, Be careful, this might lead to wrong data");
        }
    }

    public static function parseJWT(string $jwtToken, string $currencyCode): array
    {
        if(!collect(config("laravel-myanmar-payments.2c2p.merchants"))->except("default")->keys()->contains($currencyCode)) {
            throw new Exception("Provide currency code is not defined in config");
        }

        $secretKey = config("laravel-myanmar-payments.2c2p.merchants.$currencyCode.secret_key");
        return (array) JWT::decode($jwtToken, new Key($secretKey, 'HS256'));
    }

    public function verifySignature(Request $request, string $currencyCode = "MMK"): bool
    {
        $secretKey = config("laravel-myanmar-payments.2c2p.merchants.{$currencyCode}.secret_key");

        $decodedPayload = JWT::decode($request->payload, new Key($secretKey, 'HS256'));
        $decoded_array = (array) $decodedPayload;

        return "0000" == $decoded_array["respCode"];
    }
}