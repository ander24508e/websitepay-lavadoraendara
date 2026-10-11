@php
    /** @var \App\Models\Role $role */
    $isEdit = $role->exists;
    $checked = collect(old('permissions', $selectedPermissions));
    $groupCount = $permissionGroups->count();
@endphp

<div class="roles-page max-w-6xl mx-auto space-y-5">
    {{-- Encabezado --}}
    <div class="roles-card roles-header">
        <div class="flex items-center gap-3 min-w-0">
            <span class="roles-header__icon"><x-heroicon-o-shield-check class="w-5 h-5" /></span>
            <div class="min-w-0">
                <p class="roles-header__eyebrow">Roles</p>
                <h2 class="roles-header__title truncate">{{ $isEdit ? 'Editar rol: '.$role->name : 'Nuevo rol' }}</h2>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.roles.index') }}" class="roles-btn roles-btn--ghost">
                <x-heroicon-o-arrow-left class="w-4 h-4" />
                <span>Volver</span>
            </a>
            <button type="submit" form="roleForm" class="roles-btn roles-btn--primary">
                <x-heroicon-o-check class="w-4 h-4" />
                <span>{{ $isEdit ? 'Guardar cambios' : 'Crear rol' }}</span>
            </button>
        </div>
    </div>

    <form id="roleForm" method="POST" action="{{ $isEdit ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="roles-card p-4 sm:p-5 space-y-5">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Datos del rol --}}
        <section class="roles-panel space-y-4">
            <div class="flex items-start justify-between gap-3 flex-wrap border-b border-gray-200 pb-3">
                <div>
                    <h3 class="text-sm font-semibold">Datos del rol</h3>
                    <p class="text-xs text-gray-500">Completa la información principal antes de asignar permisos.</p>
                </div>
                <span class="roles-pill roles-pill--primary"><span data-selected-count>{{ $checked->count() }}</span>&nbsp;permisos seleccionados</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(0,16rem)] gap-4">
                <div class="space-y-4">
                    <div>
                        <label for="role-name" class="block text-sm font-medium mb-1">Nombre del rol *</label>
                        <input id="role-name" type="text" name="name" value="{{ old('name', $role->name) }}" maxlength="100"
                            class="roles-input" placeholder="Ej. Bodega, Ventas, Gerente" required>
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="role-description" class="block text-sm font-medium mb-1">Descripción</label>
                        <input id="role-description" type="text" name="description" value="{{ old('description', $role->description) }}" maxlength="500"
                            class="roles-input" placeholder="Para qué puesto es este rol (opcional)">
                        @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <label class="roles-toggle-card">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" class="roles-checkbox mt-0.5"
                        @checked((string) old('active', $role->active ? '1' : '0') === '1')>
                    <span>
                        <span class="block text-sm font-semibold">Activo</span>
                        <span class="block text-xs text-gray-500">Permite usar este rol en usuarios nuevos o existentes. Si lo desactivas, sus usuarios pierden el acceso al panel.</span>
                    </span>
                </label>
            </div>

            @if($isEdit && $role->users_count > 0)
                <p class="text-xs text-gray-500">Este rol está asignado a {{ $role->users_count }} {{ $role->users_count === 1 ? 'usuario' : 'usuarios' }}; los cambios se aplican de inmediato a todos.</p>
            @endif
        </section>

        {{-- Permisos --}}
        <section class="roles-panel space-y-4">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <h3 class="text-sm font-semibold">Permisos</h3>
                    <p class="text-xs text-gray-500">Marca los accesos que este rol podrá utilizar dentro del sistema.</p>
                </div>
                <div class="roles-search">
                    <x-heroicon-o-magnifying-glass />
                    <input type="search" class="roles-input" placeholder="Buscar módulo o permiso..." data-permission-search aria-label="Buscar módulo o permiso">
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 flex-wrap border-t border-gray-200 pt-3">
                <p class="text-xs text-gray-500">Se muestran hasta 3 módulos por vista. Desplaza lateralmente para ver más permisos.</p>
                <div class="flex items-center gap-2">
                    <button type="button" class="roles-btn roles-btn--ghost !py-1 !px-3 !text-xs" data-select-all="1">Marcar todo</button>
                    <button type="button" class="roles-btn roles-btn--ghost !py-1 !px-3 !text-xs" data-select-all="0">Quitar todo</button>
                    <span class="roles-pill">{{ $groupCount }} grupos</span>
                </div>
            </div>

            @error('permissions')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
            @error('permissions.*')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror

            <div class="roles-modules" data-permission-modules>
                @foreach($permissionGroups as $group)
                    <article class="roles-module" data-module data-module-label="{{ \Illuminate\Support\Str::lower($group['label']) }}">
                        <header class="roles-module__head">
                            <label>
                                <input type="checkbox" class="roles-checkbox" data-module-toggle aria-label="Marcar todos los permisos de {{ $group['label'] }}">
                                <span class="truncate">{{ $group['label'] }}</span>
                            </label>
                            <span class="roles-pill">{{ count($group['permissions']) }}</span>
                        </header>
                        <div class="roles-module__body">
                            @foreach($group['permissions'] as $permission => $label)
                                <label class="roles-permission" data-permission data-permission-text="{{ \Illuminate\Support\Str::lower($label.' '.$permission) }}">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" class="roles-checkbox mt-0.5"
                                        data-permission-input @checked($checked->contains($permission))>
                                    <span>
                                        <span class="roles-permission__label">{{ $label }}</span>
                                        <span class="roles-permission__key">{{ $permission }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 hidden" data-permission-empty>No hay módulos o permisos que coincidan con la búsqueda.</p>
        </section>
    </form>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('roleForm');
            if (!form) return;

            const counter = document.querySelector('[data-selected-count]');
            const inputs = () => form.querySelectorAll('[data-permission-input]');

            const syncModule = (module) => {
                const items = module.querySelectorAll('[data-permission-input]');
                const checked = module.querySelectorAll('[data-permission-input]:checked').length;
                const toggle = module.querySelector('[data-module-toggle]');
                toggle.checked = items.length > 0 && checked === items.length;
                toggle.indeterminate = checked > 0 && checked < items.length;
            };

            const refresh = () => {
                counter.textContent = form.querySelectorAll('[data-permission-input]:checked').length;
                form.querySelectorAll('[data-module]').forEach(syncModule);
            };

            form.addEventListener('change', (event) => {
                if (event.target.matches('[data-module-toggle]')) {
                    const module = event.target.closest('[data-module]');
                    module.querySelectorAll('[data-permission]:not(.is-hidden) [data-permission-input]')
                        .forEach((input) => { input.checked = event.target.checked; });
                }
                refresh();
            });

            document.querySelectorAll('[data-select-all]').forEach((button) => {
                button.addEventListener('click', () => {
                    const value = button.dataset.selectAll === '1';
                    inputs().forEach((input) => { input.checked = value; });
                    refresh();
                });
            });

            const search = document.querySelector('[data-permission-search]');
            const empty = document.querySelector('[data-permission-empty]');
            search?.addEventListener('input', () => {
                const term = search.value.trim().toLowerCase();
                let visibleModules = 0;

                form.querySelectorAll('[data-module]').forEach((module) => {
                    const moduleMatch = term === '' || module.dataset.moduleLabel.includes(term);
                    let visibleItems = 0;

                    module.querySelectorAll('[data-permission]').forEach((item) => {
                        const show = moduleMatch || item.dataset.permissionText.includes(term);
                        item.classList.toggle('is-hidden', !show);
                        if (show) visibleItems++;
                    });

                    module.classList.toggle('is-hidden', visibleItems === 0);
                    if (visibleItems > 0) visibleModules++;
                });

                empty.classList.toggle('hidden', visibleModules > 0);
            });

            refresh();
        });
    </script>
@endpush
