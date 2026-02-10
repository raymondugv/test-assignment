<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserLoginRequest;
use App\Http\Resources\UserResource;
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
            'user' => new UserResource($user),
        ], 'Login successful');
    }

    public function logout(): JsonResponse
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'currentAccessToken') && $token = $user->currentAccessToken()) {
            $token->delete();
        }

        return $this->successResponse(null, 'Successfully logged out');
    }
}
