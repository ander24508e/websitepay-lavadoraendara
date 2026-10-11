@extends('layouts.admin')

@section('title', 'Compras de Inventario')

@section('content')
<div class="mx-auto w-full max-w-full overflow-x-hidden px-3 pb-4 sm:px-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Compras</h2>
            <p class="text-sm text-gray-500 mt-1">Registra compras y entradas automáticas a una ubicación.</p>
        </div>
        <a href="{{ route('admin.inventario.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Volver</a>
    </div>

    @can('inventory.purchases.create')
    <form method="POST" action="{{ route('admin.inventario.purchases.store') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-5">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                <select name="supplier_id" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
                    <option value="">Sin proveedor</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación ingreso</label>
                <select name="inventory_location_id" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm" required>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Factura / referencia</label>
                <input type="text" name="document_number" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                <input type="date" name="purchase_date" value="{{ now()->toDateString() }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descuento</label>
                <input type="number" name="discount_total" min="0" step="0.01" value="0" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Impuesto</label>
                <input type="number" name="tax_total" min="0" step="0.01" value="0" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-3 text-center">Producto / presentación</th>
                        <th class="px-3 py-3 text-center w-32">Cantidad</th>
                        <th class="px-3 py-3 text-center w-40">Costo unitario</th>
                        <th class="px-3 py-3 text-center w-40">Lote</th>
                        <th class="px-3 py-3 text-center w-40">Vence</th>
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
                            <td class="px-3 py-3"><input type="number" name="items[{{ $i }}][unit_cost]" min="0" step="0.01" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" {{ $i === 0 ? 'required' : '' }}></td>
                            <td class="px-3 py-3"><input type="text" name="items[{{ $i }}][batch_number]" maxlength="255" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></td>
                            <td class="px-3 py-3"><input type="date" name="items[{{ $i }}][expires_at]" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" placeholder="Notas opcionales"></textarea>
        <button class="bg-gray-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">Registrar compra</button>
    </form>
    @endcan

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-center">Compra</th>
                    <th class="px-4 py-3 text-center">Proveedor</th>
                    <th class="px-4 py-3 text-center">Ubicación</th>
                    <th class="px-4 py-3 text-center">Items</th>
                    <th class="px-4 py-3 text-center">Total</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($purchases as $purchase)
                    <tr>
                        <td class="px-4 py-3 text-center font-semibold text-gray-800">#{{ $purchase->id }}<br><span class="text-xs text-gray-400">{{ $purchase->document_number ?: '-' }}</span></td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $purchase->supplier?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $purchase->location?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $purchase->items->count() }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-gray-800">${{ number_format((float) $purchase->total, 2) }}</td>
                        <td class="px-4 py-3 text-center"><span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-semibold">{{ $purchase->status }}</span></td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $purchase->purchase_date?->format('d/m/Y') ?? $purchase->created_at?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No hay compras.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $purchases->links() }}
</div>
@endsection
