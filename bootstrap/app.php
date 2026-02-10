<?php

use App\Http\Middleware\Authenticate;
use App\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            return ApiResponse::error('You are not authenticated.', null, 401);
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            return ApiResponse::error('Not found.', null, 404);
        });

        $exceptions->render(function (ValidationException $e, $request) {
            return ApiResponse::error($e->getMessage(), $e->errors(), 422);
        });

        $exceptions->render(function (Exception $e, $request) {
            return ApiResponse::error($e->getMessage(), null, 500);
        });
    })->create();
