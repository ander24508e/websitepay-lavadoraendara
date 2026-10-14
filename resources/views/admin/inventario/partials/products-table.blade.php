@php
    $showBusinessColumn = $selectedTypeId === 0;
    $tableColumns = 5 + ($showBusinessColumn ? 1 : 0) + ($canViewCosts ? 1 : 0);
@endphp

<section class="{{ $panelClass }}">
    <div class="px-4 sm:px-5 py-4 border-b border-gray-200/70 flex items-center justify-between gap-2">
        <h3 class="font-semibold text-gray-900">Stock actual</h3>
        <span class="text-xs font-semibold text-gray-400">{{ $variants->total() }} registros</span>
    </div>

    <div class="lg:hidden divide-y divide-gray-100">
        @forelse($variants as $variant)
            @php
                $product = $variant->item;
                $variantStock = (int) ($variant->stock ?? 0);
                $variantMinStock = (int) ($variant->min_stock ?? 0);
                $isOutOfStock = $variantStock <= 0;
                $isLowStock = !$isOutOfStock && $variantMinStock > 0 && $variantStock <= $variantMinStock;
                $locationTotal = (int) $variant->locationStocks->sum('quantity');
                $hasStockMismatch = $variant->locationStocks->isNotEmpty() && $locationTotal !== $variantStock;
                $totalValue = $variantStock * (float) ($variant->cost_price ?? 0);
            @endphp
            <article class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 break-words">{{ $product?->name ?? 'Producto' }}</p>
                        <p class="text-xs text-gray-500 break-words">
                            {{ $variant->name ?: 'Presentación base' }}@if ($variant->sku) · <span class="font-mono">{{ $variant->sku }}</span>@endif
                        </p>
                        <p class="text-xs text-gray-400 break-words">
                            @if ($showBusinessColumn){{ $product?->type?->name ?? '—' }} · @endif{{ $product?->category?->name ?? 'Sin categoría' }}
                        </p>
                    </div>
                    @include('admin.inventario.partials.stock-status')
                </div>

                <div class="flex items-end justify-between gap-3">
                    <div class="flex gap-6 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">Stock</p>
                            <p class="font-semibold text-gray-900">{{ $variantStock }} <span class="text-xs font-normal text-gray-400">/ mín. {{ $variantMinStock }}</span></p>
                        </div>
                        @if ($canViewCosts)
                            <div>
                                <p class="text-xs text-gray-400">Valor</p>
                                <p class="font-semibold text-gray-900">${{ number_format($totalValue, 2) }}</p>
                            </div>
                        @endif
                    </div>
                    @include('admin.inventario.partials.stock-actions')
                </div>
            </article>
        @empty
            <div class="px-4 py-8 text-center text-sm text-gray-400">No hay presentaciones para este negocio o búsqueda.</div>
        @endforelse
    </div>

    <div class="hidden lg:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">Producto</th>
                    @if ($showBusinessColumn)
                        <th class="px-4 py-3 text-left">Negocio</th>
                    @endif
                    <th class="px-4 py-3 text-left">Categoría</th>
                    <th class="px-4 py-3 text-center">Stock</th>
                    @if ($canViewCosts)
                        <th class="px-4 py-3 text-right">Valor</th>
                    @endif
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-right">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($variants as $variant)
                    @php
                        $product = $variant->item;
                        $variantStock = (int) ($variant->stock ?? 0);
                        $variantMinStock = (int) ($variant->min_stock ?? 0);
                        $isOutOfStock = $variantStock <= 0;
                        $isLowStock = !$isOutOfStock && $variantMinStock > 0 && $variantStock <= $variantMinStock;
                        $locationTotal = (int) $variant->locationStocks->sum('quantity');
                        $hasStockMismatch = $variant->locationStocks->isNotEmpty() && $locationTotal !== $variantStock;
                        $totalValue = $variantStock * (float) ($variant->cost_price ?? 0);
                    @endphp
                    <tr class="hover:bg-gray-50/70">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900 max-w-[260px] truncate">{{ $product?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500 max-w-[260px] truncate">
                                {{ $variant->name ?: 'Presentación base' }}@if ($variant->sku) · <span class="font-mono">{{ $variant->sku }}</span>@endif
                            </p>
                        </td>
                        @if ($showBusinessColumn)
                            <td class="px-4 py-3 text-gray-700">
                                <p class="max-w-[160px] truncate">{{ $product?->type?->name ?? '—' }}</p>
                            </td>
                        @endif
                        <td class="px-4 py-3 text-gray-700">
                            <p class="max-w-[160px] truncate">{{ $product?->category?->name ?? 'Sin categoría' }}</p>
                        </td>
                        <td class="px-4 py-3 text-center whitespace-nowrap"
                            title="{{ $hasStockMismatch ? 'Stock por ubicación: ' . $locationTotal : '' }}">
                            <span class="font-semibold text-gray-900">{{ $variantStock }}</span>
                            <span class="text-xs text-gray-400">/ mín. {{ $variantMinStock }}</span>
                        </td>
                        @if ($canViewCosts)
                            <td class="px-4 py-3 text-right text-gray-900 whitespace-nowrap">${{ number_format($totalValue, 2) }}</td>
                        @endif
                        <td class="px-4 py-3 text-center">
                            @include('admin.inventario.partials.stock-status')
                        </td>
                        <td class="px-4 py-3">
                            @include('admin.inventario.partials.stock-actions')
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $tableColumns }}" class="px-4 py-10 text-center text-gray-400">
                            No hay presentaciones para este negocio o búsqueda.
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
