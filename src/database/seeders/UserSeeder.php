<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123'); // Universal testing password
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

        for ($i = 1; $i <= 5; $i++) {
            $user = User::create([
                'name'      => "Test User $i",
                'email'     => "user$i@example.com",
                'password'  => $password,
                'role'      => 'user',
                'is_active' => true,
            ]);

            UserProfile::create([
                'user_id'      => $user->id,
                'blood_group'  => $bloodGroups[array_rand($bloodGroups)], // Assigns a random blood group
                'division'     => 'Dhaka',
                'district'     => 'Dhaka',
                'area'         => "Area $i",
                'is_available' => true,
                'trust_score'  => 1.00,
            ]);
        }
    }
}