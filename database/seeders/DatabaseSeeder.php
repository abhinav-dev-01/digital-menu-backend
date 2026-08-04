<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles — use firstOrCreate so re-seeding is idempotent (no unique constraint crash)
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['label' => 'Super Administrator']);
        $adminRole      = Role::firstOrCreate(['name' => 'admin'],       ['label' => 'Restaurant Owner']);
        $userRole       = Role::firstOrCreate(['name' => 'user'],        ['label' => 'Customer User']);

        // 2. Default Super Admin — updateOrCreate prevents duplicate on re-seed
        User::updateOrCreate(
            ['email' => 'superadmin#123@gmail.com'],
            [
                'role_id'  => $superAdminRole->id,
                'name'     => 'System Super Admin',
                'password' => Hash::make('superadmin123#'),
                'phone'    => null,
                'status'   => 'active',
                'avatar'   => null,
            ]
        );
    }
}
