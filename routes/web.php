<?php

use App\Http\Controllers\Auth\DevLoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::get('/google/redirect', [GoogleController::class, 'redirect']);
    Route::get('/google/callback', [GoogleController::class, 'callback']);
    Route::get('/dev-login', DevLoginController::class);
    Route::post('/logout', LogoutController::class)->middleware('auth');
});

// Every other path is handled by the Vue router.
Route::view('/{any?}', 'app')->where('any', '^(?!api/|auth/|up$|build/).*$');
