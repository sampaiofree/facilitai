<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\ExampleUserDataSeeder;
use Database\Seeders\AdminUserSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Usuário de teste padrão
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Usuário admin fixo
        $this->call(AdminUserSeeder::class);

        $this->call(ExampleUserDataSeeder::class);
    }
}
