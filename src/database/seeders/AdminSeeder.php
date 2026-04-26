<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = \App\Models\Role::where('name', 'admin')->first();

        User::firstOrCreate(
            ['email' => 'admin@blood.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );
    }
}
