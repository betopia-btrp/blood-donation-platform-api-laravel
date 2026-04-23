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
        $user = User::create([
            'name'      => 'Test User',
            'email'     => fake()->unique()->safeEmail(),
            'password'  => bcrypt('password123'),
            'role'      => $role,
            'is_active' => $isActive,
        ]);

        if ($role === 'user') {
            UserProfile::create(['user_id' => $user->id]);
        }

        if ($role === 'organization') {
            Organization::create([
                'user_id'             => $user->id,
                'org_name'            => 'Test Org',
                'verification_status' => 'approved',
            ]);
        }

        return $user;
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
