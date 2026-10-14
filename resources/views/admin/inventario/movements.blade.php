@extends('layouts.admin')

@section('title', 'Movimientos de inventario')

@section('content')
    @php
        $movementLabels = ['in' => 'Entrada', 'out' => 'Salida', 'adjust' => 'Ajuste'];
        $movementSigns = ['in' => '+', 'out' => '−'];
        $fieldClass = 'h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300';
        $hasFilters = collect(request()->only(['q', 'catalog_type_id', 'movement_type', 'movement_date_from', 'movement_date_to']))
            ->filter()
            ->isNotEmpty();
    @endphp

    <div class="w-full max-w-[1600px] mx-auto px-3 sm:px-5 xl:px-8 pb-8">
        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <span
                    class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-900 shadow-sm">
                    <x-heroicon-o-arrows-up-down class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <h3 class="text-2xl sm:text-3xl font-medium leading-tight text-gray-900">Movimientos</h3>
                    <p class="mt-1.5 max-w-3xl text-sm text-gray-500">Historial de entradas, salidas y ajustes de stock.</p>
                </div>
            </div>

            @can('inventory.move')
                <a href="{{ route('admin.inventario.create') }}"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-gray-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700">
                    <x-heroicon-o-plus class="h-5 w-5" />
                    Nuevo movimiento
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('admin.inventario.movements') }}"
            class="mb-5 grid grid-cols-1 gap-2 rounded-lg border border-gray-200/70 bg-white p-3 shadow-sm sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))_auto]">
            <div class="relative">
                <x-heroicon-o-magnifying-glass
                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                <input type="search" name="q" value="{{ request('q') }}" class="{{ $fieldClass }} pl-9"
                    placeholder="Producto, SKU, motivo o referencia">
            </div>
            <select name="catalog_type_id" class="{{ $fieldClass }}" aria-label="Negocio">
                <option value="">Todos los negocios</option>
                @foreach ($productTypes as $type)
                    <option value="{{ $type->id }}" @selected($selectedTypeId === $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
            <select name="movement_type" class="{{ $fieldClass }}" aria-label="Tipo de movimiento">
                <option value="">Todos los tipos</option>
                @foreach ($movementLabels as $value => $label)
                    <option value="{{ $value }}" @selected($movementType === $value)>{{ $label }}s</option>
                @endforeach
            </select>
            <input type="date" name="movement_date_from" value="{{ $movementDateFrom }}" class="{{ $fieldClass }}"
                aria-label="Desde" title="Desde">
            <input type="date" name="movement_date_to" value="{{ $movementDateTo }}" class="{{ $fieldClass }}"
                aria-label="Hasta" title="Hasta">
            <div class="flex gap-2">
                <button
                    class="inline-flex h-10 flex-1 items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-700">
                    Filtrar
                </button>
                @if ($hasFilters)
                    <a href="{{ route('admin.inventario.movements') }}"
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-gray-100 px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>

        <section class="bg-white rounded-lg border border-gray-200/70 shadow-sm overflow-hidden min-w-0">
            <div class="px-4 sm:px-5 py-4 border-b border-gray-200/70 flex items-center justify-between gap-2">
                <h3 class="font-semibold text-gray-900">Historial</h3>
                <span class="text-xs font-semibold text-gray-400">{{ $movements->total() }} registros</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-center">Cantidad</th>
                            <th class="px-4 py-3 text-center">Stock</th>
                            <th class="px-4 py-3 text-left">Motivo</th>
                            <th class="px-4 py-3 text-left">Usuario</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($movements as $movement)
                            @php
                                $locationName = $movement->location?->name
                                    ?? (($movement->fromLocation || $movement->toLocation)
                                        ? ($movement->fromLocation?->name ?? '—') . ' → ' . ($movement->toLocation?->name ?? '—')
                                        : null);
                            @endphp
                            <tr class="hover:bg-gray-50/70 {{ $movement->voided_at ? 'text-gray-400' : '' }}">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                    {{ $movement->created_at?->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-900 max-w-[240px] truncate">{{ $movement->variant->item->name ?? '—' }}</p>
                                    <p class="text-xs text-gray-500 max-w-[240px] truncate">
                                        {{ $movement->variant->name ?? '—' }}@if ($locationName) · {{ $locationName }}@endif
                                    </p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                    {{ $movementLabels[$movement->type] ?? ucfirst($movement->type) }}
                                    @if ($movement->voided_at)
                                        <span class="ml-1 inline-flex bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs font-medium">Anulado</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-900 {{ $movement->voided_at ? 'line-through' : '' }}">
                                    {{ $movementSigns[$movement->type] ?? '' }}{{ $movement->quantity }}
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap text-gray-700">
                                    {{ $movement->stock_before ?? 0 }} → {{ $movement->stock_after ?? 0 }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    <p class="max-w-[220px] truncate">{{ $movement->reason ?: '—' }}</p>
                                    @if ($movement->reference)
                                        <p class="text-xs text-gray-400 max-w-[220px] truncate">{{ $movement->reference }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $movement->user->name ?? 'Sistema' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($movement->variant)
                                            <a href="{{ route('admin.inventario.kardex', $movement->variant) }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-700 hover:bg-gray-100 transition"
                                                title="Ver kardex" aria-label="Ver kardex">
                                                <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                                            </a>
                                        @endif
                                        @if (! $movement->voided_at)
                                            @can('inventory.update')
                                                <a href="{{ route('admin.inventario.movements.edit', $movement) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-700 hover:bg-gray-100 transition"
                                                    title="Editar movimiento" aria-label="Editar movimiento">
                                                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                                                </a>
                                            @endcan
                                            @can('inventory.void')
                                                <form method="POST" action="{{ route('admin.inventario.movements.destroy', $movement) }}"
                                                    onsubmit="return confirm('¿Anular este movimiento y registrar una reversa?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 transition"
                                                        title="Anular movimiento" aria-label="Anular movimiento">
                                                        <x-heroicon-o-trash class="w-4 h-4" />
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-gray-400">
                                    No hay movimientos para estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($movements->hasPages())
                <div class="p-4 border-t">{{ $movements->links() }}</div>
            @endif
        </section>
    </div>
@endsection
