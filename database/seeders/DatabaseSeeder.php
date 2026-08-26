<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            CountrySeeder::class,
        ]);

        $this->call([
            AccountSeeder::class,
        ]);

        $this->call([
            SubsidiaryAccountSeeder::class,
        ]);
 
 
 
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'shamim.3119@gmail.com',
            'password' => Hash::make('Shamim@3119'), // Add password here
        ]);
       
    }
}
