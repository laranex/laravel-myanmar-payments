<?php

namespace Laranex\LaravelMyanmarPayments;

use Illuminate\Http\Request;

class CyberSource
{
    protected function processTransaction(string $transactionId, string $referenceNumber, float $amount, string $currencyCode = "MMK", string $transactionType = "sale"): array
    {
        $csConfig = config("laravel-myanmar-payments.cyber_source");
        $signedFiledNames = "access_key,profile_id,transaction_uuid,signed_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency";
        $dataCollection = [
            "access_key" => $csConfig["access_key"],
            "profile_id" => $csConfig["profile_id"],
            "transaction_uuid" => $transactionId,
            "signed_field_names" => $signedFiledNames,
            "signed_date_time" => gmdate("Y-m-d\TH:i:s\Z"),
            "locale" => "en",
            "transaction_type" => $transactionType,
            "reference_number" => $referenceNumber,
            "amount" => number_format((float) $amount, 2, ".", ""),
            "currency" => $currencyCode
        ];

        $this->validateData($csConfig["access_key"], $csConfig["profile_id"], $currencyCode, $amount);

        $signature = Helper::signCyberSource($dataCollection);
        $dataCollection = collect($dataCollection)->merge(["signature" => $signature]);

        return [
            'url' => $csConfig["base_url"] . "/pay",
            'data' => $dataCollection
        ];
    }

    public function verifySignature(Request $request): bool
    {
        $payloadSignature = Helper::signCyberSource(($request->all()));

        return strcmp($request->signature, $payloadSignature) == 0;
    }

    public function decode(Requet $request): array
    {        
        return $request->all();
    }

    /**
     * @throws Exception
     */
    private function validateData($accessKey, $profileId, $currencyCode, $amount): void
    {
        if (! $accessKey || ! $profileId) {
            throw new Exception("Invalid Cyber Soucre Access Key OR Invalid CyberSource Profile Id");
        }

        if (!$currencyCode) {
            throw new Exception("Invalid Currency");
        }

        if (!$amount) {
            throw new Exception("Invalid Amount");
        }
    }

}
