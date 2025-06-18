<?php

namespace Laranex\LaravelMyanmarPayments;

use Illuminate\Http\Request;

class CyberSource
{
    protected function processTransaction(string $transactionId, string $referenceNumber, int $amount, string $currencyCode = "MMK", string $transactionType = "sale"): array
    {
        $csConfig = config("laravel-myanmar-payments.cyber_source");
        $signedFiledNames = "access_key,profile_id,transaction_uuid,signed_field_names,signed_date_time,locale,transaction_type,reference_number,amount,currency";
        $dataCollection = collect([
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
        ]);
        $signature = Helper::signCyberSource($dataCollection);
        $dataCollection = $dataCollection->merge(["signature" => $signature]);

        return [
            'url' => $csConfig["base_url"] . "/pay",
            'data' => $dataCollection
        ];
    }

    public function verifySignature(Request $request): bool
    {
        $payload = collect($request->all());
        $payloadSignature = Helper::signCyberSource($payload);
        return hash_equals($payload["signature"], $payloadSignature);
    }
}
