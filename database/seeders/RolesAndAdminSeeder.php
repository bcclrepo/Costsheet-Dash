<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['super_admin', 'admin', 'area_admin', 'viewer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@costsheet.local'],
            [
                'name'       => 'Super Administrator',
                'pis_number' => 'SA001',
                'password'   => Hash::make('SuperAdmin@1234'),
                'email_verified_at' => now(),
                'is_active'  => true,
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@costsheet.local'],
            [
                'name'       => 'Demo Viewer',
                'pis_number' => 'VW001',
                'password'   => Hash::make('Viewer@1234'),
                'email_verified_at' => now(),
                'is_active'  => true,
            ]
        );
        $viewer->syncRoles(['viewer']);
    }
}
