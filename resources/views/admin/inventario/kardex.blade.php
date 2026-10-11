@extends('layouts.admin')

@section('title', 'Kardex')

@section('content')
<div class="mx-auto w-full max-w-full overflow-x-hidden px-3 pb-4 sm:px-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Kardex</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $variant->item?->name }} - {{ $variant->name }} {{ $variant->sku ? '(' . $variant->sku . ')' : '' }}</p>
        </div>
        <div class="flex gap-2">
            @can('inventory.export')<a href="{{ route('admin.inventario.kardex.export', ['variant' => $variant, ...request()->query()]) }}" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Exportar</a>@endcan
            <a href="{{ route('admin.inventario.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Volver</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Stock global</p>
            <p class="text-2xl font-bold text-gray-800 mt-2">{{ $variant->stock ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Precio</p>
            <p class="text-2xl font-bold text-gray-800 mt-2">${{ number_format((float) ($variant->price ?? 0), 2) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Costo</p>
            <p class="text-2xl font-bold text-gray-800 mt-2">{{ $canViewCosts ? '$' . number_format((float) ($variant->cost_price ?? 0), 2) : 'Restringido' }}</p>
        </div>
    </div>

    @if($stocks->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <h3 class="font-semibold text-gray-800 mb-3">Stock por ubicación</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($stocks as $stock)
                    <div class="rounded-lg border border-gray-100 px-3 py-2">
                        <p class="text-sm font-semibold text-gray-800">{{ $stock->location?->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">Cantidad: {{ $stock->quantity }} · Mín: {{ $stock->min_stock }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 grid grid-cols-1 md:grid-cols-6 gap-3">
        <select name="inventory_location_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <option value="">Todas las ubicaciones</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" {{ (int) request('inventory_location_id') === $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
            @endforeach
        </select>
        <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos los tipos</option>
            <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Entradas</option>
            <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Salidas</option>
            <option value="adjust" {{ request('type') === 'adjust' ? 'selected' : '' }}>Ajustes</option>
        </select>
        <select name="reason" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <option value="">Todos los motivos</option>
            @foreach($reasons as $reason)
                <option value="{{ $reason }}" {{ request('reason') === $reason ? 'selected' : '' }}>{{ $reason }}</option>
            @endforeach
        </select>
        <input type="text" name="reference" value="{{ request('reference') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="Referencia">
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
        <button class="md:col-span-6 bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
    </form>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-3 py-3 text-center">Fecha</th>
                    <th class="px-3 py-3 text-center">Tipo</th>
                    <th class="px-3 py-3 text-center">Ubicación</th>
                    <th class="px-3 py-3 text-center">Motivo</th>
                    <th class="px-3 py-3 text-center">Lote</th>
                    <th class="px-3 py-3 text-center">Entrada</th>
                    <th class="px-3 py-3 text-center">Salida</th>
                    <th class="px-3 py-3 text-center">Costo unit.</th>
                    <th class="px-3 py-3 text-center">Total mov.</th>
                    <th class="px-3 py-3 text-center">Saldo cant.</th>
                    <th class="px-3 py-3 text-center">Saldo costo</th>
                    <th class="px-3 py-3 text-center">Saldo total</th>
                    <th class="px-3 py-3 text-center">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($movements as $movement)
                    <tr class="{{ $movement->voided_at ? 'text-gray-400 line-through' : '' }}">
                        <td class="px-3 py-3 text-center">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-3 text-center font-semibold">{{ strtoupper($movement->type) }}</td>
                        <td class="px-3 py-3 text-center">{{ $movement->location?->name ?? ($movement->fromLocation?->name && $movement->toLocation?->name ? $movement->fromLocation->name . ' -> ' . $movement->toLocation->name : '-') }}</td>
                        <td class="px-3 py-3 text-center">{{ $movement->reason ?: '-' }}<br><span class="text-xs text-gray-400">{{ $movement->reference ?: '' }}</span></td>
                        <td class="px-3 py-3 text-center">{{ $movement->batch_number ?: '-' }}<br><span class="text-xs text-gray-400">{{ $movement->expires_at?->format('d/m/Y') ?: '' }}</span></td>
                        <td class="px-3 py-3 text-center text-green-700">{{ $movement->type === 'in' ? $movement->quantity : '-' }}</td>
                        <td class="px-3 py-3 text-center text-red-700">{{ $movement->type === 'out' ? $movement->quantity : '-' }}</td>
                        <td class="px-3 py-3 text-center">{{ $canViewCosts ? '$' . number_format((float) ($movement->unit_cost ?? 0), 2) : '-' }}</td>
                        <td class="px-3 py-3 text-center">{{ $canViewCosts ? '$' . number_format((float) ($movement->total_cost ?? 0), 2) : '-' }}</td>
                        <td class="px-3 py-3 text-center">{{ $movement->balance_quantity ?? $movement->stock_after ?? '-' }}</td>
                        <td class="px-3 py-3 text-center">{{ $canViewCosts ? '$' . number_format((float) ($movement->balance_unit_cost ?? 0), 2) : '-' }}</td>
                        <td class="px-3 py-3 text-center">{{ $canViewCosts ? '$' . number_format((float) ($movement->balance_total_cost ?? 0), 2) : '-' }}</td>
                        <td class="px-3 py-3 text-center">{{ $movement->user?->name ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="px-4 py-8 text-center text-gray-400">Sin movimientos para este producto.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $movements->links() }}
</div>
@endsection
