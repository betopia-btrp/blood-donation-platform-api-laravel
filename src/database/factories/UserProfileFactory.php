<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    public function definition(): array
    {
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
        $divisions   = ['Dhaka', 'Chittagong', 'Rajshahi', 'Khulna', 'Sylhet', 'Barisal', 'Rangpur', 'Mymensingh'];
        $districts   = ['Dhaka', 'Gazipur', 'Narayanganj', 'Chittagong', 'Rajshahi', 'Khulna', 'Sylhet', 'Comilla'];

        return [
            'user_id'           => User::factory(),
            'blood_group'       => fake()->randomElement($bloodGroups),
            'division'          => fake()->randomElement($divisions),
            'district'          => fake()->randomElement($districts),
            'area'              => fake()->word(),
            'is_available'      => fake()->boolean(80),
            'trust_score'       => fake()->randomFloat(2, 0.5, 1.0),
            'last_donation_date' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'avatar_url'        => null,
        ];
    }
}
