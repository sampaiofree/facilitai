<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'sampaio.free@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
