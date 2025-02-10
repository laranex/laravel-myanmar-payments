<?php

namespace Laranex\LaravelMyanmarPayments;
use Exception;
use Illuminate\Support\Collection;

class Helper{
    /**
     * @throws Exception
     */
    public static function parseUserDefinedFields($userDefined) {
        if (count($userDefined) > 5) {
            throw new Exception("only 5 User defined values can be existed");
        }

        $keys = range(1,5);
        for($index = 0; $index < count($keys); $index++) {
            $userDefined[$index] = $userDefined[$index] ?? "";
        }

        return $userDefined;
    }

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
}
