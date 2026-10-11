<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        $baseQuery = Role::query()->manageable();

        $roles = (clone $baseQuery)
            ->withCount(['users', 'permissions'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn (Builder $query) => $query->where('active', true))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('active', true)->count(),
            'users' => User::query()
                ->where('is_owner', false)
                ->whereHas('roles', fn (Builder $query) => $query->where('is_system', false))
                ->count(),
        ];

        return view('admin.roles.index', compact('roles', 'stats', 'search', 'status'));
    }

    public function create(Request $request)
    {
        return view('admin.roles.create', [
            'role' => new Role(['active' => true]),
            'permissionGroups' => $this->grantableGroups($request->user()),
            'selectedPermissions' => [],
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        $data = $request->validate($this->rules($actor));

        $role = DB::transaction(function () use ($data, $request) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
                'description' => $data['description'] ?? null,
                'active' => $request->boolean('active'),
                'is_system' => false,
            ]);
            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });

        NotificationHelper::success("Rol {$role->name} creado correctamente.");

        return redirect()->route('admin.roles.index');
    }

    public function edit(Request $request, Role $role)
    {
        $this->ensureEditable($request->user(), $role);

        return view('admin.roles.edit', [
            'role' => $role->loadCount('users'),
            'permissionGroups' => $this->grantableGroups($request->user()),
            'selectedPermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $actor = $request->user();
        $this->ensureEditable($actor, $role);
        $data = $request->validate($this->rules($actor, $role));

        DB::transaction(function () use ($role, $data, $request) {
            $role->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'active' => $request->boolean('active'),
            ]);
            $role->syncPermissions($data['permissions'] ?? []);
        });

        NotificationHelper::success("Rol {$role->name} actualizado correctamente.");

        return redirect()->route('admin.roles.index');
    }

    public function destroy(Request $request, Role $role)
    {
        $this->ensureEditable($request->user(), $role);

        if ($role->users()->exists()) {
            NotificationHelper::error('No se puede eliminar un rol con usuarios asignados. Reasigna a esos usuarios primero.');

            return redirect()->route('admin.roles.index');
        }

        $name = $role->name;
        $role->delete();

        NotificationHelper::success("Rol {$name} eliminado correctamente.");

        return redirect()->route('admin.roles.index');
    }

    private function rules(User $actor, ?Role $role = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::notIn([Role::CLIENT]),
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'active' => ['nullable', 'boolean'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->grantablePermissions($actor))],
        ];
    }

    /**
     * El propietario otorga cualquier permiso; el resto solo los que tiene.
     *
     * @return array<int, string>
     */
    private function grantablePermissions(User $actor): array
    {
        $catalog = PermissionCatalog::names();

        if ($actor->isOwner()) {
            return $catalog;
        }

        return array_values(array_intersect($catalog, $actor->getAllPermissions()->pluck('name')->all()));
    }

    private function grantableGroups(User $actor)
    {
        return PermissionCatalog::groupsFor($actor->isOwner() ? null : $this->grantablePermissions($actor));
    }

    private function ensureEditable(User $actor, Role $role): void
    {
        abort_if($role->is_system, 404);

        if ($actor->isOwner()) {
            return;
        }

        // Evita que un usuario se quite permisos a si mismo o edite roles con mas alcance que el suyo.
        abort_if($actor->hasRole($role), 403, 'No puedes modificar tu propio rol.');

        $outside = array_diff(
            $role->permissions->pluck('name')->all(),
            $this->grantablePermissions($actor)
        );

        abort_if($outside !== [], 403, 'Este rol tiene permisos que tu no posees.');
    }
}
