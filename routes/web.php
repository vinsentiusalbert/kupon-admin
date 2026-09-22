<?php

use App\Http\Controllers\MicrositeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MicrositeController::class, 'index']);
Route::post('/outlet/check', [MicrositeController::class, 'checkOutlet'])
    ->name('outlet.check');
Route::post('/redeem/phone', [MicrositeController::class, 'redeemPhone'])
    ->middleware('throttle:30,1')
    ->name('phone.redeem');
