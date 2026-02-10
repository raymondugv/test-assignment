<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $perPage = request()->query('per_page', 15);

        $users = User::paginate($perPage);

        return $this->successResponse(UserResource::collection($users));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        return $this->successResponse(new UserResource($user), 'User created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $authUser = Auth::user();

        $user = User::findOrFail($id);

        if (! $authUser || ! $user || $authUser->id != $user->id) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        return $this->successResponse(new UserResource($user), 'User retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        $user = Auth::user();

        if (! $user || $user->id != $id) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        return $this->successResponse(new UserResource($user), 'User updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        if (! $user || $user->id != $id) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $user->delete();

        return $this->successResponse(null, 'User deleted successfully', 204);
    }
}
