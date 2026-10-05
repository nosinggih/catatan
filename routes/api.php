<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\EntryController;
use App\Http\Controllers\Api\EntryPhotoController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\TemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', MeController::class);
    Route::post('/consent', ConsentController::class);

    Route::middleware('terms')->group(function () {
        Route::get('/templates', TemplateController::class);

        Route::get('/activities', [ActivityController::class, 'index']);
        Route::post('/activities', [ActivityController::class, 'store']);
        Route::get('/activities/{activity}', [ActivityController::class, 'show'])->whereNumber('activity');
        Route::patch('/activities/{activity}', [ActivityController::class, 'update'])->whereNumber('activity');
        Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->whereNumber('activity');
        Route::get('/activities/{activity}/entries', [EntryController::class, 'index'])->whereNumber('activity');

        Route::post('/entries', [EntryController::class, 'store']);
        Route::patch('/entries/{uuid}', [EntryController::class, 'update'])->whereUuid('uuid');
        Route::delete('/entries/{uuid}', [EntryController::class, 'destroy'])->whereUuid('uuid');
        Route::post('/entries/{uuid}/photo', [EntryPhotoController::class, 'store'])->whereUuid('uuid');
        Route::get('/entries/{uuid}/photo', [EntryPhotoController::class, 'show'])->whereUuid('uuid');
        Route::delete('/entries/{uuid}/photo', [EntryPhotoController::class, 'destroy'])->whereUuid('uuid');

        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);
    });
});
