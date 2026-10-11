@extends('layouts.admin')

@section('title', 'Nueva Venta Sistema')

@push('styles')
    @vite('resources/scss/admin/ventas-create.scss')
@endpush

@section('content')
    @php
        $catalogPayload = $catalogItems
            ->map(
                fn($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type?->name ?? 'Catalogo',
                    'business_model' =>
                        $item->type?->business_model ?? \App\Models\CatalogType::BUSINESS_MODEL_SERVICES,
                    'price' => (float) $item->display_price,
                    'uses_inventory' => (bool) $item->uses_inventory,
                    'variants' => $item->activeVariants
                        ->map(
                            fn($variant) => [
                                'id' => $variant->id,
                                'label' => trim(
                                    ($variant->name ?: 'Presentacion') . ($variant->sku ? ' - ' . $variant->sku : ''),
                                ),
                                'price' => (float) ($variant->price ?? 0),
                                'stock' => (int) ($variant->stock ?? 0),
                                'is_default' => (bool) $variant->is_default,
                            ],
                        )
                        ->values(),
                    'vehicle_prices' => $item->vehicleTypePrices
                        ->where('active', true)
                        ->mapWithKeys(function ($vehiclePrice) {
                            $key = $vehiclePrice->vehicle_specification_id
                                ? 'specification:' . $vehiclePrice->vehicle_specification_id
                                : 'type:' . $vehiclePrice->vehicle_type_id;

                            return [$key => (float) $vehiclePrice->price];
                        }),
                ],
            )
            ->values();
    @endphp

    <div
        class="mx-auto flex w-full max-w-none flex-col gap-4 lg:h-[calc(100dvh-3rem)] lg:overflow-hidden">
        <div class="flex shrink-0 items-center gap-3">
            <a href="{{ route('admin.ventas.index') }}"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-gray-800"
                title="Volver" aria-label="Volver">
                <x-heroicon-o-arrow-left class="h-5 w-5" />
            </a>

            <h2 class="text-2xl font-bold text-gray-800">Agregar Venta</h2>
        </div>

        <form method="POST" action="{{ route('admin.ventas.store') }}" id="saleForm"
            class="sales-create-layout grid min-h-0 flex-1 grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(380px,0.85fr)] lg:overflow-hidden">
            @csrf

            <div class="sales-create-primary grid min-h-0 gap-4 lg:grid-rows-[minmax(0,1fr)_auto]">
                <section
                    class="sales-create-data min-h-0 space-y-5 rounded-xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5 lg:overflow-y-auto">
                    <div>
                        <h3 class="font-bold text-gray-800">
                            Datos de la venta
                        </h3>

                        <p class="mt-1 text-xs text-gray-400">
                            Selecciona el cliente y el trabajador responsable. Cada ítem define su propio tipo.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        {{-- Cliente --}}
                        <div class="flex min-w-0 flex-col">
                            <div class="mb-1 flex min-h-8 items-center justify-between gap-2">
                                <label for="clientSelect" class="block text-sm font-medium text-gray-700">
                                    Cliente
                                </label>

                                <button type="button" id="openQuickClientModal"
                                    class="@cannot('clients.create') !hidden @endcannot inline-flex h-8 items-center justify-center gap-1 rounded-lg bg-gray-900 px-3 text-xs font-semibold text-white transition hover:bg-gray-700">
                                    <x-heroicon-o-plus class="h-4 w-4" />
                                    Nuevo cliente
                                </button>
                            </div>

                            <select name="user_id" id="clientSelect"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                                <option value="">
                                    Invitado
                                </option>

                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected(old('user_id') == $cliente->id)>
                                        {{ $cliente->name }} ({{ $cliente->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Trabajador --}}
                        <div class="flex min-w-0 flex-col">
                            <div class="mb-1 flex min-h-8 items-center">
                                <label for="attendedBySelect" class="block text-sm font-medium text-gray-700">
                                    Trabajador
                                </label>
                            </div>

                            <select name="attended_by" id="attendedBySelect"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                                <option value="">
                                    Usuario actual
                                </option>

                                @foreach ($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" @selected(old('attended_by', auth()->id()) == $usuario->id)>
                                        {{ $usuario->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="scheduled_at"
                            value="{{ old('scheduled_at', now()->addHour()->format('Y-m-d\TH:i')) }}">

                        {{-- Notas --}}
                        <div class="md:col-span-2">
                            <div class="mb-1 flex min-h-8 items-center">
                                <label for="saleNotes" class="block text-sm font-medium text-gray-700">
                                    Notas
                                </label>
                            </div>

                            <textarea name="notes" id="saleNotes" rows="3" placeholder="Observaciones internas de la venta"
                                class="min-h-24 w-full resize-y rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200">{{ old('notes') }}</textarea>
                        </div>

                    </div>

                </section>

                <section
                    class="sales-create-summary space-y-4 rounded-xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
                    <h3 class="font-bold text-gray-800">
                        Resumen
                    </h3>

                    <div class="grid grid-cols-1 gap-x-8 gap-y-2 sm:grid-cols-2">
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <strong id="subtotalText">$0.00</strong>
                        </div>
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-gray-500">Descuentos</span>
                            <strong id="discountText">$0.00</strong>
                        </div>
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="text-gray-500">Impuestos</span>
                            <strong id="taxText">$0.00</strong>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-2">
                            <span class="font-semibold text-gray-800">Total</span>
                            <strong id="totalText" class="text-xl text-gray-900">$0.00</strong>
                        </div>
                    </div>

                    <button
                        class="w-full rounded-lg bg-gray-900 px-5 py-3 text-sm font-semibold text-white hover:bg-gray-700">
                        Guardar venta pendiente
                    </button>
                </section>
            </div>

            <section
                class="sales-create-items flex min-h-[420px] min-w-0 flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm lg:min-h-0">
                <div
                    class="flex shrink-0 flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                    <div>
                        <h3 class="font-bold text-gray-800">
                            Ítems de venta
                        </h3>

                        <p class="text-xs text-gray-400">
                            Agrega productos o servicios del catálogo.
                        </p>
                    </div>

                    <button type="button" id="addSaleItem"
                        class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 text-sm font-semibold text-white transition hover:bg-gray-700">
                        <x-heroicon-o-plus class="h-4 w-4" />
                        Agregar ítem
                    </button>
                </div>

                <div class="min-h-0 flex-1 p-3 sm:p-4">
                    <div id="saleItems"
                        class="h-full space-y-3 overflow-y-auto overscroll-contain pr-1 [scrollbar-color:#cbd5e1_transparent] [scrollbar-width:thin]">
                    </div>

                    @error('items')
                        <p class="mt-3 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </section>
        </form>

        <div id="quickClientModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 px-4">
            <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Nuevo Cliente</h3>
                        <p class="text-sm text-gray-400">Crea el cliente sin salir de la venta.</p>
                    </div>
                    <button type="button" id="closeQuickClientModal"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <form id="quickClientForm" class="space-y-4 px-6 py-5">
                    @csrf
                    <div id="quickClientErrors"
                        class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                            <input type="text" name="name"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                            <input type="email" name="email"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input type="text" name="telefono"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                            <input type="text" name="direccion"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                        <input type="password" name="password"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" id="cancelQuickClient"
                            class="rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200">Cancelar</button>
                        <button type="submit" id="saveQuickClient"
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">Guardar
                            Cliente</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="quickVehicleModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-3 sm:p-4">
            <div class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-xl sm:max-h-[calc(100dvh-2rem)]">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Nuevo Vehiculo</h3>
                        <p class="text-sm text-gray-400">Crea un vehiculo para el cliente seleccionado sin salir de la
                            venta.</p>
                    </div>
                    <button type="button" id="closeQuickVehicleModal"
                        class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>

                <form id="quickVehicleForm" class="space-y-4 overflow-y-auto px-4 py-5 sm:px-6">
                    @csrf
                    <input type="hidden" name="user_id" id="quickVehicleUserId">
                    <input type="hidden" name="specification_mode" id="quickVehicleSpecificationMode" value="existing">
                    <div id="quickVehicleErrors"
                        class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div id="quickVehicleExistingSpecification" class="md:col-span-2">
                            <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                                <label class="block text-sm font-medium text-gray-700">Especificación de vehículo existente *</label>
                                <button type="button" id="showQuickVehicleNewSpecification"
                                    class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-700">
                                    + Crear nueva especificación
                                </button>
                            </div>
                            <select name="vehicle_specification_id" id="quickVehicleSpecificationSelect"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                                <option value="">Busca marca / modelo / tipo</option>
                                @foreach ($vehicleSpecifications as $specification)
                                    <option value="{{ $specification->id }}">{{ $specification->label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="quickVehicleNewSpecification" class="hidden md:col-span-2">
                            <div class="mb-3 flex flex-wrap items-start justify-between gap-2 border-t border-gray-100 pt-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Crear nueva especificación</p>
                                    <p class="mt-1 text-xs text-gray-500">Úsala únicamente cuando no encuentres la combinación de marca, modelo y tipo.</p>
                                </div>
                                <button type="button" id="showQuickVehicleExistingSpecification"
                                    class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200">
                                    Usar especificación existente
                                </button>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Crear marca nueva *</label>
                                    <input type="text" name="new_vehicle_brand_name" disabled
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                        placeholder="Ej: Toyota, Kia">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Crear modelo nuevo *</label>
                                    <input type="text" name="new_vehicle_model_name" disabled
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                        placeholder="Ej: Corolla, Picanto">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700">Crear tipo nuevo *</label>
                                    <input type="text" name="new_vehicle_type_name" disabled
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                        placeholder="Ej: Auto pequeño, SUV">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Placa </label>
                            <input type="text" name="plate"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase"
                                placeholder="Ej: ABC-1234" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                            <input type="text" name="color"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="Ej: Blanco, negro, rojo">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                            <input type="number" name="year" min="1900" max="{{ now()->year + 1 }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="{{ now()->year }}">
                        </div>

                        <label class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" value="1"
                                class="rounded border-gray-300 text-gray-900" checked>
                            <span>
                                <span class="block text-sm font-medium text-gray-700">Vehiculo activo</span>
                                <span class="block text-xs text-gray-400">Disponible para ventas y servicios.</span>
                            </span>
                        </label>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                            <textarea name="observations" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                placeholder="Notas internas o informacion relevante del vehiculo"></textarea>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 border-t border-gray-100 pt-4">
                        <button type="button" id="cancelQuickVehicle"
                            class="rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-200">Cancelar</button>
                        <button type="submit" id="saveQuickVehicle"
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">Guardar
                            Vehiculo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <template id="saleItemTemplate">
        <div class="sale-item-row rounded-xl border border-gray-100 bg-gray-50 p-3 sm:p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-6 sm:items-end">

                {{-- Tipo de venta --}}
                <div class="min-w-0 sm:col-span-3">
                    <label class="mb-1 flex min-h-8 items-end text-[11px] font-semibold uppercase text-gray-500">
                        Tipo de venta
                    </label>

                    <select class="row-kind-select h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                        required>
                        <option value="">Selecciona un tipo</option>
                        <option value="{{ \App\Models\CatalogType::BUSINESS_MODEL_PRODUCTS }}">Productos</option>
                        <option value="{{ \App\Models\CatalogType::BUSINESS_MODEL_SERVICES }}">Servicios</option>
                    </select>
                </div>

                {{-- Producto o servicio --}}
                <div class="item-catalog-field min-w-0 sm:col-span-3">
                    <label class="item-catalog-label mb-1 flex min-h-8 items-end text-[11px] font-semibold uppercase text-gray-500">
                        Producto/Servicio
                    </label>

                    <select data-field="catalog_item_id"
                        class="catalog-select h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                        required>
                        <option value="">Primero selecciona el tipo</option>
                    </select>
                </div>

                <input type="hidden" data-field="catalog_item_variant_id" class="variant-input" disabled>

                {{-- Vehículo --}}
                <div class="item-service-field hidden min-w-0 sm:col-span-3">
                    <div class="mb-1 flex min-h-8 items-center justify-between gap-2">
                        <label class="text-[11px] font-semibold uppercase text-gray-500">Vehículo</label>
                        <button type="button"
                            class="@cannot('vehicles.create') !hidden @endcannot open-quick-vehicle-modal inline-flex h-7 items-center justify-center gap-1 rounded-lg bg-gray-900 px-2 text-[11px] font-semibold text-white transition hover:bg-gray-700">
                            <x-heroicon-o-plus class="h-3.5 w-3.5" />
                            Nuevo
                        </button>
                    </div>

                    <select data-field="vehicle_id"
                        class="vehicle-select h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                        <option value="">
                            Sin vehículo
                        </option>

                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" data-client="{{ $vehicle->user_id }}"
                                data-type="{{ $vehicle->resolvedType()?->id }}"
                                data-specification="{{ $vehicle->vehicle_specification_id }}">
                                {{ $vehicle->plate }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo de vehículo --}}
                <div class="item-service-field hidden min-w-0 sm:col-span-3">
                    <label class="mb-1 flex min-h-8 items-end text-[11px] font-semibold uppercase text-gray-500">
                        Tipo de vehículo
                    </label>

                    <select data-field="vehicle_specification_id"
                        class="vehicle-type-select vehicle-specification-select h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                        <option value="">
                            Sin especificación
                        </option>

                        @foreach ($vehicleSpecifications as $specification)
                            <option value="{{ $specification->id }}" data-type="{{ $specification->type?->id }}">
                                {{ $specification->brand?->name }} / {{ $specification->model?->name }} /
                                {{ $specification->type?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Cantidad --}}
                <div class="min-w-0 sm:col-span-2">
                    <label class="mb-1 flex min-h-8 items-end text-[11px] font-semibold uppercase text-gray-500">
                        Cantidad
                    </label>

                    <input type="number" data-field="quantity" value="1" min="1"
                        class="quantity-input h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-center text-sm text-gray-700 outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                </div>

                {{-- Precio unitario --}}
                <div class="min-w-0 sm:col-span-3">
                    <label class="mb-1 flex min-h-8 items-end text-[11px] font-semibold uppercase text-gray-500">
                        Precio unitario
                    </label>

                    <div
                        class="unit-price flex h-11 w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-800">
                        $0.00
                    </div>
                </div>

                {{-- Eliminar --}}
                <div class="flex sm:col-span-1 sm:justify-end">
                    <div class="w-full">
                        <span class="mb-1 hidden min-h-8 sm:block"></span>

                        <button type="button"
                            class="remove-sale-item inline-flex h-11 w-full items-center justify-center rounded-lg border border-red-200 bg-white text-red-600 transition hover:bg-red-50 sm:w-11"
                            title="Eliminar ítem" aria-label="Eliminar ítem">
                            <x-heroicon-o-trash class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <span class="line-subtotal sr-only">
                $0.00
            </span>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        const catalogItems = @json($catalogPayload);
        const saleItems = document.getElementById('saleItems');
        const template = document.getElementById('saleItemTemplate');
        const quickClientModal = document.getElementById('quickClientModal');
        const quickClientForm = document.getElementById('quickClientForm');
        const quickClientErrors = document.getElementById('quickClientErrors');
        const clientSelect = document.getElementById('clientSelect');
        const saveQuickClient = document.getElementById('saveQuickClient');
        const quickVehicleModal = document.getElementById('quickVehicleModal');
        const quickVehicleForm = document.getElementById('quickVehicleForm');
        const quickVehicleErrors = document.getElementById('quickVehicleErrors');
        const saveQuickVehicle = document.getElementById('saveQuickVehicle');
        const quickVehicleSpecificationMode = document.getElementById('quickVehicleSpecificationMode');
        const quickVehicleExistingSpecification = document.getElementById('quickVehicleExistingSpecification');
        const quickVehicleNewSpecification = document.getElementById('quickVehicleNewSpecification');
        const quickVehicleSpecificationSelect = document.getElementById('quickVehicleSpecificationSelect');
        const quickVehicleNewSpecificationInputs = quickVehicleForm?.querySelectorAll(
            'input[name="new_vehicle_brand_name"], input[name="new_vehicle_model_name"], input[name="new_vehicle_type_name"]'
        ) || [];
        const businessModelProducts = @json(\App\Models\CatalogType::BUSINESS_MODEL_PRODUCTS);
        const businessModelServices = @json(\App\Models\CatalogType::BUSINESS_MODEL_SERVICES);
        let itemIndex = 0;
        let quickVehicleTargetRow = null;

        function money(value) {
            return '$' + Number(value || 0).toFixed(2);
        }

        function itemById(id) {
            return catalogItems.find((item) => String(item.id) === String(id));
        }

        function isProductKind(kind) {
            return kind === businessModelProducts;
        }

        function refreshNames(row) {
            row.querySelectorAll('[data-field]').forEach((field) => {
                field.name = `items[${row.dataset.index}][${field.dataset.field}]`;
            });
        }

        function setDefaultVariant(row, item) {
            const variantInput = row.querySelector('.variant-input');
            const defaultVariant = (item?.variants || []).find((variant) => variant.is_default) || item?.variants?.[0];
            variantInput.value = defaultVariant?.id || '';
        }

        function fillCatalogItems(row) {
            const kind = row.querySelector('.row-kind-select')?.value || '';
            const catalogSelect = row.querySelector('.catalog-select');
            catalogSelect.innerHTML = '';
            catalogSelect.add(new Option(kind ? `Selecciona ${isProductKind(kind) ? 'un producto' : 'un servicio'}` :
                'Primero selecciona el tipo', ''));

            if (!kind) {
                catalogSelect.disabled = true;
                return;
            }

            catalogSelect.disabled = false;
            catalogItems
                .filter((item) => item.business_model === kind)
                .forEach((item) => catalogSelect.add(new Option(`${item.name} - ${item.type}`, item.id)));
        }

        function syncRowMode(row) {
            const kind = row.querySelector('.row-kind-select')?.value || '';
            const isProduct = isProductKind(kind);
            const isService = kind === businessModelServices;
            const catalogLabel = row.querySelector('.item-catalog-label');

            catalogLabel.textContent = isProduct ? 'Producto' : (isService ? 'Servicio' : 'Producto/Servicio');

            const variantInput = row.querySelector('.variant-input');
            variantInput.disabled = !isProduct;
            if (!isProduct) variantInput.value = '';

            row.querySelectorAll('.item-service-field').forEach((field) => {
                field.classList.toggle('hidden', !isService);
                field.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !isService;
                    if (!isService) input.value = '';
                });
            });
        }

        function linePrice(row) {
            const item = itemById(row.querySelector('.catalog-select')?.value);
            if (!item) return 0;

            const isProduct = item.business_model === businessModelProducts;
            if (isProduct) {
                const variantId = row.querySelector('.variant-input')?.value;
                const variant = (item.variants || []).find((candidate) => String(candidate.id) === String(variantId));
                return Number(variant?.price || item.price || 0);
            }

            const vehicleSelect = row.querySelector('.vehicle-select');
            const selectedVehicleTypeId = vehicleSelect?.selectedOptions?.[0]?.dataset?.type;
            const fallbackTypeId = row.querySelector('.vehicle-specification-select')?.selectedOptions?.[0]?.dataset?.type;
            const vehicleTypeId = selectedVehicleTypeId || fallbackTypeId;
            const selectedSpecificationId = vehicleSelect?.selectedOptions?.[0]?.dataset?.specification ||
                row.querySelector('.vehicle-specification-select')?.value;
            const specificationKey = selectedSpecificationId ? `specification:${selectedSpecificationId}` : null;
            const typeKey = vehicleTypeId ? `type:${vehicleTypeId}` : null;

            if (specificationKey && item.vehicle_prices?.[specificationKey] !== undefined) {
                return Number(item.vehicle_prices[specificationKey]);
            }

            if (typeKey && item.vehicle_prices?.[typeKey] !== undefined) {
                return Number(item.vehicle_prices[typeKey]);
            }

            return Number(item.price || 0);
        }

        function recalculate() {
            let subtotal = 0;
            saleItems.querySelectorAll('.sale-item-row').forEach((row) => {
                const quantity = Math.max(1, Number(row.querySelector('.quantity-input')?.value || 1));
                const unitPrice = linePrice(row);
                const lineSubtotal = unitPrice * quantity;
                row.querySelector('.unit-price').textContent = money(unitPrice);
                row.querySelector('.line-subtotal').textContent = money(lineSubtotal);
                subtotal += lineSubtotal;
            });

            const discount = 0;
            const tax = 0;
            const total = subtotal - discount + tax;

            document.getElementById('subtotalText').textContent = money(subtotal);
            document.getElementById('discountText').textContent = money(discount);
            document.getElementById('taxText').textContent = money(tax);
            document.getElementById('totalText').textContent = money(total);
        }

        function optionMatchesClient(option, clientId) {
            return Boolean(clientId) && option.dataset.client === clientId;
        }

        function filterVehicleSelect(select, clientId) {
            Array.from(select.options).forEach((option) => {
                if (!option.value) return;
                const visible = optionMatchesClient(option, clientId);
                option.hidden = !visible;
                option.disabled = !visible;
            });

            if (select.value && select.selectedOptions[0]?.disabled) {
                select.value = '';
            }
        }

        function filterVehiclesByClient() {
            const clientId = clientSelect?.value || '';
            saleItems.querySelectorAll('.vehicle-select').forEach((select) => {
                filterVehicleSelect(select, clientId);
            });
        }

        function addRow() {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.sale-item-row');
            row.dataset.index = itemIndex++;
            syncRowMode(row);
            fillCatalogItems(row);
            refreshNames(row);
            saleItems.appendChild(fragment);
            filterVehiclesByClient();
            recalculate();
        }

        document.getElementById('addSaleItem')?.addEventListener('click', addRow);

        saleItems?.addEventListener('change', (event) => {
            const row = event.target.closest('.sale-item-row');
            if (!row) return;

            if (event.target.classList.contains('row-kind-select')) {
                syncRowMode(row);
                fillCatalogItems(row);
                setDefaultVariant(row, null);
            }

            if (event.target.classList.contains('catalog-select')) {
                setDefaultVariant(row, itemById(event.target.value));
            }

            if (event.target.classList.contains('vehicle-select')) {
                const typeId = event.target.selectedOptions?.[0]?.dataset?.type;
                const specificationId = event.target.selectedOptions?.[0]?.dataset?.specification;
                if (specificationId) {
                    row.querySelector('.vehicle-specification-select').value = specificationId;
                }
            }

            recalculate();
        });

        saleItems?.addEventListener('input', recalculate);
        saleItems?.addEventListener('click', (event) => {
            const openVehicleButton = event.target.closest('.open-quick-vehicle-modal');
            if (openVehicleButton) {
                quickVehicleTargetRow = openVehicleButton.closest('.sale-item-row');
                openQuickVehicleModal();
                return;
            }

            const button = event.target.closest('.remove-sale-item');
            if (!button) return;
            button.closest('.sale-item-row')?.remove();
            recalculate();
        });

        addRow();

        clientSelect?.addEventListener('change', () => {
            filterVehiclesByClient();
            recalculate();
        });
        filterVehiclesByClient();

        function openQuickClientModal() {
            quickClientModal.classList.remove('hidden');
            quickClientModal.classList.add('flex');
            quickClientForm.querySelector('input[name="name"]')?.focus();
        }

        function closeQuickClientModal() {
            quickClientModal.classList.add('hidden');
            quickClientModal.classList.remove('flex');
            quickClientErrors.classList.add('hidden');
            quickClientErrors.innerHTML = '';
            quickClientForm.reset();
        }

        function showQuickClientErrors(errors) {
            const messages = Object.values(errors || {}).flat();
            quickClientErrors.innerHTML = '';
            (messages.length ? messages : ['No se pudo crear el cliente.']).forEach((message) => {
                const paragraph = document.createElement('p');
                paragraph.textContent = message;
                quickClientErrors.appendChild(paragraph);
            });
            quickClientErrors.classList.remove('hidden');
        }

        document.getElementById('openQuickClientModal')?.addEventListener('click', openQuickClientModal);
        document.getElementById('closeQuickClientModal')?.addEventListener('click', closeQuickClientModal);
        document.getElementById('cancelQuickClient')?.addEventListener('click', closeQuickClientModal);
        quickClientModal?.addEventListener('click', (event) => {
            if (event.target === quickClientModal) closeQuickClientModal();
        });

        quickClientForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            quickClientErrors.classList.add('hidden');
            saveQuickClient.disabled = true;
            saveQuickClient.textContent = 'Guardando...';

            try {
                const response = await fetch(@json(route('admin.clientes.quick-store')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(quickClientForm),
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showQuickClientErrors(data.errors || {});
                    return;
                }

                const cliente = data.cliente;
                clientSelect.add(new Option(cliente.label, cliente.id, true, true));
                clientSelect.value = cliente.id;
                filterVehiclesByClient();
                closeQuickClientModal();
            } catch (error) {
                showQuickClientErrors({});
            } finally {
                saveQuickClient.disabled = false;
                saveQuickClient.textContent = 'Guardar Cliente';
            }
        });

        function showQuickVehicleErrors(errors) {
            const messages = Object.values(errors || {}).flat();
            quickVehicleErrors.innerHTML = '';
            (messages.length ? messages : ['No se pudo crear el vehiculo.']).forEach((message) => {
                const paragraph = document.createElement('p');
                paragraph.textContent = message;
                quickVehicleErrors.appendChild(paragraph);
            });
            quickVehicleErrors.classList.remove('hidden');
        }

        function setQuickVehicleSpecificationMode(mode) {
            const creatingNew = mode === 'new';

            quickVehicleSpecificationMode.value = creatingNew ? 'new' : 'existing';
            quickVehicleExistingSpecification.classList.toggle('hidden', creatingNew);
            quickVehicleNewSpecification.classList.toggle('hidden', !creatingNew);
            quickVehicleSpecificationSelect.disabled = creatingNew;
            quickVehicleSpecificationSelect.required = !creatingNew;

            quickVehicleNewSpecificationInputs.forEach((input) => {
                input.disabled = !creatingNew;
                input.required = creatingNew;
            });

            if (creatingNew) {
                quickVehicleSpecificationSelect.value = '';
                quickVehicleNewSpecificationInputs[0]?.focus();
            } else {
                quickVehicleNewSpecificationInputs.forEach((input) => {
                    input.value = '';
                });
                quickVehicleSpecificationSelect.focus();
            }
        }

        function openQuickVehicleModal() {
            quickVehicleErrors.classList.add('hidden');
            quickVehicleErrors.innerHTML = '';
            const selectedClientId = clientSelect?.value || '';

            if (!selectedClientId) {
                quickVehicleModal.classList.remove('hidden');
                quickVehicleModal.classList.add('flex');
                showQuickVehicleErrors({
                    user_id: ['Selecciona o crea un cliente antes de registrar un vehiculo.'],
                });
                return;
            }

            document.getElementById('quickVehicleUserId').value = selectedClientId;
            setQuickVehicleSpecificationMode('existing');
            quickVehicleModal.classList.remove('hidden');
            quickVehicleModal.classList.add('flex');
            quickVehicleSpecificationSelect?.focus();
        }

        function closeQuickVehicleModal() {
            quickVehicleModal.classList.add('hidden');
            quickVehicleModal.classList.remove('flex');
            quickVehicleErrors.classList.add('hidden');
            quickVehicleErrors.innerHTML = '';
            quickVehicleForm.reset();
            setQuickVehicleSpecificationMode('existing');
            quickVehicleTargetRow = null;
        }

        function addVehicleToSelect(select, vehicle, selected = false) {
            if (!select) return;

            const option = new Option(vehicle.label, vehicle.id, selected, selected);
            option.dataset.client = vehicle.user_id;
            option.dataset.type = vehicle.vehicle_type_id || '';
            option.dataset.specification = vehicle.vehicle_specification_id || '';
            select.add(option);
        }

        function addVehicleToRowSelects(vehicle) {
            saleItems.querySelectorAll('.vehicle-select').forEach((select) => {
                addVehicleToSelect(select, vehicle, false);
            });
        }

        document.getElementById('closeQuickVehicleModal')?.addEventListener('click', closeQuickVehicleModal);
        document.getElementById('cancelQuickVehicle')?.addEventListener('click', closeQuickVehicleModal);
        document.getElementById('showQuickVehicleNewSpecification')?.addEventListener('click', () => {
            setQuickVehicleSpecificationMode('new');
        });
        document.getElementById('showQuickVehicleExistingSpecification')?.addEventListener('click', () => {
            setQuickVehicleSpecificationMode('existing');
        });
        quickVehicleModal?.addEventListener('click', (event) => {
            if (event.target === quickVehicleModal) closeQuickVehicleModal();
        });

        quickVehicleForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            quickVehicleErrors.classList.add('hidden');
            saveQuickVehicle.disabled = true;
            saveQuickVehicle.textContent = 'Guardando...';

            try {
                const response = await fetch(@json(route('admin.vehiculos.quick-store')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(quickVehicleForm),
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showQuickVehicleErrors(data.errors || {});
                    return;
                }

                const vehicle = data.vehicle;
                addVehicleToRowSelects(vehicle);

                if (vehicle.vehicle_specification_id && vehicle.specification_label &&
                    !quickVehicleSpecificationSelect.querySelector(`option[value="${vehicle.vehicle_specification_id}"]`)) {
                    quickVehicleSpecificationSelect.add(new Option(
                        vehicle.specification_label,
                        vehicle.vehicle_specification_id
                    ));
                }

                const targetVehicleSelect = quickVehicleTargetRow?.querySelector('.vehicle-select');
                const targetSpecificationSelect = quickVehicleTargetRow?.querySelector(
                    '.vehicle-specification-select');

                if (targetVehicleSelect && !targetVehicleSelect.disabled) {
                    targetVehicleSelect.value = vehicle.id;
                }

                if (targetSpecificationSelect && !targetSpecificationSelect.disabled) {
                    targetSpecificationSelect.value = vehicle.vehicle_specification_id || '';
                }

                filterVehiclesByClient();
                recalculate();
                closeQuickVehicleModal();
            } catch (error) {
                showQuickVehicleErrors({});
            } finally {
                saveQuickVehicle.disabled = false;
                saveQuickVehicle.textContent = 'Guardar Vehiculo';
            }
        });
    </script>
@endpush
