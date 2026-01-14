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
                'company' => 'TechCorp Inc',
            ],
            [
                'name' => 'Sarah Security',
                'email' => 'sarah@example.com',
                'company' => 'SecureNet Ltd',
            ],
            [
                'name' => 'Mike Manager',
                'email' => 'mike@example.com',
                'company' => 'BuildSystems Co',
            ],
            [
                'name' => 'Lisa Analyst',
                'email' => 'lisa@example.com',
                'company' => 'DataFlow Solutions',
            ],
            [
                'name' => 'David Engineer',
                'email' => 'david@example.com',
                'company' => 'CloudHost Systems',
            ]
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
