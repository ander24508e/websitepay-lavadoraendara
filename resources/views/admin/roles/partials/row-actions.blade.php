<div class="flex items-center justify-end md:justify-center gap-1">
    @can('roles.update')
        <a href="{{ route('admin.roles.edit', $role) }}"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50 transition"
            title="Editar rol" aria-label="Editar rol {{ $role->name }}">
            <x-heroicon-o-pencil-square class="w-4 h-4" />
        </a>
    @endcan
    @can('roles.delete')
        @if($role->users_count > 0)
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-300 cursor-not-allowed"
                title="Tiene usuarios asignados; reasígnalos antes de eliminarlo">
                <x-heroicon-o-trash class="w-4 h-4" />
            </span>
        @else
            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('¿Eliminar el rol {{ $role->name }}?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:text-red-800 hover:bg-red-50 transition"
                    title="Eliminar rol" aria-label="Eliminar rol {{ $role->name }}">
                    <x-heroicon-o-trash class="w-4 h-4" />
                </button>
            </form>
        @endif
    @endcan
</div>
