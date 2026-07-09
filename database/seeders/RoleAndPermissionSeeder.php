<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::create(['name' => 'admin.access']);
        Permission::create(['name' => 'cookies.upload']);
        Permission::create(['name' => 'download.youtube']);

        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(['admin.access', 'cookies.upload']);

        $staff = Role::create(['name' => 'staff']);
        $staff->givePermissionTo('download.youtube');
    }
}
