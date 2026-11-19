<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
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
        User::updateOrCreate(
            ['email' => 'admin@xerinhub.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('XerinERP@2026'),
                'email_verified_at' => now(),
                'status' => AccountStatus::Active,
            ]
        );

        $this->call(RolePermissionSeeder::class);
    }
}
