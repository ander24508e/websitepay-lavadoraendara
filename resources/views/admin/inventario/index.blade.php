@extends('layouts.admin')

@section('title', 'Inventario')

@section('content')
    @php
        $hasProductTypes = $productTypes->isNotEmpty();
        $authUser = auth()->user();
        $primaryProductUrl = $hasProductTypes && $authUser->can('catalog.products.create')
            ? route(
                'admin.catalog-items.create',
                array_filter(['inventory' => 1, 'catalog_type_id' => $selectedTypeId ?: null]),
            )
            : null;
        $newMovementUrl = $hasProductTypes && $authUser->can('inventory.move') ? route('admin.inventario.create') : null;
        $moreActions = $hasProductTypes
            ? [
                ['can' => 'inventory.locations.view', 'route' => route('admin.inventario.locations'), 'title' => 'Ubicaciones', 'icon' => 'building-storefront'],
                ['can' => 'inventory.periods.view', 'route' => route('admin.inventario.periods'), 'title' => 'Cierres', 'icon' => 'lock-closed'],
                ['can' => 'inventory.import', 'route' => route('admin.inventario.import'), 'title' => 'Importar CSV', 'icon' => 'arrow-up-tray'],
                [
                    'can' => 'inventory.export',
                    'route' => route(
                        'admin.inventario.export',
                        array_filter(['catalog_type_id' => $selectedTypeId ?: null]),
                    ),
                    'title' => 'Exportar CSV',
                    'icon' => 'arrow-down-tray',
                ],
            ]
            : [];
        $moreActions = array_values(array_filter($moreActions, fn ($tool) => $authUser->can($tool['can'])));
        $panelClass = 'bg-white rounded-lg border border-gray-200/70 shadow-sm overflow-hidden min-w-0';
    @endphp

    <div class="w-full max-w-[1600px] mx-auto px-3 sm:px-5 xl:px-8 pb-8">
        @include('admin.inventario.partials.header')

        @unless ($hasProductTypes)
            @include('admin.inventario.partials.empty-state')
        @else
            @include('admin.inventario.partials.business-tabs')
            @include('admin.inventario.partials.stats')

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-5">
                @include('admin.inventario.partials.products-table')
                @include('admin.inventario.partials.recent-movements')
            </div>

            @include('admin.inventario.partials.stock-modal')
        @endunless
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('stockModal');
            if (!modal) return;

            const productName = document.getElementById('stockModalProduct');
            const variantName = document.getElementById('stockModalVariant');
            const currentStock = document.getElementById('stockModalCurrent');
            const productInput = document.getElementById('stockCatalogItemId');
            const variantInput = document.getElementById('stockCatalogVariantId');
            const quantityInput = document.getElementById('stockQuantity');

            function openModal(button) {
                const variantId = button.dataset.variantId || '';

                productName.textContent = button.dataset.productName || 'Producto';
                variantName.textContent = button.dataset.variantName || 'Producto base';
                currentStock.textContent = button.dataset.currentStock || '0';
                productInput.value = variantId ? '' : (button.dataset.productId || '');
                variantInput.value = variantId;
                quantityInput.value = 1;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                quantityInput.focus();
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.querySelectorAll('.open-stock-modal').forEach((button) => {
                button.addEventListener('click', () => openModal(button));
            });

            modal.querySelectorAll('[data-stock-close]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        });
    </script>
@endpush
