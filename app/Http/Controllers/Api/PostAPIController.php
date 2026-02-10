<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PostAPIController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $perPage = request()->query('per_page', 15);

        $posts = Post::paginate($perPage);

        return $this->successResponse(PostResource::collection($posts));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $post = Post::create($validated);

        return $this->successResponse(new PostResource($post), 'Post created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        return $this->successResponse(new PostResource($post), 'Post retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $post = Post::findOrFail($id);

        if ($post->author_id != $user->id) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $validated = $request->validated();
        $post->update($validated);

        return $this->successResponse(new PostResource($post), 'Post updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $post = Post::findOrFail($id);

        if ($post->author_id != $user->id) {
            return $this->errorResponse('Unauthorized access', null, 403);
        }

        $post->delete();

        return $this->successResponse(null, 'Post deleted successfully');
    }
}
