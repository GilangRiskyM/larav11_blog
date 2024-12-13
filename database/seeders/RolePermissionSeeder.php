<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'admin']);

        Permission::create(['name' => 'admin-blogs']);
        Permission::create(['name' => 'admin-pages']);
        Permission::create(['name' => 'admin-users']);

        $roleAdmin = Role::findByName('admin');

        $roleAdmin->givePermissionTo('admin-blogs');
        $roleAdmin->givePermissionTo('admin-pages');
        $roleAdmin->givePermissionTo('admin-users');
    }
}
