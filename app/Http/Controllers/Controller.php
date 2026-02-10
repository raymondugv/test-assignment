<?php

namespace App\Http\Controllers;

use App\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function successResponse(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function errorResponse(string $message, mixed $errors = null, int $status = 500): JsonResponse
    {
        return ApiResponse::error($message, $errors, $status);
    }
}
