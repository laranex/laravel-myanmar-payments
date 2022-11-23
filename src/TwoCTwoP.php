<?php

namespace Laranex\LaravelMyanmarPayments;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;


class TwoCTwoP
{
    /**
     * @throws Exception
     */
    public function getPaymentScreenUrl(string $orderId, int $amount, string $nonceStr, string $backendResultUrl, string $currencyCode, string $frontendResultUrl = "", string $paymentDescription = ""): string
    {
        $config2c2p = config("laravel-myanmar-payments.2c2p");

        $baseUrl = $config2c2p["base_url"];
        $merchantId = $config2c2p["merchant_id"];
        $secretKey = $config2c2p["secret_key"];

        $paymentDescription = $paymentDescription ?: "Payment for " . config("app.name");
        $amount = sprintf("%012d", $amount);

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
        $userDefined1 = "";
        $userDefined2 = "";
        $userDefined3 = "";
        $userDefined4 = "";
        $userDefined5 = "";
        $paymentRouteID = "";
        $statementDescriptor = "";
        $subMerchants = "";
        $locale = "";
        $frontendReturnUrl = $frontendResultUrl ?: config("app.url");
        $backendReturnUrl = $backendResultUrl;


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
            "userDefined1" => $userDefined1,
            "userDefined2" => $userDefined2,
            "userDefined3" => $userDefined3,
            "userDefined4" => $userDefined4,
            "userDefined5" => $userDefined5,
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

        $this->validateData($amount, $backendResultUrl, $secretKey, $merchantId, $currencyCode);

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
    private function validateData($amount, $backendResultUrl, $secretKey, $merchantId, $currencyCode): void
    {
        if (!$secretKey || !$merchantId) {
            throw new Exception("Invalid 2C2P Secret Key OR Invalid 2C2P Merchant Id");
        }

        if (strlen($amount) != 12) {
            throw new Exception("Amount format is not the same with 2C2P requirement");
        }

        if (!$currencyCode) {
            throw new Exception("Invalid Currency");
        }

        if (!filter_var($backendResultUrl, FILTER_VALIDATE_URL)) {
            throw  new Exception("Invalid backend URL, Be careful, this might lead to wrong data");
        }
    }

    public static function parseJWT(string $jwtToken): array
    {
        $secretKey = config("laravel-myanmar-payments.2c2p.secret_key");
        return (array) JWT::decode($jwtToken, new Key($secretKey, 'HS256'));
    }
}
