@extends('layouts.admin')

@section('title', 'Roles')

@push('styles')
    @vite('resources/scss/admin/roles.scss')
@endpush

@section('content')
<div class="roles-page space-y-6">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-start gap-3 min-w-0">
            @can('users.view')
                <a href="{{ route('admin.usuarios.index') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-gray-800"
                    title="Volver a usuarios" aria-label="Volver a usuarios">
                    <x-heroicon-o-arrow-left class="h-4 w-4" />
                </a>
            @endcan
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-shield-check class="w-8 h-8 text-gray-800" />
                    <h2 class="text-2xl font-bold text-gray-800">Roles</h2>
                </div>
                <p class="text-gray-500 text-sm mt-1">Cada rol agrupa los permisos de un puesto. Asigna un rol a uno o varios usuarios.</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @can('roles.create')
                <a href="{{ route('admin.roles.create') }}" class="roles-btn roles-btn--primary" title="Nuevo rol" aria-label="Nuevo rol">
                    <x-heroicon-o-plus class="w-5 h-5" />
                    <span>Nuevo rol</span>
                </a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="roles-card p-4"><p class="text-xs text-gray-400 uppercase font-semibold">Roles</p><p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total'] }}</p></div>
        <div class="roles-card p-4"><p class="text-xs text-gray-400 uppercase font-semibold">Activos</p><p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['active'] }}</p></div>
        <div class="roles-card p-4"><p class="text-xs text-gray-400 uppercase font-semibold">Usuarios con rol</p><p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['users'] }}</p></div>
    </div>

    <div class="roles-card p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-3">
            <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por nombre o descripción del rol" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Todos los estados</option>
                <option value="active" @selected($status === 'active')>Activos</option>
                <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
            </select>
            <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition">Buscar</button>
            <a href="{{ route('admin.roles.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium text-center">Limpiar</a>
        </form>
    </div>

    <div class="roles-card overflow-hidden">
        {{-- Movil --}}
        <div class="md:hidden divide-y divide-gray-100">
            @forelse($roles as $role)
                <article class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h4 class="font-semibold text-gray-800 break-words">{{ $role->name }}</h4>
                            <p class="text-xs text-gray-500 mt-0.5 break-words">{{ $role->description ?: 'Sin descripción' }}</p>
                        </div>
                        <span class="roles-status {{ $role->active ? 'roles-status--active' : 'roles-status--inactive' }}">{{ $role->active ? 'Activo' : 'Inactivo' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="roles-pill roles-pill--primary">{{ $role->permissions_count }} permisos</span>
                        <span class="roles-pill">{{ $role->users_count }} usuarios</span>
                    </div>
                    @include('admin.roles.partials.row-actions', ['role' => $role])
                </article>
            @empty
                <div class="px-4 py-8 text-center text-gray-400">Aún no hay roles. Crea el primero para asignarlo a tu personal.</div>
            @endforelse
        </div>

        {{-- Escritorio --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="roles-table table-fixed">
                <thead>
                    <tr>
                        <th class="w-[22%] text-left">Rol</th>
                        <th class="w-[34%] text-left">Descripción</th>
                        <th class="w-[11%] text-center">Permisos</th>
                        <th class="w-[11%] text-center">Usuarios</th>
                        <th class="w-[10%] text-center">Estado</th>
                        <th class="w-[12%] text-center">Acc.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td class="font-semibold text-gray-800 truncate">{{ $role->name }}</td>
                            <td class="text-gray-500 truncate">{{ $role->description ?: '—' }}</td>
                            <td class="text-center"><span class="roles-pill roles-pill--primary">{{ $role->permissions_count }}</span></td>
                            <td class="text-center text-gray-700">{{ $role->users_count }}</td>
                            <td class="text-center"><span class="roles-status {{ $role->active ? 'roles-status--active' : 'roles-status--inactive' }}">{{ $role->active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="text-center">@include('admin.roles.partials.row-actions', ['role' => $role])</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-gray-400">Aún no hay roles. Crea el primero para asignarlo a tu personal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $roles->links() }}</div>
</div>
@endsection
