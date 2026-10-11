<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_seeder_only_creates_the_client_system_role(): void
    {
        $this->assertSame(['cliente'], Role::query()->pluck('name')->all());
        $this->assertTrue(Role::findByName('cliente')->is_system);
    }

    public function test_clients_cannot_access_staff_management(): void
    {
        $client = User::factory()->create();
        $client->assignRole(Role::CLIENT);

        $this->actingAs($client)
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();
    }

    public function test_system_owner_has_full_access_without_a_role_and_is_not_managed_as_staff(): void
    {
        $owner = User::query()->where('is_owner', true)->firstOrFail();

        $this->assertTrue($owner->isOwner());
        $this->assertCount(0, $owner->roles);
        $this->assertTrue($owner->can('users.view'));

        $this->actingAs($owner)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertDontSee($owner->email);

        $this->actingAs($owner)
            ->get(route('admin.usuarios.show', $owner))
            ->assertNotFound();
    }

    public function test_owner_identity_cannot_be_assigned_through_mass_assignment(): void
    {
        $user = User::create([
            'name' => 'Cuenta ordinaria',
            'email' => 'ordinary@example.com',
            'password' => 'password',
            'is_owner' => true,
        ]);

        $this->assertFalse($user->isOwner());
    }

    public function test_users_index_shows_a_button_to_roles(): void
    {
        $owner = User::query()->where('is_owner', true)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('href="'.route('admin.roles.index').'"', false);
    }

    public function test_user_gets_exactly_one_role_and_a_role_can_be_shared(): void
    {
        $owner = User::query()->where('is_owner', true)->firstOrFail();
        $bodega = $this->role('Bodega', ['inventory.view']);

        foreach (['uno@example.com', 'dos@example.com'] as $email) {
            $this->actingAs($owner)->post(route('admin.usuarios.store'), [
                'name' => 'Bodeguero',
                'email' => $email,
                'password' => 'Secret123',
                'password_confirmation' => 'Secret123',
                'role_id' => $bodega->id,
                'active' => 1,
            ])->assertRedirect();
        }

        $this->assertSame(2, $bodega->users()->count());

        $user = User::where('email', 'uno@example.com')->firstOrFail();
        $ventas = $this->role('Ventas', ['sales.view']);

        $this->actingAs($owner)->put(route('admin.usuarios.update', $user), [
            'name' => 'Bodeguero',
            'email' => 'uno@example.com',
            'role_id' => $ventas->id,
            'active' => 1,
        ])->assertRedirect(route('admin.usuarios.show', $user));

        $this->assertTrue($user->fresh()->hasExactRoles([$ventas->name]));
    }

    public function test_user_screens_show_the_role_and_its_permissions(): void
    {
        $owner = User::query()->where('is_owner', true)->firstOrFail();
        $user = $this->staffWith('Bodega', ['inventory.view', 'inventory.purchases.create']);

        $this->actingAs($owner)->get(route('admin.usuarios.create'))
            ->assertOk()
            ->assertSee('Bodega')
            ->assertDontSee('Gerente responsable');
        $this->actingAs($owner)->get(route('admin.usuarios.show', $user))
            ->assertOk()
            ->assertSee('Permisos del rol')
            ->assertSee('Registrar compras');
        $this->actingAs($owner)->get(route('admin.usuarios.edit', $user))
            ->assertOk()
            ->assertSee('value="'.$user->staffRole()->id.'" selected', false);
    }

    public function test_staff_cannot_assign_a_role_with_more_permissions_than_their_own(): void
    {
        $manager = $this->staffWith('Encargado', ['users.view', 'users.create', 'inventory.view']);
        $bodega = $this->role('Bodega', ['inventory.view']);
        $admin = $this->role('Administrativo', ['inventory.view', 'company.update']);

        $this->actingAs($manager)->post(route('admin.usuarios.store'), [
            'name' => 'Permitido',
            'email' => 'permitido@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'role_id' => $bodega->id,
        ])->assertRedirect();

        $this->actingAs($manager)->post(route('admin.usuarios.store'), [
            'name' => 'Escalada',
            'email' => 'escalada@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'role_id' => $admin->id,
        ])->assertSessionHasErrors('role_id');

        $this->assertDatabaseHas('users', ['email' => 'permitido@example.com', 'created_by' => $manager->id]);
        $this->assertDatabaseMissing('users', ['email' => 'escalada@example.com']);
    }

    public function test_inactive_roles_cannot_be_assigned(): void
    {
        $owner = User::query()->where('is_owner', true)->firstOrFail();
        $inactive = $this->role('Temporal', ['inventory.view'], active: false);

        $this->actingAs($owner)->post(route('admin.usuarios.store'), [
            'name' => 'Nuevo',
            'email' => 'nuevo@example.com',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
            'role_id' => $inactive->id,
        ])->assertSessionHasErrors('role_id');
    }

    public function test_inactive_staff_cannot_login(): void
    {
        $employee = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password',
            'active' => false,
        ]);
        $employee->assignRole($this->role('Lavado', ['orders.view']));

        $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    private function role(string $name, array $permissions, bool $active = true): Role
    {
        $role = Role::create(['name' => $name, 'guard_name' => 'web', 'active' => $active]);
        $role->syncPermissions($permissions);

        return $role;
    }

    private function staffWith(string $roleName, array $permissions): User
    {
        $user = User::factory()->create();
        $user->assignRole($this->role($roleName, $permissions));

        return $user;
    }
}
