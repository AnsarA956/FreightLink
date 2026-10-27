<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Admin User',    'admin@freightlink.test',    '03000000001', 'admin',    'Admin@123'],
            ['Staff User',    'staff@freightlink.test',    '03000000002', 'staff',    'Staff@123'],
            ['Driver User',   'driver@freightlink.test',   '03000000003', 'driver',   'Driver@123'],
            ['Customer User', 'customer@freightlink.test', '03000000004', 'customer', 'Customer@123'],
        ];

        foreach ($accounts as [$name, $email, $phone, $role, $password]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name'      => $name,
                    'phone'     => $phone,
                    'role'      => $role,
                    'is_active' => true,
                    'password'  => Hash::make($password),
                ]
            );
        }
    }
}