@extends('layouts.admin')

@section('title', 'Detalle Cliente')

@section('content')
    @php
        $statusClasses = [
            'pending' => 'bg-yellow-100 text-yellow-700',
            'paid' => 'bg-green-100 text-green-700',
            'reserved' => 'bg-blue-100 text-blue-700',
            'failed' => 'bg-red-100 text-red-700',
            'cancelled' => 'bg-gray-100 text-gray-600',
        ];

        $statusLabels = [
            'pending' => 'Pendiente',
            'paid' => 'Pagada',
            'reserved' => 'Reservada',
            'failed' => 'Fallida',
            'cancelled' => 'Cancelada',
        ];

        $whatsapp = $cliente->telefono ? preg_replace('/\D+/', '', (string) $cliente->telefono) : null;
    @endphp

    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-gray-900 break-words">{{ $cliente->name }}</h2>
                <p class="text-sm text-gray-500">Detalle completo del cliente e historial de compras.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @can('clients.update')
                <a href="{{ route('admin.clientes.edit', $cliente) }}"
                    class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                    Editar
                </a>
                @endcan

                @can('clients.delete')
                <form method="POST" action="{{ route('admin.clientes.destroy', $cliente) }}"
                    onsubmit="return confirm('¿Eliminar cliente?');">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-red-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-600">
                        Eliminar
                    </button>
                </form>
                @endcan

                <a href="{{ route('admin.clientes.index') }}"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-white">
                    Volver
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
            <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm xl:col-span-4 2xl:col-span-3">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[120px_1fr] xl:grid-cols-1 2xl:grid-cols-[120px_1fr]">
                    <div class="flex justify-center sm:justify-start xl:justify-center 2xl:justify-start">
                        <div
                            class="flex h-28 w-28 items-center justify-center rounded-full bg-slate-500 text-6xl font-light uppercase text-white shadow-inner">
                            {{ mb_substr($cliente->name, 0, 1) }}
                        </div>
                    </div>

                    <div class="min-w-0">
                        <h3 class="mb-3 text-base font-semibold text-gray-900">Perfil</h3>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-start gap-2">
                                <x-heroicon-o-envelope class="mt-0.5 h-5 w-5 shrink-0 text-gray-500" />
                                <div class="min-w-0">
                                    <p class="text-gray-500">Correo</p>
                                    <p class="break-all font-medium text-gray-900">{{ $cliente->email }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <x-heroicon-o-phone class="mt-0.5 h-5 w-5 shrink-0 text-gray-500" />
                                <div>
                                    <p class="text-gray-500">Teléfono</p>
                                    <p class="font-medium text-gray-900">{{ $cliente->telefono ?? '-' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <x-heroicon-o-map-pin class="mt-0.5 h-5 w-5 shrink-0 text-gray-500" />
                                <div class="min-w-0">
                                    <p class="text-gray-500">Dirección</p>
                                    <p class="break-words font-medium text-gray-900">{{ $cliente->direccion ?? '-' }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-2">
                                <x-heroicon-o-calendar-days class="mt-0.5 h-5 w-5 shrink-0 text-gray-500" />
                                <div>
                                    <p class="text-gray-500">Registro</p>
                                    <p class="font-medium text-gray-900">{{ $cliente->created_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        @if ($whatsapp)
                            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer"
                                class="mt-4 inline-flex w-full items-center justify-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700">
                                WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm xl:col-span-8 2xl:col-span-9">
                <h3 class="mb-4 text-base font-semibold text-gray-900">Resumen Comercial</h3>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500">Órdenes</p>
                        <div class="mt-2 flex items-end gap-2">
                            <p class="text-2xl font-bold text-gray-900">{{ $resumen['total_ordenes'] }}</p>
                        </div>
                    </article>

                    <article class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500">Total pagado</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">${{ number_format($resumen['total_pagado'], 2) }}
                        </p>
                    </article>

                    <article class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500">Reservas</p>
                        <div class="mt-2 flex items-end gap-2">
                            <p class="text-2xl font-bold text-gray-900">{{ $resumen['total_reservado'] }}</p>
                        </div>
                    </article>

                    <article class="rounded-lg bg-gray-50 p-4">
                        <p class="text-xs font-semibold uppercase text-gray-500">Última compra</p>
                        <p class="mt-3 text-sm font-semibold text-gray-900">
                            {{ $resumen['ultima_compra'] ? $resumen['ultima_compra']->format('d/m/Y H:i') : 'Sin compras' }}
                        </p>
                    </article>
                </div>

                <div class="mt-5 border-t border-gray-100 pt-4">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-900">Vehículos del Cliente</h3>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                            {{ $cliente->vehicles->count() }} registrados
                        </span>
                        @can('vehicles.create')
                        <button type="button" data-open-vehicle-modal
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-gray-700">
                            <x-heroicon-o-plus class="h-4 w-4" />
                            Vehículo
                        </button>
                        @endcan
                    </div>
                </div>

                @if ($cliente->vehicles->isNotEmpty())
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($cliente->vehicles as $vehicle)
                            @php
                                $brand = $vehicle->resolvedBrand()?->name ?? '-';
                                $model = $vehicle->resolvedModel()?->name ?? '-';
                                $type = $vehicle->resolvedType()?->name ?? '-';
                            @endphp

                            <a href="{{ route('admin.vehiculos.show', $vehicle) }}"
                                class="group rounded-lg border border-gray-100 bg-gray-50 p-4 transition hover:border-gray-200 hover:bg-white hover:shadow-sm">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-gray-500 shadow-sm">
                                        <x-heroicon-o-truck class="h-5 w-5" />
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="font-semibold text-gray-900 group-hover:text-blue-700">
                                                {{ $vehicle->plate ?: 'Sin placa' }}
                                            </p>
                                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $vehicle->active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $vehicle->active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>

                                        <p class="mt-1 truncate text-sm text-gray-600">{{ $brand }} · {{ $model }}</p>
                                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $type }}</p>

                                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-500">
                                            <span>Color: <strong class="text-gray-700">{{ $vehicle->color ?: '-' }}</strong></span>
                                            <span>Year: <strong class="text-gray-700">{{ $vehicle->year ?: '-' }}</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex min-h-[96px] items-center justify-between gap-4 rounded-lg bg-gray-50 px-4 py-5">
                        <div class="hidden sm:flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm">
                            <x-heroicon-o-cog-6-tooth class="h-7 w-7" />
                        </div>

                        <p class="flex-1 text-center text-sm text-gray-500">
                            Este cliente aún no tiene vehículos registrados.
                        </p>

                        <div class="hidden sm:flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-gray-400 shadow-sm">
                            <x-heroicon-o-truck class="h-7 w-7" />
                        </div>
                    </div>
                @endif
                </div>
            </section>
        </div>

        <section class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="px-4 py-4">
                <h3 class="text-base font-semibold text-gray-900">Historial de Compras</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px] text-sm">
                    <thead class="border-y border-gray-100 bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Orden</th>
                            <th class="px-4 py-3 text-left font-semibold">Fecha</th>
                            <th class="px-4 py-3 text-left font-semibold">Estado</th>
                            <th class="px-4 py-3 text-center font-semibold">Items</th>
                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                            <th class="px-4 py-3 text-right font-semibold">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($cliente->orders as $order)
                            @php
                                $statusClass = $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-600';
                                $statusLabel = $statusLabels[$order->status] ?? ucfirst((string) $order->status);
                            @endphp

                            <tr class="transition hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-800">#{{ $order->id }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ $order->items->count() }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                    ${{ number_format($order->total, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.ventas.show', $order) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-600 transition hover:bg-blue-50"
                                        title="Ver venta" aria-label="Ver venta">
                                        <x-heroicon-o-eye class="h-4 w-4" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center justify-center">
                                        <x-heroicon-o-shopping-cart class="h-16 w-16 text-gray-300" />
                                        <p class="mt-2 font-semibold text-gray-800">¡Todo listo!</p>
                                        <p class="text-sm text-gray-500">Este cliente aún no tiene compras registradas.</p>
                                        <p class="text-sm text-gray-500">Puedes crear una nueva venta para este cliente.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div id="clientVehicleModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="clientVehicleModalTitle" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center px-4 py-6">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" data-close-vehicle-modal></div>

            <div class="relative w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 id="clientVehicleModalTitle" class="text-lg font-bold text-gray-900">Agregar vehículo</h3>
                        <p class="mt-1 text-sm text-gray-500">Registra un vehículo para {{ $cliente->name }} sin salir del cliente.</p>
                    </div>
                    <button type="button" data-close-vehicle-modal
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                        aria-label="Cerrar modal">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.vehiculos.store') }}" class="space-y-5 px-5 py-5">
                    @csrf
                    <input type="hidden" name="return_back" value="1">
                    <input type="hidden" name="user_id" value="{{ $cliente->id }}">

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

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Especificación del vehículo *</label>
                            <select name="vehicle_specification_id" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200">
                                <option value="">Selecciona marca / modelo / tipo</option>
                                @foreach ($vehicleSpecifications as $specification)
                                    <option value="{{ $specification->id }}" @selected(old('vehicle_specification_id') == $specification->id)>
                                        {{ $specification->brand?->name }} / {{ $specification->model?->name }} / {{ $specification->type?->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs text-gray-400">Usa las especificaciones reutilizables del módulo Vehículos.</p>
                                @can('vehicles.specifications.view')
                                <a href="{{ route('admin.vehiculos.specifications.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
                                    Administrar especificaciones
                                </a>
                                @endcan
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Placa *</label>
                            <input type="text" name="plate" value="{{ old('plate') }}" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                                placeholder="Ej: ABC-1234">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Color</label>
                            <input type="text" name="color" value="{{ old('color') }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                                placeholder="Ej: Blanco, negro, rojo">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Year</label>
                            <input type="number" name="year" value="{{ old('year') }}" min="1900" max="{{ now()->year + 1 }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                                placeholder="{{ now()->year }}">
                        </div>

                        <label class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" value="1" class="rounded border-gray-300 text-gray-900" checked>
                            <span>
                                <span class="block text-sm font-medium text-gray-700">Vehículo activo</span>
                                <span class="block text-xs text-gray-400">Disponible para ventas y servicios.</span>
                            </span>
                        </label>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Observaciones</label>
                            <textarea name="observations" rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                                placeholder="Notas internas o información relevante del vehículo">{{ old('observations') }}</textarea>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end">
                        <button type="button" data-close-vehicle-modal
                            class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                            Guardar vehículo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('clientVehicleModal');
            const openButtons = document.querySelectorAll('[data-open-vehicle-modal]');
            const closeButtons = document.querySelectorAll('[data-close-vehicle-modal]');

            if (!modal) {
                return;
            }

            const openModal = () => {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            openButtons.forEach((button) => button.addEventListener('click', openModal));
            closeButtons.forEach((button) => button.addEventListener('click', closeModal));

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            @if ($errors->any())
                openModal();
            @endif
        });
    </script>
@endpush
