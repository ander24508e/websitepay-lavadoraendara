<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAudit;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UsuariosController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $roleId = $request->integer('role_id') ?: null;

        $usuarios = $this->staffQuery()
            ->with('roles')
            ->withCount('assignedOrders')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('telefono', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn (Builder $query) => $query->where('active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('active', false))
            ->when($roleId, fn (Builder $query) => $query->whereHas('roles', fn (Builder $roles) => $roles->whereKey($roleId)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statsQuery = $this->staffQuery();
        $stats = [
            'total_usuarios' => (clone $statsQuery)->count(),
            'activos' => (clone $statsQuery)->where('active', true)->count(),
            'roles' => Role::query()->manageable()->count(),
        ];
        $roles = Role::query()->manageable()->orderBy('name')->get(['id', 'name']);

        return view('admin.usuarios.index', compact('usuarios', 'stats', 'search', 'status', 'roles', 'roleId'));
    }

    public function show(User $usuario)
    {
        $this->ensureStaff($usuario);

        $usuario->load([
            'roles.permissions',
            'createdBy:id,name',
            'assignedOrders' => fn ($query) => $query->latest()->take(10),
        ]);

        $role = $usuario->staffRole();
        $rolePermissions = $role
            ? PermissionCatalog::groupsFor($role->permissions->pluck('name')->all())
            : collect();

        $resumen = [
            'ordenes_asignadas' => $usuario->assignedOrders()->count(),
            'ventas_atendidas' => $usuario->attendedSales()->count(),
            'total_vendido' => (float) $usuario->attendedSales()->where('status', 'paid')->sum('total'),
            'registro' => $usuario->created_at,
        ];

        return view('admin.usuarios.show', compact('usuario', 'role', 'rolePermissions', 'resumen'));
    }

    public function create(Request $request)
    {
        return view('admin.usuarios.create', [
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        $data = $request->validate($this->rules($actor));

        $usuario = DB::transaction(function () use ($actor, $data) {
            $role = Role::query()->findOrFail($data['role_id']);

            $usuario = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'telefono' => $this->clean($data['telefono'] ?? null),
                'direccion' => $this->clean($data['direccion'] ?? null),
                'password' => $data['password'],
                'active' => (bool) ($data['active'] ?? true),
                'created_by' => $actor->id,
            ]);

            $usuario->syncRoles([$role]);

            $this->audit($usuario, $actor, 'staff.created', ['role' => $role->name]);

            return $usuario;
        });

        return redirect()->route('admin.usuarios.show', $usuario)
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(Request $request, User $usuario)
    {
        $actor = $request->user();
        $this->ensureManageable($actor, $usuario);

        return view('admin.usuarios.edit', [
            'usuario' => $usuario->load('roles'),
            'roles' => $this->assignableRoles($actor, $usuario->staffRole()),
        ]);
    }

    public function update(Request $request, User $usuario)
    {
        $actor = $request->user();
        $this->ensureManageable($actor, $usuario);

        $currentRole = $usuario->staffRole();
        $data = $request->validate($this->rules($actor, $usuario, $currentRole));

        DB::transaction(function () use ($actor, $usuario, $data, $currentRole) {
            $role = Role::query()->findOrFail($data['role_id']);
            $before = [
                'role' => $currentRole?->name,
                'active' => $usuario->active,
            ];

            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'telefono' => $this->clean($data['telefono'] ?? null),
                'direccion' => $this->clean($data['direccion'] ?? null),
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }
            if ($actor->can('users.deactivate')) {
                $payload['active'] = (bool) ($data['active'] ?? false);
            }

            $usuario->update($payload);
            $usuario->syncRoles([$role]);

            $this->audit($usuario, $actor, 'staff.updated', [
                'before' => $before,
                'after' => ['role' => $role->name, 'active' => $usuario->active],
            ]);
        });

        return redirect()->route('admin.usuarios.show', $usuario)
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $usuario)
    {
        $actor = $request->user();
        $this->ensureManageable($actor, $usuario);

        $usuario->update(['active' => false]);
        $this->audit($usuario, $actor, 'staff.deactivated');

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'El acceso del usuario fue desactivado; su historial se conserva.');
    }

    private function rules(User $actor, ?User $usuario = null, ?Role $currentRole = null): array
    {
        $uniqueEmail = Rule::unique('users', 'email');
        if ($usuario) {
            $uniqueEmail->ignore($usuario->id);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $uniqueEmail],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', Rule::in($this->assignableRoles($actor, $currentRole)->pluck('id')->all())],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Personal interno: usuarios (no propietarios) con un rol que no es cliente.
     */
    private function staffQuery(): Builder
    {
        return User::query()
            ->where('is_owner', false)
            ->whereHas('roles', fn (Builder $query) => $query->where('is_system', false));
    }

    private function ensureStaff(User $target): void
    {
        abort_unless($this->staffQuery()->whereKey($target->id)->exists(), 404);
    }

    private function ensureManageable(User $actor, User $target): void
    {
        $this->ensureStaff($target);
        abort_if($target->is($actor), 403, 'No puedes modificar tu propia cuenta desde este modulo.');

        if ($actor->isOwner()) {
            return;
        }

        $targetRole = $target->staffRole();
        abort_if($targetRole && ! $this->canGrantRole($actor, $targetRole), 403, 'Este usuario tiene un rol con permisos que tu no posees.');
    }

    /**
     * Roles activos que el actor puede asignar. Un usuario que no es propietario
     * solo asigna roles cuyos permisos ya posee (evita escalar privilegios).
     * Se conserva el rol actual del usuario editado aunque este inactivo.
     */
    private function assignableRoles(User $actor, ?Role $currentRole = null): Collection
    {
        $roles = Role::query()
            ->manageable()
            ->where(fn (Builder $query) => $query
                ->where('active', true)
                ->when($currentRole, fn (Builder $sub) => $sub->orWhere('id', $currentRole->id)))
            ->with('permissions')
            ->orderBy('name')
            ->get();

        if ($actor->isOwner()) {
            return $roles;
        }

        return $roles->filter(fn (Role $role) => $this->canGrantRole($actor, $role))->values();
    }

    private function canGrantRole(User $actor, Role $role): bool
    {
        if ($actor->isOwner()) {
            return true;
        }

        $actorPermissions = $actor->getAllPermissions()->pluck('name')->all();

        return array_diff($role->permissions->pluck('name')->all(), $actorPermissions) === [];
    }

    private function audit(User $target, User $actor, string $action, array $payload = []): void
    {
        UserAudit::create([
            'target_user_id' => $target->id,
            'actor_user_id' => $actor->id,
            'action' => $action,
            'payload' => $payload ?: null,
        ]);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
