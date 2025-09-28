<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create a default Filament admin user if not exists
        User::firstOrCreate(
            ['email' => 'admin@pplus.my.id'],
            [
                'name' => 'Admin',
                'password' => Hash::make('ydhwjy'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
