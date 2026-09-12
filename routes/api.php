<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->group(function () {
    Route::post('/', [PaymentController::class, 'store']);
    Route::post('/webhook', [PaymentController::class, 'webhook']);
    Route::get('/{reference}', [PaymentController::class, 'show']);
});
