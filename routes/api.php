<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
    ]);
});

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
});
