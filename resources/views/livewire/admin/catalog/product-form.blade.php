<div class="mx-auto w-full max-w-[1500px] px-3 pb-4 sm:px-5 xl:h-[calc(100vh-2rem)] xl:overflow-hidden">
    @php
        $isEditing = (bool) $catalogItemId;

        $inputClass = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300';

        $labelClass = 'mb-1 block text-xs font-semibold text-gray-700';
    @endphp

    @include('partials.admin-notifications')

    {{-- Encabezado --}}
    <div class="mb-3 flex items-center gap-3">
        <a
            href="{{ $returnUrl }}"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-gray-800"
            aria-label="Volver"
        >
            <span aria-hidden="true">&larr;</span>
        </a>

        <div class="min-w-0">
            <h2 class="text-xl font-bold leading-tight text-gray-900 sm:text-2xl">
                {{ $isEditing ? 'Editar Producto' : 'Nuevo Producto' }}
            </h2>

            <p class="text-sm text-gray-400">
                {{ $isEditing
                    ? 'Actualiza la información general del producto.'
                    : 'Crea la información general del producto.' }}
            </p>
        </div>
    </div>

    @if ($types->isEmpty())
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
            Inventario necesita un negocio configurado como productos antes de registrar productos.

            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                <a
                    href="{{ route('admin.catalog-types.create', ['business_model' => 'products']) }}"
                    class="inline-flex justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700"
                >
                    Crear negocio de productos
                </a>

                <a
                    href="{{ $returnUrl }}"
                    class="inline-flex justify-center rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-200"
                >
                    Volver
                </a>
            </div>
        </div>
    @else
        {{-- Formulario principal --}}
        <form
            wire:submit.prevent="save"
            class="rounded-lg bg-white shadow-sm xl:flex xl:h-[calc(100%-4.25rem)] xl:flex-col xl:overflow-hidden"
        >
            <div class="grid gap-4 p-4 xl:grid-cols-[minmax(0,1fr)_300px] xl:overflow-y-auto">

                {{-- Columna principal --}}
                <div class="min-w-0 space-y-4">

                    {{-- Imagen --}}
                    <section class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Imagen
                        </p>

                        <div class="grid gap-3 sm:grid-cols-[150px_minmax(0,1fr)] sm:items-center">
                            <div
                                class="mx-auto flex h-28 w-28 items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-gray-200 bg-white text-center text-xs text-gray-400 sm:h-32 sm:w-32"
                            >
                                @if ($image)
                                    <img
                                        src="{{ $image->temporaryUrl() }}"
                                        class="h-full w-full object-cover"
                                        alt="Vista previa del producto"
                                    >
                                @elseif ($currentImage)
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::url($currentImage) }}"
                                        class="h-full w-full object-cover"
                                        alt="Imagen actual del producto"
                                    >
                                @else
                                    Sin imagen
                                @endif
                            </div>

                            <div class="min-w-0">
                                <p class="mb-2 truncate text-xs text-gray-400">
                                    <span wire:loading.remove wire:target="image">
                                        {{ $image
                                            ? $image->getClientOriginalName()
                                            : (basename((string) $currentImage) ?: 'Sin imagen seleccionada') }}
                                    </span>

                                    <span wire:loading wire:target="image">
                                        Cargando imagen...
                                    </span>
                                </p>

                                <label
                                    class="inline-flex w-full cursor-pointer justify-center rounded-lg bg-gray-900 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 sm:max-w-xs"
                                >
                                    {{ $currentImage || $image ? 'Cambiar imagen' : 'Subir imagen' }}

                                    <input
                                        type="file"
                                        wire:model="image"
                                        accept="image/*"
                                        class="hidden"
                                    >
                                </label>

                                <p class="mt-2 text-xs text-gray-400">
                                    JPG, PNG o WEBP - Máx. 6 MB
                                </p>

                                @error('image')
                                    <p class="mt-1 text-xs text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    {{-- Información básica --}}
                    <section class="space-y-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                Información básica
                            </p>

                            <p class="text-xs text-gray-500">
                                Nombre, categoría y descripción del producto.
                            </p>
                        </div>

                        {{-- Nombre --}}
                        <div>
                            <label for="catalogItemName" class="{{ $labelClass }}">
                                Nombre *
                            </label>

                            <input
                                id="catalogItemName"
                                type="text"
                                wire:model="name"
                                class="{{ $inputClass }} @error('name') border-red-400 bg-red-50 @enderror"
                                placeholder="Nombre del producto"
                            >

                            @error('name')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Negocio y categoría --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            {{-- Negocio --}}
                            <div class="flex min-w-0 flex-col">
                                <div class="mb-1 flex min-h-8 items-center">
                                    <label
                                        for="selectedTypeId"
                                        class="block text-xs font-semibold text-gray-700"
                                    >
                                        Negocio *
                                    </label>
                                </div>

                                <select
                                    id="selectedTypeId"
                                    wire:model.live="selectedTypeId"
                                    class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-gray-300 disabled:cursor-not-allowed disabled:opacity-70 @error('selectedTypeId') border-red-400 bg-red-50 @enderror"
                                    @disabled($isEditing)
                                >
                                    @unless ($selectedTypeId)
                                        <option value="0">Selecciona un negocio</option>
                                    @endunless

                                    @foreach ($types as $type)
                                        <option value="{{ $type->id }}">
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('selectedTypeId')
                                    <p class="mt-1 text-xs text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- Categoría --}}
                            <div class="flex min-w-0 flex-col">
                                <div class="mb-1 flex min-h-8 items-center justify-between gap-2">
                                    <label
                                        for="selectedCategoryId"
                                        class="block text-xs font-semibold text-gray-700"
                                    >
                                        Categoría
                                    </label>

                                    <button
                                        type="button"
                                        wire:click="openCategoryModal"
                                        class="inline-flex h-8 shrink-0 items-center justify-center gap-1 whitespace-nowrap rounded-lg bg-gray-100 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                                        @disabled(! $selectedTypeId)
                                    >
                                        <x-heroicon-o-plus class="h-4 w-4" />

                                        Nueva categoría
                                    </button>
                                </div>

                                @if ($newCategoryName)
                                    <input
                                        type="text"
                                        value="{{ $newCategoryName }}"
                                        class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-gray-300"
                                        readonly
                                    >
                                @else
                                    <select
                                        id="selectedCategoryId"
                                        wire:key="category-select-{{ $selectedTypeId }}"
                                        wire:model="selectedCategoryId"
                                        wire:change="clearInlineCategory"
                                        class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 outline-none transition focus:ring-2 focus:ring-gray-300 @error('selectedCategoryId') border-red-400 bg-red-50 @enderror"
                                    >
                                        <option value="">
                                            Sin categoría
                                        </option>

                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif

                                @error('selectedCategoryId')
                                    <p class="mt-1 text-xs text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror

                                @error('newCategoryName')
                                    <p class="mt-1 text-xs text-red-500">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                        </div>

                        {{-- Descripción --}}
                        <div>
                            <label for="catalogItemDescription" class="{{ $labelClass }}">
                                Descripción
                            </label>

                            <textarea
                                id="catalogItemDescription"
                                wire:model="description"
                                rows="3"
                                class="{{ $inputClass }} resize-none @error('description') border-red-400 bg-red-50 @enderror"
                                placeholder="Describe este producto o su uso dentro del negocio"
                            ></textarea>

                            @error('description')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </section>
                </div>

                {{-- Columna lateral --}}
                <aside class="space-y-4">

                    {{-- Presentaciones --}}
                    <section class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Presentaciones
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Agrega las formas en que vendes este producto, por ejemplo: litro, galón, caneca o unidad.
                        </p>

                        @if ($catalogItemId && $presentationUrl)
                            <a
                                href="{{ $presentationUrl }}"
                                wire:key="presentation-action-{{ $highlightKey }}"
                                class="{{ $highlightPresentation ? 'catalog-presentation-highlight' : '' }} mt-4 inline-flex h-10 w-full items-center justify-center rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-700"
                            >
                                Agregar presentación
                            </a>

                            <p class="mt-2 text-center text-xs text-gray-500">
                                {{ $variantCount }}
                                {{ $variantCount === 1 ? 'opción agregada' : 'opciones agregadas' }}
                            </p>

                            @if ($presentationIndexUrl)
                                <a
                                    href="{{ $presentationIndexUrl }}"
                                    class="mt-2 inline-flex h-9 w-full items-center justify-center rounded-lg bg-gray-100 px-4 text-xs font-semibold text-gray-700 transition hover:bg-gray-200"
                                >
                                    Ver presentaciones
                                </a>
                            @endif
                        @else
                            <p class="mt-4 rounded-lg border border-gray-100 bg-white px-3 py-3 text-xs text-gray-500">
                                Después de guardar el producto, este botón se activará para continuar.
                            </p>
                        @endif
                    </section>

                    {{-- Estado --}}
                    <section class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                            Estado
                        </p>

                        <label
                            class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-100 bg-white p-3"
                        >
                            <input
                                type="checkbox"
                                wire:model="active"
                                class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-400"
                            >

                            <span>
                                <span class="block text-sm font-semibold text-gray-700">
                                    Activo
                                </span>

                                <span class="block text-xs text-gray-400">
                                    Visible en catálogo
                                </span>
                            </span>
                        </label>
                    </section>
                </aside>
            </div>

            {{-- Acciones del formulario --}}
            <div class="flex flex-col gap-2 border-t border-gray-100 p-4 sm:flex-row sm:justify-end">
                <a
                    href="{{ $returnUrl }}"
                    class="inline-flex h-10 items-center justify-center rounded-lg bg-gray-100 px-5 text-sm font-semibold text-gray-600 transition hover:bg-gray-200"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="inline-flex h-10 items-center justify-center rounded-lg bg-gray-900 px-5 text-sm font-semibold text-white transition hover:bg-gray-700 disabled:cursor-wait disabled:opacity-70"
                    wire:loading.attr="disabled"
                    wire:target="save,image"
                >
                    <span wire:loading.remove wire:target="save">
                        {{ $isEditing ? 'Actualizar producto' : 'Guardar producto' }}
                    </span>

                    <span wire:loading wire:target="save">
                        Guardando...
                    </span>
                </button>
            </div>
        </form>

        {{-- Modal para crear categoría --}}
        @if ($showCategoryModal)
            <div
                class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto px-4 py-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="categoryModalTitle"
            >
                {{-- Fondo del modal --}}
                <button
                    type="button"
                    class="fixed inset-0 cursor-default bg-gray-900/50 backdrop-blur-[1px]"
                    wire:click="closeCategoryModal"
                    aria-label="Cerrar modal"
                ></button>

                {{-- Contenido del modal --}}
                <div class="relative z-10 w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">

                    {{-- Encabezado --}}
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                        <div>
                            <h3
                                id="categoryModalTitle"
                                class="font-bold text-gray-900"
                            >
                                Nueva categoría
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Se creará dentro del negocio actual.
                            </p>
                        </div>

                        <button
                            type="button"
                            wire:click="closeCategoryModal"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                            aria-label="Cerrar"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    {{-- Campos --}}
                    <div class="space-y-4 p-5">
                        <div>
                            <label
                                for="newCategoryName"
                                class="{{ $labelClass }}"
                            >
                                Nombre *
                            </label>

                            <input
                                id="newCategoryName"
                                type="text"
                                wire:model="newCategoryName"
                                class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm outline-none transition focus:ring-2 focus:ring-gray-300 @error('newCategoryName') border-red-400 bg-red-50 @enderror"
                                placeholder="Ej.: Interior del vehículo"
                                autofocus
                            >

                            @error('newCategoryName')
                                <p class="mt-1 text-xs text-red-500">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label
                                for="newCategoryDescription"
                                class="{{ $labelClass }}"
                            >
                                Descripción
                            </label>

                            <textarea
                                id="newCategoryDescription"
                                wire:model="newCategoryDescription"
                                rows="3"
                                class="w-full resize-none rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm outline-none transition focus:ring-2 focus:ring-gray-300"
                                placeholder="Opcional"
                            ></textarea>
                        </div>
                    </div>

                    {{-- Acciones del modal --}}
                    <div class="flex flex-col-reverse gap-2 border-t border-gray-100 p-4 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            wire:click="closeCategoryModal"
                            class="inline-flex h-10 items-center justify-center rounded-lg bg-gray-100 px-5 text-sm font-semibold text-gray-600 transition hover:bg-gray-200"
                        >
                            Cancelar
                        </button>

                        <button
                            type="button"
                            wire:click="applyCategory"
                            class="inline-flex h-10 items-center justify-center rounded-lg bg-gray-900 px-5 text-sm font-semibold text-white transition hover:bg-gray-700 disabled:cursor-wait disabled:opacity-70"
                            wire:loading.attr="disabled"
                            wire:target="applyCategory"
                        >
                            <span wire:loading.remove wire:target="applyCategory">
                                Usar categoría
                            </span>

                            <span wire:loading wire:target="applyCategory">
                                Guardando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
