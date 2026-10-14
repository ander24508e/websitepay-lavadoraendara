<div id="stockModal" class="fixed inset-0 z-50 hidden items-center justify-center px-4 py-6">
    <div class="absolute inset-0 bg-gray-900/50" data-stock-close></div>
    <div class="relative w-full max-w-md bg-white rounded-lg shadow-xl overflow-hidden max-h-[92vh] overflow-y-auto">
        <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Mover stock</p>
                <h3 id="stockModalProduct" class="text-lg font-bold text-gray-900 truncate">Producto</h3>
                <p id="stockModalVariant" class="text-sm text-gray-500 truncate">Presentación</p>
            </div>
            <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition"
                data-stock-close aria-label="Cerrar">
                ×
            </button>
        </div>

        <form method="POST" action="{{ route('admin.inventario.movements.store') }}" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="catalog_item_id" id="stockCatalogItemId">
            <input type="hidden" name="catalog_item_variant_id" id="stockCatalogVariantId">

            <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-3 flex items-center justify-between">
                <span class="text-sm text-gray-500">Stock actual</span>
                <span id="stockModalCurrent" class="text-lg font-bold text-gray-900">0</span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Movimiento</label>
                <select name="type"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <option value="in">Entrada de stock</option>
                    <option value="out">Salida de stock</option>
                    <option value="adjust">Ajustar stock exacto</option>
                </select>
            </div>

            @if ($locations->count() > 1)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
                    <select name="inventory_location_id"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300">
                        <option value="">Ubicación principal automática</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
                    <input type="number" name="quantity" id="stockQuantity" min="1" value="1" required
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Costo unitario</label>
                    <input type="number" name="unit_cost" min="0" step="0.01"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300"
                        placeholder="Costo promedio" {{ $canViewCosts ? '' : 'disabled' }}>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Motivo</label>
                <input type="text" name="reason" maxlength="255" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300"
                    placeholder="Ej: compra, conteo, daño">
            </div>

            <details class="rounded-lg border border-gray-200">
                <summary class="cursor-pointer select-none px-3 py-2.5 text-sm font-medium text-gray-700">
                    Más detalles
                    <span class="text-xs font-normal text-gray-400">(referencia, lote, vencimiento, nota)</span>
                </summary>
                <div class="space-y-3 border-t border-gray-200 p-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                        <input type="text" name="reference" maxlength="255" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300"
                            placeholder="Ej: factura #001">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lote</label>
                            <input type="text" name="batch_number" maxlength="255" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vencimiento</label>
                            <input type="date" name="expires_at" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nota</label>
                        <input type="text" name="notes" maxlength="1000" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300"
                            placeholder="Detalle del movimiento">
                    </div>
                </div>
            </details>

            <div class="flex flex-col sm:flex-row justify-end gap-2 pt-2">
                <button type="button"
                    class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition text-sm font-semibold"
                    data-stock-close>
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition text-sm font-semibold">
                    Guardar movimiento
                </button>
            </div>
        </form>
    </div>
</div>
