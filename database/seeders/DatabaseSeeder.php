<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Seed roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'team', 'guard_name' => 'web']);
        Role::create(['name' => 'coach', 'guard_name' => 'web']);
    }
}
