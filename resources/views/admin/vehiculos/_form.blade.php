@csrf

@if ($errors->any())
    <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">
        <p class="font-semibold">Revisa los campos marcados.</p>
        <ul class="mt-2 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <section class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-6 shadow-sm space-y-5">
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Información principal</p>
            <p class="text-sm text-gray-500 mt-1">Relaciona el vehículo con un cliente y registra su identificación.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                <select name="user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Selecciona un cliente</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected(old('user_id', $vehiculo->user_id ?? '') == $client->id)>
                            {{ $client->name }} — {{ $client->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Placa *</label>
                <input type="text" name="plate" value="{{ old('plate', $vehiculo->plate ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase" placeholder="Ej: ABC-1234" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                <input type="text" name="color" value="{{ old('color', $vehiculo->color ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ej: Blanco, negro, rojo">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <input type="number" name="year" value="{{ old('year', $vehiculo->year ?? '') }}"
                    min="1900" max="{{ now()->year + 1 }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="{{ now()->year }}">
            </div>

            <div class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" class="rounded border-gray-300 text-gray-900"
                    @checked(old('active', $vehiculo->active ?? true))>
                <div>
                    <p class="text-sm font-medium text-gray-700">Vehículo activo</p>
                    <p class="text-xs text-gray-400">Disponible para usar en ventas y servicios.</p>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
            <textarea name="observations" rows="4"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                placeholder="Detalles visibles, notas internas o información relevante del vehículo">{{ old('observations', $vehiculo->observations ?? '') }}</textarea>
        </div>
    </section>

    <section class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm space-y-5">
        <div>
            <p class="text-xs uppercase tracking-wide text-gray-400 font-semibold">Especificaciones</p>
            <p class="text-sm text-gray-500 mt-1">Selecciona la combinación de marca, modelo y tipo.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Especificación del vehículo *</label>
            <select name="vehicle_specification_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                <option value="">Selecciona marca / modelo / tipo</option>
                @foreach ($specifications as $specification)
                    <option value="{{ $specification->id }}" @selected(old('vehicle_specification_id', $vehiculo->vehicle_specification_id ?? '') == $specification->id)>
                        {{ $specification->brand?->name }} / {{ $specification->model?->name }} / {{ $specification->type?->name }}
                    </option>
                @endforeach
            </select>
            @error('vehicle_specification_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="button" data-open-vehicle-modal="vehicleSpecificationsFullModal"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
            <x-heroicon-o-plus class="h-4 w-4" />
            ¿No encuentras la marca o modelo? Créalo aquí
        </button>

        @can('vehicles.specifications.view')
        <a href="{{ route('admin.vehiculos.specifications.index') }}"
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
            <x-heroicon-o-adjustments-horizontal class="h-4 w-4" />
            Administrar especificaciones
        </a>
        @endcan
    </section>
</div>

@include('admin.vehiculos.partials.specifications-modal-full', ['modalId' => 'vehicleSpecificationsFullModal'])

<div class="flex flex-col sm:flex-row gap-3 justify-end">
    @if (!empty($modalCancel))
        <button type="button" data-close-vehicle-modal
            class="inline-flex items-center justify-center bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-semibold">
            Cancelar
        </button>
    @else
        <a href="{{ route('admin.vehiculos.index') }}" class="inline-flex items-center justify-center bg-gray-100 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-semibold">
            Cancelar
        </a>
    @endif
    <button class="inline-flex items-center justify-center bg-gray-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-gray-700 transition">
        {{ $buttonText ?? 'Guardar vehículo' }}
    </button>
</div>
