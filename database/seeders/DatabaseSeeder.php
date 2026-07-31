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
        // 1. Roles
        $superAdminRole = Role::create(['name' => 'super_admin', 'label' => 'Super Administrator']);
        $adminRole      = Role::create(['name' => 'admin', 'label' => 'Restaurant Owner']);
        $userRole       = Role::create(['name' => 'user', 'label' => 'Customer User']);

        // 2. Default Super Admin (EXACT credentials requested by user)
        User::create([
            'role_id' => $superAdminRole->id,
            'name' => 'System Super Admin',
            'email' => 'superadmin#123@gmail.com',
            'password' => Hash::make('superadmin123#'),
            'phone' => null,
            'status' => 'active',
            'avatar' => null,
        ]);
    }
}
