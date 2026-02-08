<?php

use App\Http\Controllers\MicrositeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MicrositeController::class, 'index']);
Route::post('/outlet/check', [MicrositeController::class, 'checkOutlet'])
    ->name('outlet.check');
