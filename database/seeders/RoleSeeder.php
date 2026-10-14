<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Database\Seeder;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Sincroniza la tabla permissions con config/permissions_catalog.php.
        $permissions = PermissionCatalog::names();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $permissions)
            ->delete();

        // Unico rol del sistema. Los roles del personal se crean desde el modulo Roles.
        Role::client()->forceFill(['is_system' => true, 'active' => true])->save();

        // El propietario tiene acceso total por Gate::before; no necesita rol.
        $ownerName = trim((string) config('auth.owner_name', 'Administrador')) ?: 'Administrador';
        $ownerEmail = trim((string) config('auth.owner_email', 'admin@endara.com')) ?: 'admin@endara.com';
        $ownerPassword = (string) config('auth.owner_password', '');

        if (app()->environment('production') && (trim($ownerPassword) === '' || $ownerPassword === 'Admin123')) {
            throw new RuntimeException('Configura OWNER_PASSWORD o ADMIN_PASSWORD antes de ejecutar seeders en produccion.');
        }

        if (trim($ownerPassword) === '') {
            $ownerPassword = 'Admin123';
        }

        $owner = User::firstOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => $ownerName,
                'password' => bcrypt($ownerPassword),
                'email_verified_at' => now(),
                'active' => true,
            ]
        );

        User::query()
            ->where('is_owner', true)
            ->whereKeyNot($owner->id)
            ->update(['is_owner' => false]);

        $owner->forceFill([
            'active' => true,
            'is_owner' => true,
            'created_by' => null,
        ])->save();
        $owner->syncRoles([]);

        $client = User::firstOrCreate(
            ['email' => 'cliente@test.com'],
            [
                'name' => 'Daniel',
                'password' => bcrypt('Cliente123'),
                'email_verified_at' => now(),
                'active' => true,
            ]
        );
        $client->syncRoles([Role::CLIENT]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Catalogo de permisos sincronizado ('.count($permissions).' permisos).');
    }
}
