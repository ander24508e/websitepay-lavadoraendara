@extends('layouts.admin')

@section('title', 'Detalle de Sección')

@push('styles')
    @vite($catalogType->business_model === \App\Models\CatalogType::BUSINESS_MODEL_PRODUCTS
        ? 'resources/scss/Catalogo/catalogo-products.scss'
        : 'resources/scss/Catalogo/catalogo-services.scss')
@endpush

@section('content')
    @php
        $isProductBusiness =
            ($catalogType->business_model ?? \App\Models\CatalogType::BUSINESS_MODEL_SERVICES) ===
            \App\Models\CatalogType::BUSINESS_MODEL_PRODUCTS;
        $businessModelLabel = $isProductBusiness ? 'Productos' : 'Servicios';
        $itemPluralTitle = $isProductBusiness ? 'Productos' : 'Servicios';
    @endphp

    <div class="catalog-business-show mx-auto w-full max-w-6xl overflow-x-hidden px-3 pb-4 sm:px-6">
        <div class="flex items-start gap-3 mb-4">
            <a href="{{ route('admin.catalog.index') }}"
                class="flex items-center justify-center w-9 h-9 bg-white rounded-lg shadow-sm hover:bg-gray-50 transition text-gray-500 hover:text-gray-800 shrink-0">
                <span aria-hidden="true">&larr;</span>
            </a>
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">{{ $catalogType->name }}</h2>
                <p class="text-gray-400 text-sm">Configura esta sección y su contenido en la web.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[320px_minmax(0,1fr)] gap-4">
            <aside class="min-w-0">
                <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
                    <div class="flex flex-wrap gap-3 mb-5">
                        <h3 class="text-lg font-bold text-gray-800">Información del Negocio</h3>
                    </div>

                    <div class="space-y-4 mb-6 min-w-0">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Nombre</p>
                            <p class="font-semibold text-gray-800 break-words">{{ $catalogType->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Slug</p>
                            <p class="text-gray-700 font-mono text-sm break-all">{{ $catalogType->slug ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Descripción</p>
                            <p class="text-center text-gray-700 break-words">{{ $catalogType->description ?: 'Sin descripción.' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Modelo del negocio</p>
                            <span
                                class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">{{ $businessModelLabel }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">{{ $itemPluralTitle }}</p>
                            <p class="text-gray-700 font-semibold">{{ $catalogType->items_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Estado</p>
                            @if ($catalogType->active)
                                <span
                                    class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-medium inline-flex">Activo</span>
                            @else
                                <span
                                    class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-medium inline-flex">Oculto</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 pt-4 border-t border-gray-100 sm:flex-row">
                        @if(\App\Support\Permissions\CatalogPermission::allows($catalogType->business_model, 'update'))
                        <a href="{{ route('admin.catalog-types.edit', $catalogType) }}"
                            class="w-full bg-gray-900 text-white px-5 py-2.5 rounded-lg hover:bg-gray-700 transition font-medium text-sm text-center sm:w-auto">
                            Editar
                        </a>
                        @endif
                        @if(\App\Support\Permissions\CatalogPermission::allows($catalogType->business_model, 'delete'))
                        <form action="{{ route('admin.catalog-types.destroy', $catalogType) }}" method="POST"
                            onsubmit="return confirm('¿Eliminar esta sección?')">
                            @csrf
                            @method('DELETE')
                            <button
                                class="w-full bg-red-50 text-red-600 px-5 py-2.5 rounded-lg hover:bg-red-100 transition font-medium text-sm border border-red-200 sm:w-auto">
                                Eliminar
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </aside>

            <section class="min-w-0 flex flex-col gap-4">
                @livewire('admin.catalog.type-item-browser', ['catalogType' => $catalogType])
            </section>
        </div>
    </div>
@endsection
