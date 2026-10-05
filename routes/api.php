<?php

use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\MeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', MeController::class);
    Route::post('/consent', ConsentController::class);
});
