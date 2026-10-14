<div class="flex items-center justify-end gap-1.5">
    <a href="{{ route('admin.inventario.kardex', $variant) }}"
        class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition"
        title="Ver kardex" aria-label="Kardex de {{ $product?->name ?? 'producto' }}">
        <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
    </a>
    @can('inventory.move')
        <button type="button"
            class="open-stock-modal inline-flex items-center justify-center w-9 h-9 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition"
            data-product-id="{{ $product?->id }}" data-variant-id="{{ $variant->id }}"
            data-product-name="{{ $product?->name ?? 'Producto' }}"
            data-variant-name="{{ $variant->name ?: 'Presentación base' }}"
            data-current-stock="{{ $variantStock }}" title="Mover stock"
            aria-label="Mover stock de {{ $product?->name ?? 'producto' }}">
            <x-heroicon-o-arrows-up-down class="w-4 h-4" />
        </button>
    @endcan
</div>
