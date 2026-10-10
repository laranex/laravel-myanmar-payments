<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    |
    | Only the gateways you call need settings, and every setting of those
    | gateways is required. Each gateway uses its production endpoints; to
    | test against UAT, set the URL overrides to the UAT URLs.
    |
    */

    'kbz_pay' => [
        'app_id' => env('KBZ_PAY_APP_ID'),
        'app_key' => env('KBZ_PAY_APP_KEY'),
        'merchant_code' => env('KBZ_PAY_MERCHANT_CODE'),
        'api_url' => env('KBZ_PAY_BASE_URL'),
        'pwa_url' => env('KBZ_PAY_PWA_BASE_REDIRECT_URL'),
    ],

    'wave_money' => [
        'merchant_id' => env('WAVE_MONEY_MERCHANT_ID'),
        'secret_key' => env('WAVE_MONEY_SECRET_KEY'),
        'merchant_name' => env('WAVE_MONEY_MERCHANT_NAME'),
        'time_to_live_in_seconds' => env('WAVE_MONEY_TIME_TO_LIVE_IN_SECONDS'),
        'base_url' => env('WAVE_MONEY_BASE_URL'),
        'authenticate_url' => env('WAVE_MONEY_AUTHENTICATE_URL'),
    ],

    'aya_pay' => [
        'app_key' => env('AYA_PAY_APP_KEY', env('AYA_PGW_APP_KEY')),
        'app_secret' => env('AYA_PAY_APP_SECRET', env('AYA_PGW_APP_SECRET')),
        'base_url' => env('AYA_PAY_BASE_URL', env('AYA_PGW_BASE_URL')),
    ],

    'yoma_mmqr' => [
        'merchant_id' => env('YOMA_MMQR_MERCHANT_ID'),
        'client_id' => env('YOMA_MMQR_CLIENT_ID'),
        'client_secret' => env('YOMA_MMQR_CLIENT_SECRET'),
        'webhook_hashkey' => env('YOMA_MMQR_WEBHOOK_HASHKEY'),
        'webhook_secret' => env('YOMA_MMQR_WEBHOOK_SECRET'),
        'base_url' => env('YOMA_MMQR_BASE_URL'),
        'api_version' => env('YOMA_MMQR_API_VERSION'),
    ],

    'cyber_source' => [
        'profile_id' => env('CYBER_SOURCE_PROFILE_ID'),
        'access_key' => env('CYBER_SOURCE_ACCESS_KEY'),
        'secret_key' => env('CYBER_SOURCE_SECRET_KEY'),
        'base_url' => env('CYBER_SOURCE_BASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    |
    | Seconds before a gateway API call gives up. Required by every gateway
    | that calls an API (all but CyberSource), as its `timeout_in_seconds`.
    |
    */

    'http' => [
        'timeout' => env('MYANMAR_PAYMENTS_HTTP_TIMEOUT'),
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
    | `redirect($payment->autoSubmitUrl)`. Links expire after `ttl_minutes`,
    | which is required while the route is enabled.
    |
    */

    'form_route' => [
        'enabled' => true,
        'path' => 'myanmar-payments/form',
        'middleware' => ['web'],
        'ttl_minutes' => env('MYANMAR_PAYMENTS_FORM_TTL_MINUTES'),
    ],

];
