<section class="{{ $panelClass }}">
    <div
        class="px-4 sm:px-5 py-4 border-b border-gray-200/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h3 class="font-semibold text-gray-900">Stock actual por presentación</h3>
            <p class="text-xs text-gray-400 mt-1">Cada fila representa una presentación con su SKU y stock propio.</p>
        </div>
        <span class="text-xs font-semibold text-gray-400">{{ $variants->total() }} registros</span>
    </div>

    <div class="lg:hidden space-y-3 p-4">
        @forelse($variants as $variant)
            @php
                $product = $variant->item;
                $locationTotal = (int) $variant->locationStocks->sum('quantity');
                $hasLocationDetail = $variant->locationStocks->isNotEmpty();
                $variantStock = (int) ($variant->stock ?? 0);
                $variantMinStock = (int) ($variant->min_stock ?? 0);
                $isOutOfStock = $variantStock <= 0;
                $isLowStock = !$isOutOfStock && $variantMinStock > 0 && $variantStock <= $variantMinStock;
                $hasStockMismatch = $hasLocationDetail && $locationTotal !== $variantStock;
                $totalValue = $variantStock * (float) ($variant->cost_price ?? 0);
            @endphp
            <article class="rounded-lg border border-gray-200/70 p-4 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 break-words">{{ $product?->name ?? 'Producto' }}</p>
                        <p class="text-xs text-gray-400 break-words">
                            {{ $product?->type?->name ?? '-' }} · {{ $product?->category?->name ?? 'Sin categoría' }}
                        </p>
                        <p class="text-sm text-gray-700 mt-1 break-words">{{ $variant->name ?: 'Presentación base' }}</p>
                    </div>
                    <span
                        class="shrink-0 {{ $isOutOfStock ? 'bg-red-100 text-red-700' : ($isLowStock ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }} px-2.5 py-1 rounded-full text-xs font-semibold">
                        {{ $isOutOfStock ? 'Agotado' : ($isLowStock ? 'Bajo' : 'Activo') }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">SKU</p>
                        <p class="font-mono text-xs text-gray-700 break-words">{{ $variant->sku ?: 'Sin SKU' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Stock</p>
                        <p class="font-semibold {{ $hasStockMismatch ? 'text-amber-700' : 'text-gray-900' }}">
                            {{ $variantStock }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Mínimo</p>
                        <p class="font-semibold text-gray-900">{{ $variantMinStock }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Precio</p>
                        <p class="font-semibold text-gray-900">${{ number_format((float) ($variant->price ?? 0), 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Costo</p>
                        <p class="font-semibold text-gray-900">
                            {{ $canViewCosts ? '$' . number_format((float) ($variant->cost_price ?? 0), 2) : '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Valor</p>
                        <p class="font-semibold text-gray-900">
                            {{ $canViewCosts ? '$' . number_format($totalValue, 2) : '-' }}
                        </p>
                    </div>
                </div>

                @if ($hasStockMismatch)
                    <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700">
                        Stock por ubicación: {{ $locationTotal }}. Revisar consistencia.
                    </p>
                @endif

                <div class="flex flex-wrap justify-end gap-2">
                    <a href="{{ route('admin.inventario.kardex', $variant) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                        Kardex
                    </a>
                    @can('inventory.move')
                    <button type="button"
                        class="open-stock-modal inline-flex items-center justify-center gap-2 bg-gray-900 text-white px-3 py-2 rounded-lg hover:bg-gray-700 transition text-sm font-semibold"
                        data-product-id="{{ $product?->id }}" data-variant-id="{{ $variant->id }}"
                        data-product-name="{{ $product?->name ?? 'Producto' }}"
                        data-variant-name="{{ $variant->name ?: 'Presentación base' }}"
                        data-current-stock="{{ $variantStock }}" title="Gestionar stock"
                        aria-label="Gestionar stock de {{ $product?->name ?? 'producto' }}">
                        <x-heroicon-o-archive-box class="w-4 h-4" />
                        Stock
                    </button>
                    @endcan
                </div>
            </article>
        @empty
            <div class="px-4 py-8 text-center text-gray-400">No hay presentaciones para este negocio o búsqueda.</div>
        @endforelse
    </div>

    <div class="hidden lg:block overflow-x-auto">
        <table class="min-w-[1120px] w-full text-sm">
            <thead class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Producto</th>
                    <th class="px-4 py-3 text-left">Negocio</th>
                    <th class="px-4 py-3 text-left">Categoría</th>
                    <th class="px-4 py-3 text-left">Presentación</th>
                    <th class="px-4 py-3 text-left">SKU</th>
                    <th class="px-4 py-3 text-center">Stock</th>
                    <th class="px-4 py-3 text-center">Mín.</th>
                    <th class="px-4 py-3 text-center">Costo</th>
                    <th class="px-4 py-3 text-center">Valor</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($variants as $variant)
                    @php
                        $product = $variant->item;
                        $locationTotal = (int) $variant->locationStocks->sum('quantity');
                        $hasLocationDetail = $variant->locationStocks->isNotEmpty();
                        $variantStock = (int) ($variant->stock ?? 0);
                        $variantMinStock = (int) ($variant->min_stock ?? 0);
                        $isOutOfStock = $variantStock <= 0;
                        $isLowStock = !$isOutOfStock && $variantMinStock > 0 && $variantStock <= $variantMinStock;
                        $hasStockMismatch = $hasLocationDetail && $locationTotal !== $variantStock;
                        $totalValue = $variantStock * (float) ($variant->cost_price ?? 0);
                    @endphp
                    <tr class="hover:bg-gray-50/70">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900 max-w-[220px] truncate">{{ $product?->name ?? '-' }}</p>
                            <p class="text-xs text-gray-400 max-w-[220px] truncate">
                                {{ $product?->description ?: 'Sin descripción' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            <p class="max-w-[150px] truncate">{{ $product?->type?->name ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            <p class="max-w-[150px] truncate">{{ $product?->category?->name ?? 'Sin categoría' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-gray-900 max-w-[180px] truncate">{{ $variant->name ?: 'Presentación base' }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-mono text-xs text-gray-700 max-w-[130px] truncate">{{ $variant->sku ?: 'Sin SKU' }}</p>
                        </td>
                        <td
                            class="px-4 py-3 text-center font-semibold {{ $hasStockMismatch ? 'text-amber-700' : ($isOutOfStock ? 'text-red-700' : ($isLowStock ? 'text-amber-700' : 'text-gray-900')) }}"
                            title="{{ $hasStockMismatch ? 'Stock por ubicación: ' . $locationTotal : '' }}">
                            {{ $variantStock }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $variantMinStock }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">
                            {{ $canViewCosts ? '$' . number_format((float) ($variant->cost_price ?? 0), 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-900 font-semibold">
                            {{ $canViewCosts ? '$' . number_format($totalValue, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($hasStockMismatch)
                                <span
                                    class="inline-flex bg-amber-100 text-amber-700 px-2 py-1 rounded-full text-xs font-medium">Revisar</span>
                            @elseif ($isOutOfStock)
                                <span
                                    class="inline-flex bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-medium">Agotado</span>
                            @elseif ($isLowStock)
                                <span
                                    class="inline-flex bg-amber-100 text-amber-700 px-2 py-1 rounded-full text-xs font-medium">Bajo stock</span>
                            @elseif ($variant->active && ($product?->active ?? false))
                                <span
                                    class="inline-flex bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Activo</span>
                            @else
                                <span
                                    class="inline-flex bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs font-medium">Oculto</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('admin.inventario.kardex', $variant) }}"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition"
                                    title="Kardex" aria-label="Kardex de {{ $product?->name ?? 'producto' }}">
                                    <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                                </a>
                                @can('inventory.move')
                                <button type="button"
                                    class="open-stock-modal inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition"
                                    data-product-id="{{ $product?->id }}" data-variant-id="{{ $variant->id }}"
                                    data-product-name="{{ $product?->name ?? 'Producto' }}"
                                    data-variant-name="{{ $variant->name ?: 'Presentación base' }}"
                                    data-current-stock="{{ $variantStock }}" title="Gestionar stock"
                                    aria-label="Gestionar stock de {{ $product?->name ?? 'producto' }}">
                                    <x-heroicon-o-archive-box class="w-4 h-4" />
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-10 text-center text-gray-400">
                            No hay presentaciones para este negocio o búsqueda. Crea un producto o cambia el filtro.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($variants->hasPages())
        <div class="p-4 border-t">{{ $variants->links() }}</div>
    @endif
</section>
