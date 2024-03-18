<?php

namespace Laranex\LaravelMyanmarPayments;
use Exception;

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
}
