@extends('layouts.admin')

@section('title', $catalogItem->name)

@push('styles')
    @vite($catalogItem->type?->business_model === \App\Models\CatalogType::BUSINESS_MODEL_PRODUCTS
        ? 'resources/scss/Catalogo/catalogo-products.scss'
        : 'resources/scss/Catalogo/catalogo-services.scss')
@endpush

@section('content')
    @php
        $returnUrl = $returnUrl ?? route('admin.catalog-types.show', $catalogItem->catalog_type_id);
        $returnContext = $returnContext ?? [];
        $isServiceContext =
            ($catalogItem->type?->business_model ?? 'services') === \App\Models\CatalogType::BUSINESS_MODEL_SERVICES;
        $configItems = $isServiceContext ? $catalogItem->vehicleTypePrices : $catalogItem->variants;
        $configCount = $configItems->count();
        $itemLabel = $isServiceContext ? 'servicio' : 'producto';
        $configSingular = $isServiceContext ? 'precio por vehiculo' : 'presentacion';
        $configPlural = $isServiceContext ? 'precios por vehiculo' : 'presentaciones';
        $configSearchId = 'catalogItemConfigSearch-' . $catalogItem->id;
        $addConfigUrl = $isServiceContext
            ? route('admin.catalog-service-prices.create', ['catalogItem' => $catalogItem, ...$returnContext])
            : route('admin.catalog-variants.create', ['catalog_item_id' => $catalogItem->id]);
    @endphp

    <div class="catalog-business-show catalog-item-detail-show">
        <div class="catalog-item-detail-header">
            <a href="{{ $returnUrl }}"
                class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-gray-800">
                <span aria-hidden="true">&larr;</span>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800 sm:text-2xl">{{ $catalogItem->name }}</h2>
                <p class="text-sm text-gray-400">Detalle del {{ $itemLabel }} y sus {{ $configPlural }}.</p>
            </div>
        </div>

        <div class="catalog-item-detail-layout">
            <aside>
                <div class="catalog-item-detail-info rounded-xl bg-white p-5 shadow-sm">
                    <div class="catalog-item-detail-image-wrap">
                        <img src="{{ $catalogItem->image_url }}" alt="{{ $catalogItem->name }}"
                            class="catalog-item-detail-image">
                    </div>

                    <h3>Informacion del {{ ucfirst($itemLabel) }}</h3>

                    <div class="catalog-item-detail-facts">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Nombre</p>
                            <p class="font-semibold text-gray-800 break-words">{{ $catalogItem->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Seccion</p>
                            <p class="text-gray-800">{{ $catalogItem->type?->name ?? 'Sin seccion' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Categoria</p>
                            <p class="text-gray-800">{{ $catalogItem->category?->name ?? 'Sin categoria' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Descripcion</p>
                            <p class="text-gray-800">{{ $catalogItem->description ?: 'Sin descripcion adicional.' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">
                                {{ $isServiceContext ? 'Precios' : 'Presentaciones' }}
                            </p>
                            <p class="font-semibold text-gray-800">{{ $configCount }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Estado</p>
                            @if ($catalogItem->active)
                                <span class="catalog-business-status is-active">Activo</span>
                            @else
                                <span class="catalog-business-status">Oculto</span>
                            @endif
                        </div>
                    </div>

                    <div class="catalog-item-detail-badges">
                        @if ($catalogItem->featured)
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">Destacado</span>
                        @endif
                        @if ($catalogItem->purchasable)
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">Se puede vender</span>
                        @endif
                        @if ($catalogItem->reservable)
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">Se puede reservar</span>
                        @endif
                        @if ($catalogItem->uses_inventory)
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">Controla stock</span>
                        @endif
                    </div>

                    <div class="catalog-item-detail-buttons">
                        @if(\App\Support\Permissions\CatalogPermission::allows($catalogItem->type?->business_model, 'update'))
                        <a href="{{ route('admin.catalog-items.edit', ['catalogItem' => $catalogItem, ...$returnContext]) }}"
                            class="w-full rounded-lg bg-gray-900 px-5 py-2.5 text-center text-sm font-medium text-white transition hover:bg-gray-700">
                            Editar
                        </a>
                        @endif
                        @if(\App\Support\Permissions\CatalogPermission::allows($catalogItem->type?->business_model, 'delete'))
                        <form action="{{ route('admin.catalog-items.destroy', $catalogItem) }}" method="POST"
                            onsubmit="return confirm('¿Eliminar este producto o servicio?')">
                            @csrf
                            @method('DELETE')
                            <button
                                class="w-full rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-100">
                                Eliminar
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </aside>

            <section class="catalog-business-list-shell rounded-xl bg-white shadow-sm overflow-hidden flex flex-col">
                <div
                    class="catalog-business-list-header flex shrink-0 flex-col gap-3 p-4 sm:p-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="catalog-business-search min-w-0 flex-1">
                        <label for="{{ $configSearchId }}">Buscar {{ $configSingular }}</label>
                        <div class="catalog-business-search-row">
                            <div class="min-w-0 flex-1">
                                <select id="{{ $configSearchId }}" class="select2 catalog-item-detail-search"
                                    data-placeholder="Buscar {{ $configSingular }}">
                                    <option value="">Todas las {{ $configPlural }}</option>
                                    @foreach ($configItems as $configItem)
                                        @php
                                            $optionLabel = $isServiceContext
                                                ? $configItem->vehicle_label
                                                : $configItem->name;
                                        @endphp
                                        <option value="{{ $configItem->id }}">{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    @if(\App\Support\Permissions\CatalogPermission::allows($catalogItem->type?->business_model, 'update'))
                    <a href="{{ $addConfigUrl }}"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-center text-sm font-medium text-white transition hover:bg-gray-700">
                        + {{ $isServiceContext ? 'Precio por vehiculo' : 'Nueva Presentacion' }}
                    </a>
                    @endif
                </div>

                @if ($configItems->isEmpty())
                    <div class="catalog-business-empty text-center">
                        <p class="mb-2 font-medium text-gray-700">
                            Este {{ $itemLabel }} aun no tiene {{ $configPlural }}.
                        </p>
                        <p class="mb-5 text-sm">
                            Agrega el primero para completar su informacion.
                        </p>
                        @if(\App\Support\Permissions\CatalogPermission::allows($catalogItem->type?->business_model, 'update'))
                        <a href="{{ $addConfigUrl }}"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-center text-sm font-medium text-white transition hover:bg-gray-700">
                            + {{ $isServiceContext ? 'Precio por vehiculo' : 'Nueva Presentacion' }}
                        </a>
                        @endif
                    </div>
                @else
                    <div class="catalog-business-items-scroll">
                        <div class="catalog-business-card-grid">
                            @foreach ($configItems as $configItem)
                                @if ($isServiceContext)
                                    <article class="catalog-business-item-card catalog-detail-config-card"
                                        data-config-id="{{ $configItem->id }}">
                                        <div class="catalog-business-item-main">
                                            <div class="min-w-0">
                                                <h4>{{ $configItem->vehicle_label }}</h4>
                                                <p>{{ $configItem->description ?: 'Sin descripcion' }}</p>
                                            </div>
                                            <div class="catalog-business-item-actions">
                                                @if(\App\Support\Permissions\CatalogPermission::allows($catalogItem->type?->business_model, 'update'))
                                                <a href="{{ route('admin.catalog-service-prices.edit', $configItem) }}"
                                                    title="Editar precio" aria-label="Editar precio">
                                                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                                                </a>
                                                @endif
                                                @if(\App\Support\Permissions\CatalogPermission::allows($catalogItem->type?->business_model, 'update'))
                                                <form method="POST"
                                                    action="{{ route('admin.catalog-service-prices.destroy', $configItem) }}"
                                                    onsubmit="return confirm('¿Eliminar este precio por vehiculo?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Eliminar precio" aria-label="Eliminar precio">
                                                        <x-heroicon-o-trash class="h-4 w-4" />
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="catalog-business-item-meta">
                                            <span class="catalog-business-chip">
                                                {{ $configItem->duration_minutes ? $configItem->duration_minutes . ' min' : 'Sin duracion' }}
                                            </span>
                                            <strong>${{ number_format((float) $configItem->price, 2) }}</strong>
                                        </div>

                                        <div class="catalog-business-item-footer">
                                            <span>Insumos <strong>{{ $configItem->supplies->count() }}</strong></span>
                                            @if ($configItem->active)
                                                <span class="catalog-business-status is-active">Activo</span>
                                            @else
                                                <span class="catalog-business-status">Oculto</span>
                                            @endif
                                        </div>
                                    </article>
                                @else
                                    <article class="catalog-business-item-card catalog-detail-config-card"
                                        data-config-id="{{ $configItem->id }}">
                                        <div class="catalog-business-item-main">
                                            <div class="min-w-0">
                                                <h4>{{ $configItem->name }}</h4>
                                                <p>{{ $configItem->sku ?: 'Sin codigo interno' }}</p>
                                            </div>
                                            <div class="catalog-business-item-actions">
                                                @if(\App\Support\Permissions\CatalogPermission::allows($catalogItem->type?->business_model, 'update'))
                                                <a href="{{ route('admin.catalog-variants.edit', ['catalogVariant' => $configItem, 'redirect_to_item' => 1]) }}"
                                                    title="Editar presentacion" aria-label="Editar presentacion">
                                                    <x-heroicon-o-pencil-square class="h-4 w-4" />
                                                </a>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="catalog-business-item-meta">
                                            <span class="catalog-business-chip">
                                                Stock {{ $configItem->stock ?? 0 }}
                                            </span>
                                            <strong>
                                                {{ $configItem->price !== null ? '$' . number_format((float) $configItem->price, 2) : '-' }}
                                            </strong>
                                        </div>

                                        <div class="catalog-business-item-footer">
                                            <span>{{ $configItem->is_default ? 'Principal' : 'Presentacion' }}</span>
                                            @if ($configItem->active)
                                                <span class="catalog-business-status is-active">Activa</span>
                                            @else
                                                <span class="catalog-business-status">Oculta</span>
                                            @endif
                                        </div>
                                    </article>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const selectId = @json($configSearchId);

                function initCatalogItemDetailSearch() {
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
                        .off('change.catalogItemDetail')
                        .on('change.catalogItemDetail', function () {
                            filterCatalogItemConfig(this.value || '');
                        });
                }

                function filterCatalogItemConfig(selectedId) {
                    document.querySelectorAll('.catalog-detail-config-card').forEach((card) => {
                        card.hidden = Boolean(selectedId) && card.dataset.configId !== String(selectedId);
                    });
                }

                document.addEventListener('DOMContentLoaded', initCatalogItemDetailSearch);
                document.addEventListener('livewire:navigated', initCatalogItemDetailSearch);
                initCatalogItemDetailSearch();
            })();
        </script>
    @endpush
@endsection
