<div class="catalog-business-list-shell bg-white rounded-xl shadow-sm overflow-hidden flex flex-col">
    @php
        $primaryButtonClass =
            'inline-flex items-center justify-center gap-2 bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm font-medium text-center shrink-0';
        $secondaryButtonClass =
            'inline-flex items-center justify-center gap-2 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition text-sm font-medium text-center shrink-0';
    @endphp

    <div
        class="catalog-business-list-header p-4 sm:p-5 flex flex-col gap-3 shrink-0 lg:flex-row lg:items-start lg:justify-between">
        <div class="catalog-business-search min-w-0 flex-1">
            <label for="catalogTypeItemSearch-{{ $catalogTypeId }}">Buscar {{ $itemSingular }}</label>
            <div class="catalog-business-search-row">
                <div class="min-w-0 flex-1" wire:ignore>
                    <select id="catalogTypeItemSearch-{{ $catalogTypeId }}" class="select2 catalog-business-search-select"
                        data-placeholder="Buscar {{ $itemSingular }}">
                        <option value="">Todos los {{ $itemPlural }}</option>
                        @foreach ($allItems as $selectItem)
                            <option value="{{ $selectItem->id }}" @selected((int) $selectedItemId === (int) $selectItem->id)>
                                {{ $selectItem->name }}{{ $selectItem->category?->name ? ' / ' . $selectItem->category->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($selectedItemId)
                    <button type="button" wire:click="clearSelection" class="catalog-business-search-clear"
                        title="Borrar búsqueda" aria-label="Borrar búsqueda">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                @endif
            </div>
        </div>

        <div class="flex flex-col items-stretch gap-2 shrink-0 sm:flex-row sm:items-center">
            @if(\App\Support\Permissions\CatalogPermission::allows($isProductBusiness ? 'products' : 'services', 'create'))
            <a href="{{ route('admin.catalog-items.create', ['catalog_type_id' => $catalogTypeId, 'return_to_type' => 1]) }}"
                class="{{ $primaryButtonClass }}">
                + Agregar {{ $itemSingularTitle }}
            </a>
            @endif
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="catalog-business-empty text-center">
            <p class="font-medium text-gray-700 mb-2">
                {{ $selectedItemId ? 'No se encontró ese ' . $itemSingular . '.' : 'Aún no tienes ' . $itemPlural . ' aquí.' }}
            </p>
            <p class="text-sm mb-5">
                {{ $selectedItemId ? 'Limpia el buscador para volver a ver todos.' : 'Crea el primero para venderlo o mostrarlo en la web.' }}
            </p>
            @if ($selectedItemId)
                <button type="button" wire:click="clearSelection" class="{{ $secondaryButtonClass }}">
                    Mostrar todos
                </button>
            @endif
        </div>
    @else
        <div class="catalog-business-items-scroll">
            <div class="catalog-business-card-grid">
                @foreach ($items as $item)
                    @php
                        $configCount = $isProductBusiness
                            ? (int) $item->variants_count
                            : (int) $item->vehicle_type_prices_count;
                        $configLabel = $isProductBusiness ? 'Pres.' : 'Precios';
                    @endphp

                    <article class="catalog-business-item-card" wire:key="catalog-type-item-{{ $item->id }}">
                        <div class="catalog-business-item-main">
                            <div class="min-w-0">
                                <h4>{{ $item->name }}</h4>
                                <p>{{ \Illuminate\Support\Str::limit($item->description, 70) ?: 'Sin descripción' }}</p>
                            </div>
                            <div class="catalog-business-item-actions">
                                <a href="{{ route('admin.catalog-items.show', ['catalogItem' => $item, 'return_to_type' => 1]) }}"
                                    title="Ver {{ $itemSingular }}" aria-label="Ver {{ $itemSingular }}">
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                </a>
                                @if(\App\Support\Permissions\CatalogPermission::allows($isProductBusiness ? 'products' : 'services', 'update'))
                                <a href="{{ route('admin.catalog-items.edit', ['catalogItem' => $item, 'return_to_type' => 1]) }}"
                                    title="Editar {{ $itemSingular }}" aria-label="Editar {{ $itemSingular }}">
                                    <x-heroicon-o-pencil-square class="w-4 h-4" />
                                </a>
                                @endif
                                @if ($isProductBusiness)
                                    @if(\App\Support\Permissions\CatalogPermission::allows($isProductBusiness ? 'products' : 'services', 'update'))
                                    <a href="{{ route('admin.catalog-variants.create', ['catalog_item_id' => $item->id, 'catalog_type_id' => $catalogTypeId, 'return_to_type' => 1]) }}"
                                        title="Agregar presentación" aria-label="Agregar presentación">
                                        <x-heroicon-o-rectangle-stack class="w-4 h-4" />
                                    </a>
                                    @endif
                                @else
                                    @if(\App\Support\Permissions\CatalogPermission::allows($isProductBusiness ? 'products' : 'services', 'update'))
                                    <a href="{{ route('admin.catalog-service-prices.create', ['catalogItem' => $item->id, 'catalog_type_id' => $catalogTypeId, 'return_to_type' => 1]) }}"
                                        title="Precios por vehículos" aria-label="Precios por vehículos">
                                        <x-heroicon-o-banknotes class="w-4 h-4" />
                                    </a>
                                    @endif
                                @endif
                                @if(\App\Support\Permissions\CatalogPermission::allows($isProductBusiness ? 'products' : 'services', 'delete'))
                                <form method="POST" action="{{ route('admin.catalog-items.destroy', $item) }}"
                                    onsubmit="return confirm('¿Eliminar este {{ $itemSingular }}? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return_to_type" value="1">
                                    <button type="submit" title="Eliminar {{ $itemSingular }}"
                                        aria-label="Eliminar {{ $itemSingular }}">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>

                        <div class="catalog-business-item-meta">
                            <span class="catalog-business-chip">{{ $item->category?->name ?: 'Sin categoría' }}</span>
                            <strong>${{ number_format($item->display_price, 2) }}</strong>
                        </div>

                        <div class="catalog-business-item-footer">
                            <span>{{ $configLabel }} <strong>{{ $configCount }}</strong></span>
                            @if ($item->active)
                                <span class="catalog-business-status is-active">Activo</span>
                            @else
                                <span class="catalog-business-status">Oculto</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <script>
        (() => {
            const selectId = 'catalogTypeItemSearch-{{ $catalogTypeId }}';

            function initCatalogTypeItemSearch() {
                const select = document.getElementById(selectId);
                if (!select || !window.jQuery?.fn?.select2) return;

                const $select = window.jQuery(select);

                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    width: '100%',
                    placeholder: select.dataset.placeholder || 'Buscar',
                    allowClear: true,
                });

                $select
                    .off('change.catalogTypeItemSearch')
                    .on('change.catalogTypeItemSearch', function () {
                        @this.set('selectedItemId', this.value ? Number(this.value) : null);
                    });
            }

            function bindLivewireEvents() {
                if (!window.Livewire) return;

                Livewire.on('catalog-type-browser-cleared', () => {
                    const select = document.getElementById(selectId);
                    if (!select || !window.jQuery?.fn?.select2) return;
                    window.jQuery(select).val('').trigger('change.select2');
                });
            }

            document.addEventListener('DOMContentLoaded', initCatalogTypeItemSearch);
            document.addEventListener('livewire:init', () => {
                initCatalogTypeItemSearch();
                bindLivewireEvents();
            });
            document.addEventListener('livewire:navigated', initCatalogTypeItemSearch);
            document.addEventListener('livewire:updated', initCatalogTypeItemSearch);

            initCatalogTypeItemSearch();

            if (window.Livewire) {
                bindLivewireEvents();
            }

            document.addEventListener('catalog-type-browser-cleared', () => {
                const select = document.getElementById(selectId);
                if (!select || !window.jQuery?.fn?.select2) return;
                window.jQuery(select).val('').trigger('change.select2');
            });
        })();
    </script>
</div>
