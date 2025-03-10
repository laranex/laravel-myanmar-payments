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
    "kbz_pay" => [
        "base_url" => env("KBZ_PAY_BASE_URL", "http://api.kbzpay.com/payment/gateway/uat"),
        "merchant_name" => env("KBZ_PAY_MERCHANT_NAME", env("APP_NAME", "LARAVEL")),
        "merchant_code" => env("KBZ_PAY_MERCHANT_CODE"),
        "app_id" => env("KBZ_PAY_APP_ID"),
        "app_key" => env("KBZ_PAY_APP_KEY"),
        "pwa" => [
            "base_redirect_url" => env("KBZ_PAY_PWA_BASE_REDIRECT_URL", "https://static.kbzpay.com/pgw/uat/pwa/#"),
        ],
        "qr" => []
    ],
    "2c2p" => [
        "base_url" => env("2C2P_BASE_URL", "https://sandbox-pgw.2c2p.com/payment/4.1"),
        "merchants" => [

            "default" => "MMK",

            "MMK" => [
                "secret_key" => env("2C2P_MMK_SECRET_KEY"),
                "merchant_id" => env("2C2P_MMK_MERCHANT_ID"),
                "currency_code" => "MMK"
            ],

            "USD" => [
                "secret_key" => env("2C2P_USD_SECRET_KEY"),
                "merchant_id" => env("2C2P_USD_MERCHANT_ID"),
                "currency_code" => "USD"
            ]
        ]
    ],
    "cyber_source" => [
        "base_url" => env("CYBER_SOURCE_BASE_URL", ""),
        "profile_id" => env("CYBER_SOURCE_PROFILE_ID", ""),
        "access_key" => env("CYBER_SOURCE_ACCESS_KEY", ""),
        "secret_key" => env("CYBER_SOURCE_SECRET_KEY", "")
    ],
    "aya_pgw" => [
        "base_url" => env("AYA_PGW_BASE_URL", ""),
        "app_key" => env("AYA_PGW_APP_KEY", ""),
        "app_secret" => env("AYA_PGW_APP_SECRET", "")
    ],
];
