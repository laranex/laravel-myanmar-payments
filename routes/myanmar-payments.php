<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laranex\LaravelMyanmarPayments\Http\Controllers\FormPaymentController;

if (config('myanmar-payments.form_route.enabled', true)) {
    Route::middleware(config('myanmar-payments.form_route.middleware', ['web']))
        ->get(config('myanmar-payments.form_route.path', 'myanmar-payments/form'), FormPaymentController::class)
        ->name('myanmar-payments.form');
}
