<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@tourism.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager@tourism.com'],
            [
                'name' => 'Operations Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@tourism.com'],
            [
                'name' => 'Front Desk Staff',
                'password' => Hash::make('password'),
                'role' => 'staff',
                'is_active' => true,
            ]
        );
    }
}
