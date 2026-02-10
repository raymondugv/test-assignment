<?php

use App\Http\Controllers\Api\UserAPIController;
use App\Http\Controllers\UserAuthController;
use Illuminate\Support\Facades\Route;

Route::group(['controller' => UserAuthController::class], function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::resource('users', UserAPIController::class);

    Route::post('/logout', [UserAuthController::class, 'logout']);
});
