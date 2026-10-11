@php
    $vehicleSpecificationsModalId = $modalId ?? 'vehicleSpecificationsModal';
@endphp

<div id="{{ $vehicleSpecificationsModalId }}" data-vehicle-specifications-modal
    class="fixed inset-0 z-[3000] hidden items-center justify-center overflow-y-auto bg-gray-900/60 p-2 sm:p-4 lg:p-6">
    <div
        class="relative flex max-h-[calc(100dvh-1rem)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-gray-50 shadow-2xl sm:max-h-[calc(100dvh-2rem)]">
        <div class="shrink-0 flex items-start justify-between gap-4 border-b border-gray-100 bg-white px-4 py-4 sm:px-5">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Especificaciones de Vehículos</h3>
                <p class="text-sm text-gray-400">Crea marcas, modelos o tipos reutilizables.</p>
            </div>
            <button type="button" data-close-vehicle-modal
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                title="Cerrar" aria-label="Cerrar">
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-4 sm:p-5">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <section class="rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="border-b border-gray-100 p-4">
                        <h4 class="font-bold text-gray-800">Marcas</h4>
                        <p class="mt-1 text-xs text-gray-400">Registra marcas reutilizables.</p>
                    </div>
                    @can('vehicles.specifications.create')
                    <form method="POST" action="{{ route('admin.vehiculos.specifications.brands.store') }}"
                        class="space-y-4 p-4">
                        @csrf
                        <input type="text" name="name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
                            placeholder="Ej: Toyota" required>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" value="1" class="rounded border-gray-300" checked>
                                Activo
                            </label>
                            <button
                                class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">
                                Crear marca
                            </button>
                        </div>
                    </form>
                    @endcan
                </section>

                <section class="rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="border-b border-gray-100 p-4">
                        <h4 class="font-bold text-gray-800">Modelos</h4>
                        <p class="mt-1 text-xs text-gray-400">Relaciona cada modelo con una marca.</p>
                    </div>
                    @can('vehicles.specifications.create')
                    <form method="POST" action="{{ route('admin.vehiculos.specifications.models.store') }}"
                        class="space-y-4 p-4">
                        @csrf
                        <select name="vehicle_brand_id" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
                            required>
                            <option value="">Selecciona una marca</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
                            placeholder="Ej: Grand Vitara, F-150" required>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" value="1" class="rounded border-gray-300" checked>
                                Activo
                            </label>
                            <button
                                class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">
                                Crear modelo
                            </button>
                        </div>
                    </form>
                    @endcan
                </section>

                <section class="rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div class="border-b border-gray-100 p-4">
                        <h4 class="font-bold text-gray-800">Tipos de vehículo</h4>
                        <p class="mt-1 text-xs text-gray-400">Define clases como sedán, SUV o camioneta.</p>
                    </div>
                    @can('vehicles.specifications.create')
                    <form method="POST" action="{{ route('admin.vehiculos.specifications.types.store') }}"
                        class="space-y-4 p-4">
                        @csrf
                        <input type="text" name="name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"
                            placeholder="Ej: SUV, camioneta grande" required>
                        <div class="flex items-center justify-between gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="hidden" name="active" value="0">
                                <input type="checkbox" name="active" value="1" class="rounded border-gray-300" checked>
                                Activo
                            </label>
                        </div>
                        <button
                            class="w-full rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-700">
                            Crear tipo
                        </button>
                    </form>
                    @endcan
                </section>
            </div>
        </div>
    </div>
</div>

@include('admin.vehiculos.partials._specifications-modal-script')
