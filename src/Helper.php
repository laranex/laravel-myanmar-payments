<?php

namespace Laranex\LaravelMyanmarPayments;
use Illuminate\Support\Collection;

class Helper{
    public static function generateQueryString($collection, $appKey): string
    {
        return $collection->sortKeys()->map(function ($value, $key) {
            return "$key=$value";
        })->implode("&") . "&key=$appKey";
    }

    public static function signCyberSource(Collection $collection): string
    {
        $secret = config("laravel-myanmar-payments.cyber_source.secret_key");
        $signedFieldNames = explode(",", $collection['signed_field_names']);
        $dataToSign = [];
        foreach ($signedFieldNames as $field) {
           $dataToSign[] = $field . "=" . $collection[$field];
        }
        $singableString = implode(",", $dataToSign);
        return base64_encode(hash_hmac('sha256', $singableString, $secret, true));
    }

    public static function hashAyaPgw(array $data): string
    {
        $config = config("laravel-myanmar-payments.aya_pgw");
        $hashString = collect($data)->values()->implode(":");
        return hash_hmac('sha256', $hashString, $config["app_secret"]);
    }
}
