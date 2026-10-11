@extends('layouts.admin')

@section('title', 'Usuarios')

@push('styles')
    @vite('resources/scss/admin/roles.scss')
@endpush

@section('content')
<div class="roles-page space-y-6">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div>
            <div class="flex items-center gap-2">
            <x-heroicon-o-user-group class="w-8 h-8 text-gray-800" />
            <h2 class="text-2xl font-bold text-gray-800">Personal interno</h2>
            </div>
            <p class="text-gray-500 text-sm mt-1">Gestiona el personal interno y el rol de cada uno. Los permisos se definen en el rol. La cuenta propietaria y los clientes se administran por separado.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @can('roles.view')
                <a href="{{ route('admin.roles.index') }}" class="roles-btn roles-btn--ghost" title="Roles" aria-label="Ir a roles">
                    <x-heroicon-o-shield-check class="w-5 h-5" />
                    <span>Roles</span>
                </a>
            @endcan
            @can('users.create')
                <a href="{{ route('admin.usuarios.create') }}"
                    class="inline-flex items-center justify-center bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-700 transition"
                    title="Nuevo usuario" aria-label="Nuevo usuario">
                    <x-heroicon-o-plus class="w-5 h-5" />
                </a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm"><p class="text-xs text-gray-400 uppercase font-semibold">Personal</p><p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total_usuarios'] }}</p></div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm"><p class="text-xs text-gray-400 uppercase font-semibold">Activos</p><p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['activos'] }}</p></div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm"><p class="text-xs text-gray-400 uppercase font-semibold">Roles creados</p><p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['roles'] }}</p></div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
            <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por nombre, correo o telefono" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"><option value="">Todos los estados</option><option value="active" @selected($status === 'active')>Activos</option><option value="inactive" @selected($status === 'inactive')>Inactivos</option></select>
            <select name="role_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"><option value="">Todos los roles</option>@foreach($roles as $roleOption)<option value="{{ $roleOption->id }}" @selected((int) $roleId === $roleOption->id)>{{ $roleOption->name }}</option>@endforeach</select>
            <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium">Buscar</button>
            <a href="{{ route('admin.usuarios.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Limpiar</a>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="md:hidden divide-y divide-gray-100">
            @forelse($usuarios as $usuario)
                @php
                    $userRole = $usuario->staffRole();
                @endphp
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h4 class="font-semibold text-gray-800 break-words">{{ $usuario->name }}</h4>
                            <p class="text-xs text-gray-400 mt-0.5">ID #{{ $usuario->id }}</p>
                        </div>
                        <span class="roles-pill roles-pill--primary shrink-0">{{ $userRole?->name ?? 'Sin rol' }}</span>
                    </div>

                    <div class="grid grid-cols-1 gap-2 mt-3 text-sm">
                        <div>
                            <p class="text-xs uppercase text-gray-400 font-semibold">Correo</p>
                            <p class="text-gray-700 break-words">{{ $usuario->email }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Telefono</p>
                                <p class="text-gray-700 break-words">{{ $usuario->telefono ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Ordenes</p>
                                <p class="text-gray-700 font-semibold">{{ $usuario->assigned_orders_count }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-400 font-semibold">Registro</p>
                            <p class="text-gray-700">{{ $usuario->created_at?->format('d/m/Y') ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 mt-4">
                        <a href="{{ route('admin.usuarios.show', $usuario) }}"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-blue-600 hover:text-blue-800 hover:bg-blue-50 transition"
                            title="Ver usuario" aria-label="Ver usuario">
                            <x-heroicon-o-eye class="w-5 h-5" />
                        </a>
                        @if(! $usuario->is(auth()->user())) @can('users.update')<a href="{{ route('admin.usuarios.edit', $usuario) }}"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50 transition"
                            title="Editar usuario" aria-label="Editar usuario">
                            <x-heroicon-o-pencil-square class="w-5 h-5" />
                        </a>
                        @endcan @endif
                        @if($usuario->active && ! $usuario->is(auth()->user())) @can('users.deactivate')<form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}" onsubmit="return confirm('¿Desactivar el acceso de este trabajador?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-red-600 hover:text-red-800 hover:bg-red-50 transition"
                                title="Desactivar usuario" aria-label="Desactivar usuario">
                                <x-heroicon-o-trash class="w-5 h-5" />
                            </button>
                        </form>
                        @endcan @endif
                    </div>
                </article>
            @empty
                <div class="px-4 py-8 text-center text-gray-400">No hay usuarios registrados.</div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-hidden">
            <table class="w-full table-fixed text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="w-[20%] px-4 py-3 text-center">Usuario</th>
                        <th class="w-[25%] px-4 py-3 text-center">Correo</th>
                        <th class="w-[13%] px-4 py-3 text-center">Telefono</th>
                        <th class="w-[12%] px-4 py-3 text-center">Rol</th>
                        <th class="w-[8%] px-4 py-3 text-center">Ord.</th>
                        <th class="w-[10%] px-4 py-3 text-center">Registro</th>
                        <th class="w-[12%] px-4 py-3 text-center">Acc.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($usuarios as $usuario)
                        @php
                            $userRole = $usuario->staffRole();
                        @endphp
                        <tr class="border-t border-gray-100">
                            <td class="px-4 py-3 text-center">
                                <div class="font-semibold text-gray-800 truncate">{{ $usuario->name }}</div>
                                <div class="text-xs text-gray-400">ID #{{ $usuario->id }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-center truncate">{{ $usuario->email }}</td>
                            <td class="px-4 py-3 text-gray-600 text-center truncate">{{ $usuario->telefono ?? '-' }}</td>
                            <td class="px-4 py-3 text-center"><span class="roles-pill roles-pill--primary max-w-full truncate">{{ $userRole?->name ?? 'Sin rol' }}</span>@unless($usuario->active)<span class="block text-[11px] text-red-500 mt-1">Inactivo</span>@endunless</td>
                            <td class="px-4 py-3 text-gray-700 text-center">{{ $usuario->assigned_orders_count }}</td>
                            <td class="px-4 py-3 text-gray-500 text-center">{{ $usuario->created_at?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('admin.usuarios.show', $usuario) }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:text-blue-800 hover:bg-blue-50 transition"
                                    title="Ver usuario" aria-label="Ver usuario">
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                </a>
                                @if(! $usuario->is(auth()->user())) @can('users.update')<a href="{{ route('admin.usuarios.edit', $usuario) }}"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50 transition"
                                    title="Editar usuario" aria-label="Editar usuario">
                                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                                </a>
                                @endcan @endif
                                @if($usuario->active && ! $usuario->is(auth()->user())) @can('users.deactivate')<form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}" onsubmit="return confirm('¿Desactivar el acceso de este trabajador?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:text-red-800 hover:bg-red-50 transition"
                                        title="Desactivar usuario" aria-label="Desactivar usuario">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </form>
                                @endcan @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No hay usuarios registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $usuarios->links() }}</div>
</div>
@endsection
