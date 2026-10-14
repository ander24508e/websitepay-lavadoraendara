<div class="mb-5 flex flex-col gap-2 rounded-lg border border-gray-200/70 bg-white p-2 shadow-sm lg:flex-row lg:items-center lg:justify-between">
    <div class="min-w-0 overflow-x-auto">
        <div class="flex min-w-max items-center gap-1">
            <a href="{{ route('admin.inventario.index', array_filter(['q' => request('q')])) }}"
                class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition {{ $selectedTypeId === 0 ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                Todos
            </a>
            @foreach ($productTypes as $type)
                <a href="{{ route('admin.inventario.index', array_filter(['catalog_type_id' => $type->id, 'q' => request('q')])) }}"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition {{ $selectedTypeId === $type->id ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $type->name }}
                </a>
            @endforeach
        </div>
    </div>

    <form method="GET" action="{{ route('admin.inventario.index') }}" class="relative w-full lg:w-80 lg:shrink-0">
        <input type="hidden" name="catalog_type_id" value="{{ $selectedTypeId ?: '' }}">
        <x-heroicon-o-magnifying-glass
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input type="search" name="q" value="{{ request('q') }}"
            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300"
            placeholder="Buscar producto o SKU">
    </form>
</div>
