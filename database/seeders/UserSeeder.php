<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'John Developer',
                'email' => 'john@example.com',
            ],
            [
                'name' => 'Sarah Security',
                'email' => 'sarah@example.com',
            ],
            [
                'name' => 'Mike Manager',
                'email' => 'mike@example.com',
            ],
            [
                'name' => 'Lisa Analyst',
                'email' => 'lisa@example.com',
            ],
            [
                'name' => 'David Engineer',
                'email' => 'david@example.com',
            ]
        ];

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]);
        }
    }
}
