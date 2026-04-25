<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventRegistrationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id'          => Event::factory(),
            'profile_id'        => UserProfile::factory(),
            'registration_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'attendance_status' => fake()->randomElement(['registered', 'attended', 'absent']),
        ];
    }
}
