<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Admin Kurator 1',
                'first_name' => 'Admin',
                'last_name' => 'Kurator 1',
                'username' => 'admin1',
                'email' => 'admin1@dch.test',
                'password' => 'AdminDCH#101',
            ],
            [
                'name' => 'Admin Kurator 2',
                'first_name' => 'Admin',
                'last_name' => 'Kurator 2',
                'username' => 'admin2',
                'email' => 'admin2@dch.test',
                'password' => 'AdminDCH#202',
            ],
            [
                'name' => 'Admin Kurator 3',
                'first_name' => 'Admin',
                'last_name' => 'Kurator 3',
                'username' => 'admin3',
                'email' => 'admin3@dch.test',
                'password' => 'AdminDCH#303',
            ],
        ];

        foreach ($admins as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'username' => $data['username'],
                    'role' => 'admin',
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
