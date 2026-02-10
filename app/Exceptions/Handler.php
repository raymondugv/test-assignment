<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class Handler extends Exception
{
    public function render($request, Throwable $e)
    {
        if ($e instanceof ModelNotFoundException && $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
                'data' => null,
                'error' => null,
            ], 404);
        }

        return parent::render($request, $e);
    }
}
