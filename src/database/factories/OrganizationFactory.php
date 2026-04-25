<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'             => User::factory()->organization(),
            'org_name'            => fake()->company(),
            'license_number'      => 'LIC-' . fake()->year() . '-' . fake()->numerify('###'),
            'verification_status' => 'approved',
            'contact_person'      => fake()->name(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => ['verification_status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state(fn() => ['verification_status' => 'rejected']);
    }
}
