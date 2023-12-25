<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $configPermissions = config('access_control.permissions');
        foreach ($configPermissions as $permissionName => $roles) {
            $permission = Permission::findOrCreate($permissionName);
            foreach ($roles as $roleName) {
                $role = Role::findOrCreate($roleName);
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
            $this->removePermissionFromUnlistedRoles($permission, $roles);
        }
        $this->removeUnlistedPermissions($configPermissions);
    }

    private function removePermissionFromUnlistedRoles(Permission $permission, array $listedRoles): void
    {
        $allRoles = Role::all();
        foreach ($allRoles as $role) {
            if (!in_array($role->name, $listedRoles)) {
                $role->revokePermissionTo($permission);
            }
        }
    }

    private function removeUnlistedPermissions(array $configPermissions): void
    {
        $allPermissions = Permission::all();
        foreach ($allPermissions as $permission) {
            if (!array_key_exists($permission->name, $configPermissions)) {
                $permission->delete();
            }
        }
    }
}
