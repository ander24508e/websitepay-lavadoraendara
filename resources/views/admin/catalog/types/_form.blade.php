<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_380px] gap-6 xl:gap-8">
    <div class="space-y-6 min-w-0">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
            <input type="text" name="name" id="catalog_type_name" value="{{ old('name', $catalogType?->name) }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-gray-400 @error('name') border-red-400 bg-red-50 @enderror"
                placeholder="Ej: Servicios, Productos, Comida, Bebidas">
            @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Modelo del negocio *</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @php($typeAction = $catalogType ? 'update' : 'create')
                @if(\App\Support\Permissions\CatalogPermission::allows('services', $typeAction))
                <label class="flex gap-3 rounded-xl border border-gray-200 p-4 cursor-pointer hover:border-gray-400 transition">
                    <input type="radio" name="business_model" value="services"
                        class="mt-1 text-gray-900 focus:ring-gray-400"
                        {{ $selectedBusinessModel === 'services' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">Servicios</span>
                        <span class="block text-xs text-gray-500 mt-1">Para reservas, trabajos o atencion sin stock directo.</span>
                    </span>
                </label>
                @endif
                @if(\App\Support\Permissions\CatalogPermission::allows('products', $typeAction))
                <label class="flex gap-3 rounded-xl border border-gray-200 p-4 cursor-pointer hover:border-gray-400 transition">
                    <input type="radio" name="business_model" value="products"
                        class="mt-1 text-gray-900 focus:ring-gray-400"
                        {{ $selectedBusinessModel === 'products' ? 'checked' : '' }}>
                    <span>
                        <span class="block text-sm font-semibold text-gray-800">Productos</span>
                        <span class="block text-xs text-gray-500 mt-1">Para articulos fisicos que podran manejar stock e inventario.</span>
                    </span>
                </label>
                @endif
            </div>
            @error('business_model')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion</label>
            <textarea name="description" rows="5"
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-gray-400 @error('description') border-red-400 bg-red-50 @enderror"
                placeholder="Explica que tipo de items agrupara esta seccion">{{ old('description', $catalogType?->description) }}</textarea>
            @error('description')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <aside class="rounded-xl border border-gray-200 bg-gray-50 p-5 sm:p-6 h-fit space-y-5">
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Informacion</p>
            <p class="text-sm text-gray-500 mt-1">Datos internos generados por el sistema.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
            <input type="text" name="slug" id="catalog_type_slug" value="{{ old('slug', $catalogType?->slug) }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white text-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-400 @error('slug') border-red-400 bg-red-50 @enderror"
                placeholder="Se genera automaticamente" readonly>
            @error('slug')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-700">
            <input type="checkbox" name="active" value="1"
                {{ old('active', $catalogType?->active ?? true) ? 'checked' : '' }}
                class="rounded border-gray-300 text-gray-900 focus:ring-gray-400">
            <span>
                <span class="block font-semibold text-gray-800">Activo</span>
                <span class="block text-xs text-gray-400 mt-0.5">Visible dentro del catalogo.</span>
            </span>
        </label>
    </aside>
</div>
