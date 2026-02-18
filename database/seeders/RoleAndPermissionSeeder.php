<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions from config
        $permissions = config('permissions.permissions');
        $allPermissions = [];

        foreach ($permissions as $permissionName => $description) {
            $allPermissions[] = $permissionName;
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Create roles from config
        $roles = config('permissions.roles');

        foreach ($roles as $roleKey => $roleData) {
            $role = Role::firstOrCreate(['name' => $roleKey]);

            // Assign permissions based on role_permissions config
            $rolePermissions = config("permissions.role_permissions.{$roleKey}", []);

            if ($rolePermissions === '*') {
                // Admin gets all permissions
                $role->syncPermissions($allPermissions);
            } elseif (is_array($rolePermissions)) {
                // Other roles get their specific permissions
                $role->syncPermissions($rolePermissions);
            }
        }

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Total roles: '.Role::count());
        $this->command->info('Total permissions: '.Permission::count());
    }
}
