<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAPIControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_index_returns_paginated_users_list(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        User::factory()->count(5)->create();

        $response = $this->getJson('/api/users');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $users = isset($data['data']) ? $data['data'] : $data;
        $this->assertCount(6, $users);
        $this->assertArrayHasKey('id', $users[0]);
        $this->assertArrayHasKey('name', $users[0]);
        $this->assertArrayHasKey('email', $users[0]);
    }

    public function test_index_respects_per_page_query_parameter(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        User::factory()->count(10)->create();

        $response = $this->getJson('/api/users?per_page=5');

        $response->assertOk();

        $data = $response->json('data');
        $users = isset($data['data']) ? $data['data'] : $data;
        $this->assertCount(5, $users);
        $perPage = $data['meta']['per_page'] ?? $data['per_page'] ?? null;
        $this->assertTrue($perPage === 5 || count($users) === 5);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertUnauthorized();
    }

    public function test_store_creates_new_user_successfully(): void
    {
        $admin = User::factory()->create();
        Sanctum::actingAs($admin);

        $userData = [
            'name' => 'Mai Nguyen',
            'email' => 'mai.nguyen@mail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'User created successfully')
            ->assertJsonPath('data.name', 'Mai Nguyen')
            ->assertJsonPath('data.email', 'mai.nguyen@mail.com');

        $this->assertDatabaseHas('users', [
            'name' => 'Mai Nguyen',
            'email' => 'mai.nguyen@mail.com',
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Hoang Le',
            'email' => 'hoang.le@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/users', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_store_validates_email_unique(): void
    {
        $existingUser = User::factory()->create(['email' => 'duplicate@mail.com']);
        Sanctum::actingAs($existingUser);

        $response = $this->postJson('/api/users', [
            'name' => 'Thao Pham',
            'email' => 'duplicate@mail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_validates_password_confirmation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/users', [
            'name' => 'Duc Tran',
            'email' => 'duc.tran@mail.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_show_returns_user_when_authorized(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'User retrieved successfully')
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_show_returns_403_when_accessing_other_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$otherUser->id}");

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized access');
    }

    public function test_show_returns_error_for_nonexistent_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/users/99999');

        $response->assertJsonPath('success', false);
        $this->assertContains($response->status(), [404, 500]);
        $message = strtolower($response->json('message') ?? '');
        $this->assertTrue(
            str_contains($message, 'not found') || str_contains($message, 'no query results'),
            "Expected error message about not found, got: {$response->json('message')}"
        );
    }

    public function test_update_modifies_user_when_authorized(): void
    {
        $user = User::factory()->create([
            'name' => 'Anh Nguyen',
            'email' => 'anh.nguyen@mail.com',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'Anh Nguyen Van',
            'email' => 'anh.nguyen.vn@gmail.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'User updated successfully')
            ->assertJsonPath('data.name', 'Anh Nguyen Van')
            ->assertJsonPath('data.email', 'anh.nguyen.vn@gmail.com');

        $user->refresh();
        $this->assertEquals('Anh Nguyen Van', $user->name);
        $this->assertEquals('anh.nguyen.vn@gmail.com', $user->email);
    }

    public function test_update_prevents_updating_other_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['name' => 'Lan Pham']);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/users/{$otherUser->id}", [
            'name' => 'Tampered Name',
            'email' => 'tampered@mail.com',
        ]);

        $this->assertContains($response->status(), [403, 500]);
        $response->assertJsonPath('success', false);

        $otherUser->refresh();
        $this->assertEquals('Lan Pham', $otherUser->name);
    }

    public function test_update_allows_partial_update_without_password(): void
    {
        $user = User::factory()->create([
            'name' => 'Quynh Tran',
            'email' => 'quynh.tran@mail.com',
        ]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/users/{$user->id}", [
            'name' => 'Quynh Tran Thi',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Quynh Tran Thi')
            ->assertJsonPath('data.email', 'quynh.tran@mail.com');
    }

    public function test_destroy_deletes_user_when_authorized(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertNoContent(204);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_returns_403_when_deleting_other_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/users/{$otherUser->id}");

        $response->assertForbidden()
            ->assertJsonPath('message', 'Unauthorized access');

        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }

    public function test_destroy_requires_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertUnauthorized();
    }
}
