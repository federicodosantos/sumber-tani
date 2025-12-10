<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $role = ['OWNER', 'EMPLOYEE'];

        User::factory()->create([
            'username' => 'owner',
            'password' => bcrypt('password123'),
            'role' => $role[0],
        ]);

        User::factory()->create([
            'username' => 'employee',
            'password' => bcrypt('password123'),
            'role' => $role[1],
        ]);
    }
}
