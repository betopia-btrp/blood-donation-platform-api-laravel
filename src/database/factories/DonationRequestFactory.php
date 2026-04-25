<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonationRequestFactory extends Factory
{
    public function definition(): array
    {
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
        $districts   = ['Dhaka', 'Gazipur', 'Chittagong', 'Rajshahi', 'Khulna'];

        return [
            'requester_user_id' => User::factory(),
            'blood_group'       => fake()->randomElement($bloodGroups),
            'quantity'          => fake()->numberBetween(1, 5),
            'hospital_name'     => fake()->company() . ' Hospital',
            'division'          => 'Dhaka',
            'district'          => fake()->randomElement($districts),
            'area'              => fake()->word(),
            'location'          => fake()->address(),
            'note'              => fake()->optional()->sentence(),
            'status'            => 'open',
            'needed_at'         => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }

    public function fulfilled(): static
    {
        return $this->state(fn() => ['status' => 'fulfilled']);
    }
}
