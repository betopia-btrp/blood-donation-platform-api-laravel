<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');

        for ($i = 1; $i <= 2; $i++) {
            $orgUser = User::create([
                'name'      => "Test Organization $i",
                'email'     => "org$i@example.com",
                'password'  => $password,
                'role'      => 'organization',
                'is_active' => true,
            ]);

            Organization::create([
                'user_id'             => $orgUser->id,
                'org_name'            => "Dhaka Blood Bank Branch $i",
                'license_number'      => "LIC-2026-00$i",
                'verification_status' => 'approved',
                'contact_person'      => "Contact Person $i",
            ]);
        }
    }
}