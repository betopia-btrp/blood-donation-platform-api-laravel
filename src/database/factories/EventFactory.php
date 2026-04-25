<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $divisions = ['Dhaka', 'Chittagong', 'Rajshahi', 'Khulna', 'Sylhet'];
        $districts = ['Dhaka', 'Gazipur', 'Chittagong', 'Rajshahi', 'Khulna'];

        return [
            'organization_id' => Organization::factory(),
            'title'           => fake()->sentence(4),
            'description'     => fake()->paragraph(),
            'event_date'      => fake()->dateTimeBetween('now', '+3 months'),
            'location'        => fake()->address(),
            'district'        => fake()->randomElement($districts),
            'division'        => fake()->randomElement($divisions),
            'max_capacity'    => fake()->numberBetween(50, 200),
            'status'          => 'upcoming',
            'banner_image'    => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn() => ['status' => 'pending']);
    }

    public function completed(): static
    {
        return $this->state(fn() => ['status' => 'completed']);
    }
}
