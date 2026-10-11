<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 sm:p-10 text-center max-w-3xl mx-auto">
    <div class="w-14 h-14 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center mx-auto mb-4">
        <x-heroicon-o-building-storefront class="w-7 h-7" />
    </div>
    <h3 class="text-xl font-bold text-gray-800">Inventario necesita un negocio de productos</h3>
    <p class="text-sm text-gray-500 mt-2 max-w-xl mx-auto">
        Para controlar stock, primero crea un negocio configurado como productos. Los servicios no aparecen aquí
        porque no tienen existencias físicas.
    </p>
    <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3">
        @can('catalog.products.create')
        <a href="{{ route('admin.catalog-types.create', ['business_model' => 'products']) }}"
            class="inline-flex items-center justify-center gap-2 bg-gray-900 text-white px-5 py-2.5 rounded-lg hover:bg-gray-700 transition text-sm font-semibold">
            <x-heroicon-o-plus class="w-5 h-5" />
            Crear negocio de productos
        </a>
        @endcan
        <a href="{{ route('admin.catalog.index') }}"
            class="inline-flex items-center justify-center gap-2 bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-200 transition text-sm font-semibold">
            <x-heroicon-o-squares-2x2 class="w-5 h-5" />
            Ir a catálogo
        </a>
    </div>
</div>
