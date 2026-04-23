<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Rahim Uddin',
            'email' => 'rahim@test.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'token']
            ]);
    }

    public function test_organization_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Org',
            'email' => 'org@test.com',
            'password' => 'password123',
            'role' => 'organization',
        ]);

        $response->assertStatus(201);
    }

    public function test_admin_cannot_self_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_login()
    {
        $user = $this->createUser('user');

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['user', 'token']
            ]);
    }

    public function test_inactive_user_cannot_login()
    {
        $user = $this->createUser('user', false);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_invalid_credentials_rejected()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_me()
    {
        $user = $this->createUser('user');

        $response = $this->getJson('/api/auth/me', $this->authHeader($user));

        $response->assertStatus(200)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_user_can_logout()
    {
        $user = $this->createUser('user');

        $response = $this->postJson('/api/auth/logout', [], $this->authHeader($user));

        $response->assertStatus(200);
    }

    public function test_user_can_change_password()
    {
        $user = $this->createUser('user');

        $response = $this->postJson('/api/auth/change-password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ], $this->authHeader($user));

        $response->assertStatus(200);
    }

    public function test_wrong_current_password_rejected()
    {
        $user = $this->createUser('user');

        $response = $this->postJson('/api/auth/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ], $this->authHeader($user));

        $response->assertStatus(400);
    }
}
