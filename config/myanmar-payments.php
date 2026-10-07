<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    |
    | Only the gateways you call need credentials. `sandbox` picks each
    | gateway's UAT endpoints; set it to false and use production credentials
    | when you go live. URL overrides are optional.
    |
    */

    'kbz_pay' => [
        'sandbox' => env('KBZ_PAY_SANDBOX', true),
        'app_id' => env('KBZ_PAY_APP_ID'),
        'app_key' => env('KBZ_PAY_APP_KEY'),
        'merchant_code' => env('KBZ_PAY_MERCHANT_CODE'),
        'api_url' => env('KBZ_PAY_BASE_URL'),
        'pwa_url' => env('KBZ_PAY_PWA_BASE_REDIRECT_URL'),
    ],

    'wave_money' => [
        'sandbox' => env('WAVE_MONEY_SANDBOX', true),
        'merchant_id' => env('WAVE_MONEY_MERCHANT_ID'),
        'secret_key' => env('WAVE_MONEY_SECRET_KEY'),
        'merchant_name' => env('WAVE_MONEY_MERCHANT_NAME', env('APP_NAME')),
        'time_to_live_in_seconds' => env('WAVE_MONEY_TIME_TO_LIVE_IN_SECONDS', 300),
        'base_url' => env('WAVE_MONEY_BASE_URL'),
        'authenticate_url' => env('WAVE_MONEY_AUTHENTICATE_URL'),
    ],

    'aya_pay' => [
        'sandbox' => env('AYA_PAY_SANDBOX', true),
        'app_key' => env('AYA_PAY_APP_KEY', env('AYA_PGW_APP_KEY')),
        'app_secret' => env('AYA_PAY_APP_SECRET', env('AYA_PGW_APP_SECRET')),
        'base_url' => env('AYA_PAY_BASE_URL', env('AYA_PGW_BASE_URL')),
    ],

    'yoma_mmqr' => [
        'sandbox' => env('YOMA_MMQR_SANDBOX', true),
        'merchant_id' => env('YOMA_MMQR_MERCHANT_ID'),
        'client_id' => env('YOMA_MMQR_CLIENT_ID'),
        'client_secret' => env('YOMA_MMQR_CLIENT_SECRET'),
        'webhook_hashkey' => env('YOMA_MMQR_WEBHOOK_HASHKEY'),
        'webhook_secret' => env('YOMA_MMQR_WEBHOOK_SECRET'),
        'base_url' => env('YOMA_MMQR_BASE_URL'),
        'api_version' => env('YOMA_MMQR_API_VERSION', 'v1rc'),
    ],

    'cyber_source' => [
        'sandbox' => env('CYBER_SOURCE_SANDBOX', true),
        'profile_id' => env('CYBER_SOURCE_PROFILE_ID'),
        'access_key' => env('CYBER_SOURCE_ACCESS_KEY'),
        'secret_key' => env('CYBER_SOURCE_SECRET_KEY'),
        'base_url' => env('CYBER_SOURCE_BASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    */

    'http' => [
        'timeout' => env('MYANMAR_PAYMENTS_HTTP_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | The cache store that keeps Yoma MMQR access tokens between requests.
    | Null uses your default store.
    |
    */

    'cache_store' => env('MYANMAR_PAYMENTS_CACHE_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Auto-submit Form Route
    |--------------------------------------------------------------------------
    |
    | AYA Pay and CyberSource need the customer's browser to POST a signed
    | form. This route renders that form and submits it, so you can simply
    | `redirect($payment->autoSubmitUrl)`. Links expire after `ttl_minutes`.
    |
    */

    'form_route' => [
        'enabled' => true,
        'path' => 'myanmar-payments/form',
        'middleware' => ['web'],
        'ttl_minutes' => 30,
    ],

];
