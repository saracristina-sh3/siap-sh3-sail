<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthCore\Http\Controllers\AuthCoreController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('authcores', AuthCoreController::class)->names('authcore');
});
