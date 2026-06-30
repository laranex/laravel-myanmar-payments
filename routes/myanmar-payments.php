<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;

Route::get('/myanmar-payments/form', function (Request $request) {
    $data = json_decode(Crypt::decryptString($request->query('payload', '')), true);

    if (! $data || ! isset($data['formUrl'], $data['formData'])) {
        abort(400, 'Invalid payment form payload.');
    }

    $inputs = implode('', array_map(
        fn ($key, $value) => '<input type="hidden" name="'.e($key).'" value="'.e($value).'">',
        array_keys($data['formData']),
        array_values($data['formData']),
    ));

    return response(<<<HTML
        <!DOCTYPE html><html><body>
        <form id="f" method="POST" action="{$data['formUrl']}">{$inputs}</form>
        <script>document.getElementById('f').submit()</script>
        </body></html>
        HTML);
})->name('myanmar-payments.form');
