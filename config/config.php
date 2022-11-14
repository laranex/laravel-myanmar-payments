<?php



/*
 * You can place your custom package configuration in here.
 */
return [
    "wave_money" => [
        "base_url" => env("WAVE_MONEY_BASE_URL", "https://testpayments.wavemoney.io:8107"),
        "time_to_live_in_seconds" => env("WAVE_MONEY_TIME_TO_LIVE_IN_SECONDS", 300),
        "merchant_name" => env("WAVE_MONEY_MERCHANT_NAME", env("APP_NAME", "LARAVEL")),
        "merchant_id" => env("WAVE_MONEY_MERCHANT_ID"),
        "secret_key" => env("WAVE_MONEY_SECRET_KEY")
    ],
    "2c2p" => [
        "base_url" => env("2C2P_BASE_URL", "https://sandbox-pgw.2c2p.com/payment/4.1"),
        "merchant_id" => env("2C2P_MERCHANT_ID"),
        "secret_key" => env("2C2P_SECRET_KEY"),
        "currency_code" => env("2C2P_CURRENCY_CODE"),
    ]
];
