@extends('layouts.admin')

@section('title', 'Transferencias de Inventario')

@section('content')
<div class="mx-auto w-full max-w-full overflow-x-hidden px-3 pb-4 sm:px-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Transferencias</h2>
            <p class="text-sm text-gray-500 mt-1">Mueve stock entre bodegas, sucursales o puntos de venta.</p>
        </div>
        <a href="{{ route('admin.inventario.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Volver</a>
    </div>

    @can('inventory.transfers.create')
    <form method="POST" action="{{ route('admin.inventario.transfers.store') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-5">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                <select name="from_location_id" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm" required>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hacia</label>
                <select name="to_location_id" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm" required>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                <input type="text" name="reference" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-3 text-center">Producto / presentación</th>
                        <th class="px-3 py-3 text-center w-32">Cantidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @for($i = 0; $i < 5; $i++)
                        <tr>
                            <td class="px-3 py-3">
                                <select name="items[{{ $i }}][catalog_item_variant_id]" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" {{ $i === 0 ? 'required' : '' }}>
                                    <option value="">Selecciona</option>
                                    @foreach($variants as $variant)
                                        <option value="{{ $variant->id }}">{{ $variant->item?->name }} - {{ $variant->name }} {{ $variant->sku ? '(' . $variant->sku . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-3 py-3"><input type="number" name="items[{{ $i }}][quantity]" min="1" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" {{ $i === 0 ? 'required' : '' }}></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="Notas opcionales"></textarea>
        <button class="bg-gray-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">Registrar transferencia</button>
    </form>
    @endcan

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-center">Transferencia</th>
                    <th class="px-4 py-3 text-center">Desde</th>
                    <th class="px-4 py-3 text-center">Hacia</th>
                    <th class="px-4 py-3 text-center">Items</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($transfers as $transfer)
                    <tr>
                        <td class="px-4 py-3 text-center font-semibold text-gray-800">#{{ $transfer->id }}<br><span class="text-xs text-gray-400">{{ $transfer->reference ?: '-' }}</span></td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $transfer->fromLocation?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $transfer->toLocation?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $transfer->items->count() }}</td>
                        <td class="px-4 py-3 text-center"><span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-semibold">{{ $transfer->status }}</span></td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $transfer->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hay transferencias.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $transfers->links() }}
</div>
@endsection
