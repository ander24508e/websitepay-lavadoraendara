@php
    /** @var \App\Models\User|null $usuario */
    $usuario ??= null;
    $currentRoleId = old('role_id', $usuario?->staffRole()?->id);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div><label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label><input type="text" name="name" value="{{ old('name', $usuario?->name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>@error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror</div>
    <div><label class="block text-sm font-medium text-gray-700 mb-1">Correo</label><input type="email" name="email" value="{{ old('email', $usuario?->email) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>@error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror</div>
    <div><label class="block text-sm font-medium text-gray-700 mb-1">Telefono</label><input type="text" name="telefono" value="{{ old('telefono', $usuario?->telefono) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
    <div><label class="block text-sm font-medium text-gray-700 mb-1">Direccion</label><input type="text" name="direccion" value="{{ old('direccion', $usuario?->direccion) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
        @if($roles->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 px-3 py-3 text-sm text-gray-500">
                No hay roles disponibles para asignar.
                @can('roles.create')<a href="{{ route('admin.roles.create') }}" class="font-semibold text-gray-800 underline">Crea un rol primero</a>.@endcan
            </div>
        @else
            <select name="role_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                <option value="">Selecciona un rol</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) $currentRoleId === (string) $role->id)>
                        {{ $role->name }} ({{ $role->permissions->count() }} permisos){{ $role->active ? '' : ' · inactivo' }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Cada usuario tiene un solo rol. Los permisos se administran desde el rol.</p>
        @endif
        @error('role_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">{{ $usuario ? 'Nueva contraseña' : 'Contraseña' }}</label>
        <div class="relative">
            <input id="password" type="password" name="password" class="w-full border border-gray-300 rounded-lg pl-3 pr-11 py-2 text-sm" @if($usuario) placeholder="Dejar vacío para conservarla" @else required @endif>
            <button type="button" data-password-toggle="password" class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gray-400 rounded-r-lg" aria-label="Mostrar contraseña" aria-pressed="false">
                <x-heroicon-o-eye class="h-5 w-5" data-password-visible-icon />
                <x-heroicon-o-eye-slash class="hidden h-5 w-5" data-password-hidden-icon />
            </button>
        </div>
        @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
        <div class="relative">
            <input id="password_confirmation" type="password" name="password_confirmation" class="w-full border border-gray-300 rounded-lg pl-3 pr-11 py-2 text-sm" @unless($usuario) required @endunless>
            <button type="button" data-password-toggle="password_confirmation" class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-gray-400 rounded-r-lg" aria-label="Mostrar confirmación de contraseña" aria-pressed="false">
                <x-heroicon-o-eye class="h-5 w-5" data-password-visible-icon />
                <x-heroicon-o-eye-slash class="hidden h-5 w-5" data-password-hidden-icon />
            </button>
        </div>
    </div>
</div>
