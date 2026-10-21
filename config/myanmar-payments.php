<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    |
    | Only the gateways you call need settings, and every setting of those
    | gateways is required, URLs included: set production's URLs, or the
    | gateway's UAT URLs to test. Wave Pay requests payments at
    | WAVE_PAY_BASE_URL and sends the customer to WAVE_PAY_BASE_REDIRECT_URL.
    | Wave Pay Mini App requests access tokens at WAVE_PAY_MINI_APP_BASE_URL
    | (production: https://api.wavemoney.io:8100, UAT:
    | https://preprodnewapi.wavemoney.io).
    |
    */

    'kbz_pay' => [
        'app_id' => env('KBZ_PAY_APP_ID'),
        'app_key' => env('KBZ_PAY_APP_KEY'),
        'merchant_code' => env('KBZ_PAY_MERCHANT_CODE'),
        'api_url' => env('KBZ_PAY_BASE_URL'),
        'pwa_url' => env('KBZ_PAY_PWA_BASE_REDIRECT_URL'),
        'time_to_live_in_minutes' => env('KBZ_PAY_TIME_TO_LIVE_IN_MINUTES'),
    ],

    'wave_pay' => [
        'merchant_id' => env('WAVE_PAY_MERCHANT_ID'),
        'secret_key' => env('WAVE_PAY_SECRET_KEY'),
        'merchant_name' => env('WAVE_PAY_MERCHANT_NAME'),
        'time_to_live_in_seconds' => env('WAVE_PAY_TIME_TO_LIVE_IN_SECONDS'),
        'base_url' => env('WAVE_PAY_BASE_URL'),
        'base_redirect_url' => env('WAVE_PAY_BASE_REDIRECT_URL'),
    ],

    'wave_pay_mini_app' => [
        'client_id' => env('WAVE_PAY_MINI_APP_CLIENT_ID'),
        'client_secret' => env('WAVE_PAY_MINI_APP_CLIENT_SECRET'),
        'mini_app_id' => env('WAVE_PAY_MINI_APP_MINI_APP_ID'),
        'secret_key' => env('WAVE_PAY_MINI_APP_SECRET_KEY'),
        'base_url' => env('WAVE_PAY_MINI_APP_BASE_URL'),
    ],

    'aya_pay' => [
        'app_key' => env('AYA_PAY_APP_KEY'),
        'app_secret' => env('AYA_PAY_APP_SECRET'),
        'base_url' => env('AYA_PAY_BASE_URL'),
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
    | Seconds before a gateway API call gives up, connecting included, 30
    | by default. Every gateway that calls an API (all but CyberSource)
    | uses this one timeout.
    |
    */

    'http' => [
        'timeout' => env('MYANMAR_PAYMENTS_HTTP_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | The cache store that keeps Yoma MMQR and Wave Pay Mini App access
    | tokens between requests. Null uses your default store.
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
    | which is required while the route is enabled. The page needs no
    | session or cookies, so it runs without middleware by default; add your
    | own (e.g. throttling or a CSP nonce through `Vite::useCspNonce()`).
    |
    */

    'form_route' => [
        'enabled' => true,
        'path' => 'myanmar-payments/form',
        'middleware' => [],
        'ttl_minutes' => env('MYANMAR_PAYMENTS_FORM_TTL_MINUTES'),
    ],

];
