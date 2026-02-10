<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{
    public function login(UserLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! Auth::attempt($validated)) {
            return $this->errorResponse('Invalid credentials', null, 401);
        }

        $user = Auth::user();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 'Login successful');
    }
}
