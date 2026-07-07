<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperadminSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'superadmin@fitnessapp.test')],
            [
                'name' => env('SUPERADMIN_NAME', 'Super Admin'),
                'password' => env('SUPERADMIN_PASSWORD', 'password'),
            ]
        );

        $user->assignRole('superadmin');
    }
}
