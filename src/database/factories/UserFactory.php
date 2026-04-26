<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition(): array
    {
        $role = Role::where('name', 'user')->first();

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ];
    }

    public function organization(): static
    {
        return $this->state(function () {
            $role = Role::where('name', 'organization')->first();
            return ['role_id' => $role->id];
        });
    }

    public function admin(): static
    {
        return $this->state(function () {
            $role = Role::where('name', 'admin')->first();
            return ['role_id' => $role->id];
        });
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }
}