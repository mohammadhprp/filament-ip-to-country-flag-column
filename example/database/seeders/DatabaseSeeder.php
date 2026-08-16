<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Visit;
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

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Visit::create(['ip_address' => '8.8.8.8']);
        Visit::create(['ip_address' => '1.1.1.1']);
        Visit::create(['ip_address' => '127.0.0.1']);
    }
}
