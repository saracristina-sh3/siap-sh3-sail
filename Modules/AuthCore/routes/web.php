<?php

use Illuminate\Support\Facades\Route;
use Modules\AuthCore\Http\Controllers\AuthCoreController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('authcores', AuthCoreController::class)->names('authcore');
});
