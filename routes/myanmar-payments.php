<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Laranex\LaravelMyanmarPayments\Http\Controllers\FormPaymentController;

if (Config::get('myanmar-payments.form_route.enabled', true)) {
    Route::middleware(Config::get('myanmar-payments.form_route.middleware', ['web']))
        ->get(Config::get('myanmar-payments.form_route.path', 'myanmar-payments/form'), FormPaymentController::class)
        ->name('myanmar-payments.form');
}
