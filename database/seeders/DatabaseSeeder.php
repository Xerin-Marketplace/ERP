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
        $hq = \Modules\Organization\Models\Branch::firstOrCreate(
            ['code' => 'HQ'],
            ['name' => 'Head Office', 'city' => 'Dar es Salaam', 'status' => 'active']
        );

        User::updateOrCreate(
            ['email' => 'admin@xerinhub.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('XerinERP@2026'),
                'email_verified_at' => now(),
                'status' => AccountStatus::Active,
                'branch_id' => $hq->id,
            ]
        );

        $this->call([
            RolePermissionSeeder::class,
            \Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder::class,
            \Modules\Identity\Database\Seeders\IdentityDatabaseSeeder::class,
            \Modules\Employee\Database\Seeders\EmployeeDatabaseSeeder::class,
            \Modules\Ticketing\Database\Seeders\TicketingDatabaseSeeder::class,
            \Modules\HR\Database\Seeders\HRDatabaseSeeder::class,
            \Modules\Finance\Database\Seeders\FinanceDatabaseSeeder::class,
            \Modules\SystemAdministration\Database\Seeders\SystemAdministrationDatabaseSeeder::class,
        ]);
    }
}
