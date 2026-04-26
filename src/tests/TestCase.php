<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Organization;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function createUser(string $role = 'user', bool $isActive = true): User
    {
        $roleModel = \App\Models\Role::where('name', $role)->first();

        $user = User::create([
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password123'),
            'role_id' => $roleModel->id,
            'is_active' => $isActive,
        ]);

        if ($role === 'user') {
            UserProfile::create([
                'user_id' => $user->id,
                'full_name' => fake()->name(),
            ]);
        }

        if ($role === 'organization') {
            Organization::create([
                'user_id' => $user->id,
                'org_name' => fake()->company(),
                'verification_status' => 'approved',
            ]);
        }

        return $user->fresh('role');
    }

    protected function getToken(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    protected function authHeader(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $this->getToken($user)];
    }
}
