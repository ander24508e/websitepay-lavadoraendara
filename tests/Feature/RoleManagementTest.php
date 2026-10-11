<?php

namespace Tests\Feature;

use App\Models\CatalogType;
use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->owner = User::query()->where('is_owner', true)->firstOrFail();
    }

    public function test_seeder_syncs_the_permission_catalog(): void
    {
        $this->assertEqualsCanonicalizing(
            PermissionCatalog::names(),
            Permission::query()->pluck('name')->all()
        );
    }

    public function test_owner_can_create_a_role_with_permissions(): void
    {
        $this->actingAs($this->owner)->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('Nuevo rol')
            ->assertSee('value="inventory.purchases.create"', false);

        $this->actingAs($this->owner)->post(route('admin.roles.store'), [
            'name' => 'Bodega',
            'description' => 'Personal de bodega',
            'active' => 1,
            'permissions' => ['inventory.view', 'inventory.purchases.view', 'inventory.purchases.create'],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('Bodega');
        $this->assertTrue($role->active);
        $this->assertFalse($role->is_system);
        $this->assertEqualsCanonicalizing(
            ['inventory.view', 'inventory.purchases.view', 'inventory.purchases.create'],
            $role->permissions->pluck('name')->all()
        );
    }

    public function test_role_names_are_unique_and_client_is_reserved(): void
    {
        Role::create(['name' => 'Bodega', 'guard_name' => 'web']);

        $this->actingAs($this->owner)->post(route('admin.roles.store'), ['name' => 'Bodega'])
            ->assertSessionHasErrors('name');
        $this->actingAs($this->owner)->post(route('admin.roles.store'), ['name' => 'cliente'])
            ->assertSessionHasErrors('name');
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $this->actingAs($this->owner)->post(route('admin.roles.store'), [
            'name' => 'Raro',
            'permissions' => ['no.existe'],
        ])->assertSessionHasErrors('permissions.0');
    }

    public function test_client_system_role_is_hidden_and_protected(): void
    {
        $client = Role::findByName('cliente');

        $this->actingAs($this->owner)->get(route('admin.roles.index'))
            ->assertOk()
            ->assertDontSee(route('admin.roles.edit', $client), false);
        $this->actingAs($this->owner)->get(route('admin.roles.edit', $client))->assertNotFound();
        $this->actingAs($this->owner)->delete(route('admin.roles.destroy', $client))->assertNotFound();
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'Ventas', 'guard_name' => 'web']);
        User::factory()->create()->assignRole($role);

        $this->actingAs($this->owner)->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));
        $this->assertDatabaseHas('roles', ['id' => $role->id]);

        $empty = Role::create(['name' => 'Vacio', 'guard_name' => 'web']);
        $this->actingAs($this->owner)->delete(route('admin.roles.destroy', $empty));
        $this->assertDatabaseMissing('roles', ['id' => $empty->id]);
    }

    public function test_warehouse_role_only_reaches_inventory(): void
    {
        Empresa::create(['nombre' => 'Lavadora Endara']);
        $role = Role::create(['name' => 'Bodega', 'guard_name' => 'web']);
        $role->syncPermissions(['inventory.view', 'inventory.purchases.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('admin.inventario.index'));
        $this->actingAs($user)->get(route('admin.inventario.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.inventario.purchases'))->assertOk();
        $this->actingAs($user)->post(route('admin.inventario.purchases.store'), [])->assertForbidden();
        $this->actingAs($user)->get(route('admin.ventas.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.usuarios.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_inactive_role_loses_panel_access(): void
    {
        $role = Role::create(['name' => 'Bodega', 'guard_name' => 'web', 'active' => false]);
        $role->syncPermissions(['inventory.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get(route('admin.inventario.index'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('home'));
    }

    public function test_staff_can_only_grant_permissions_they_have(): void
    {
        $role = Role::create(['name' => 'Supervisor', 'guard_name' => 'web']);
        $role->syncPermissions(['roles.view', 'roles.create', 'roles.update', 'inventory.view']);
        $supervisor = User::factory()->create();
        $supervisor->assignRole($role);

        $this->actingAs($supervisor)->get(route('admin.roles.create'))
            ->assertOk()
            ->assertSee('value="inventory.view"', false)
            ->assertDontSee('value="company.update"', false);

        $this->actingAs($supervisor)->post(route('admin.roles.store'), [
            'name' => 'Escalado',
            'permissions' => ['inventory.view', 'company.update'],
        ])->assertSessionHasErrors('permissions.1');

        $this->actingAs($supervisor)->get(route('admin.roles.edit', $role))->assertForbidden();
    }

    public function test_catalog_permissions_are_split_by_business_model(): void
    {
        $empresa = Empresa::create(['nombre' => 'Lavadora Endara']);
        $products = CatalogType::create(['empresa_id' => $empresa->id, 'name' => 'Tienda', 'business_model' => CatalogType::BUSINESS_MODEL_PRODUCTS]);
        $services = CatalogType::create(['empresa_id' => $empresa->id, 'name' => 'Lavado', 'business_model' => CatalogType::BUSINESS_MODEL_SERVICES]);

        $role = Role::create(['name' => 'Bodega', 'guard_name' => 'web']);
        $role->syncPermissions(['catalog.products.view', 'catalog.products.update']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get(route('admin.catalog-types.show', $products))->assertOk();
        $this->actingAs($user)->get(route('admin.catalog-types.edit', $products))->assertOk();
        $this->actingAs($user)->get(route('admin.catalog-types.show', $services))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.catalog-types.destroy', $products))->assertForbidden();
    }
}
