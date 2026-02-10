<?php

use App\Http\Controllers\Api\UserAPIController;
use Illuminate\Support\Facades\Route;

Route::post('/login', function () {
    return '';
});

Route::post('/register', function () {
    return '';
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/logout', function () {
        return '';
    });

    Route::resource('users', UserAPIController::class);
});
