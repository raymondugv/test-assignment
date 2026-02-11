<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostAPIControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_index_returns_paginated_posts_list(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Post::factory()->count(5)->create(['author_id' => $user->id]);

        $response = $this->getJson('/api/posts');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $posts = isset($data['data']) ? $data['data'] : $data;
        $this->assertCount(5, $posts);
        $this->assertArrayHasKey('id', $posts[0]);
        $this->assertArrayHasKey('title', $posts[0]);
        $this->assertArrayHasKey('slug', $posts[0]);
        $this->assertArrayHasKey('content', $posts[0]);
    }

    public function test_index_respects_per_page_query_parameter(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Post::factory()->count(10)->create(['author_id' => $user->id]);

        $response = $this->getJson('/api/posts?per_page=5');

        $response->assertOk();

        $data = $response->json('data');
        $posts = isset($data['data']) ? $data['data'] : $data;
        $this->assertCount(5, $posts);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/posts');

        $response->assertUnauthorized();
    }

    public function test_store_creates_new_post_successfully(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $postData = [
            'title' => 'Introduction to Laravel',
            'slug' => 'introduction-to-laravel',
            'content' => 'Laravel is a powerful PHP framework for building web applications.',
        ];

        $response = $this->postJson('/api/posts', $postData);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'author',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Post created successfully')
            ->assertJsonPath('data.title', 'Introduction to Laravel')
            ->assertJsonPath('data.slug', 'introduction-to-laravel')
            ->assertJsonPath('data.content', 'Laravel is a powerful PHP framework for building web applications.');

        $this->assertDatabaseHas('posts', [
            'title' => 'Introduction to Laravel',
            'slug' => 'introduction-to-laravel',
            'author_id' => $user->id,
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/posts', [
            'title' => 'Test Post',
            'slug' => 'test-post',
            'content' => 'Content here',
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/posts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'slug', 'content']);
    }

    public function test_store_validates_slug_unique(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['slug' => 'existing-slug', 'author_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/posts', [
            'title' => 'Another Post',
            'slug' => 'existing-slug',
            'content' => 'Some content',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_show_returns_post_successfully(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'title' => 'My First Post',
            'slug' => 'my-first-post',
            'author_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'author',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Post retrieved successfully')
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.title', 'My First Post')
            ->assertJsonPath('data.slug', 'my-first-post');
    }

    public function test_show_returns_error_for_nonexistent_post(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/posts/99999');

        $response->assertJsonPath('success', false);
        $this->assertContains($response->status(), [404, 500]);
        $message = strtolower($response->json('message') ?? '');
        $this->assertTrue(
            str_contains($message, 'not found') || str_contains($message, 'no query results'),
            "Expected error message about not found, got: {$response->json('message')}"
        );
    }

    public function test_update_modifies_post_when_authorized(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'content' => 'Original content',
            'author_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Updated Title',
            'slug' => 'updated-slug',
            'content' => 'Updated content',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Post updated successfully')
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.slug', 'updated-slug')
            ->assertJsonPath('data.content', 'Updated content');

        $post->refresh();
        $this->assertEquals('Updated Title', $post->title);
        $this->assertEquals('updated-slug', $post->slug);
    }

    public function test_update_returns_403_when_updating_other_author_post(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'title' => 'Other Author Post',
            'slug' => 'other-author-post',
            'author_id' => $otherUser->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Tampered Title',
            'slug' => 'tampered-slug',
            'content' => 'Tampered content',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized access');

        $post->refresh();
        $this->assertEquals('Other Author Post', $post->title);
    }

    public function test_update_allows_partial_update(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'title' => 'Keep Title',
            'slug' => 'keep-slug',
            'content' => 'Original content',
            'author_id' => $user->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Only Title Updated',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Only Title Updated')
            ->assertJsonPath('data.slug', 'keep-slug')
            ->assertJsonPath('data.content', 'Original content');
    }

    public function test_destroy_deletes_post_when_authorized(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['author_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Post deleted successfully');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_destroy_returns_403_when_deleting_other_author_post(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['author_id' => $otherUser->id]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized access');

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $post = Post::factory()->create();

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertUnauthorized();
    }
}
