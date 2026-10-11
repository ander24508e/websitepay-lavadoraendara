@extends('layouts.admin')

@section('title', 'Detalle Vehiculo')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3 min-w-0">
                <a href="{{ route('admin.vehiculos.index') }}"
                    class="inline-flex items-center justify-center w-10 h-10 bg-white rounded-lg shadow-sm hover:bg-gray-50 transition text-gray-500 hover:text-gray-800 shrink-0"
                    title="Volver" aria-label="Volver">
                    <x-heroicon-o-arrow-left class="w-5 h-5" />
                </a>
                <div class="min-w-0">
                    <h2 class="text-2xl font-bold text-gray-800 break-words">{{ $vehiculo->plate }}</h2>
                    <p class="text-gray-500 text-sm mt-1 break-words">{{ $vehiculo->resolvedBrand()?->name }} {{ $vehiculo->resolvedModel()?->name }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('vehicles.update')
                <a href="{{ route('admin.vehiculos.edit', $vehiculo) }}"
                    class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition"
                    title="Editar vehiculo" aria-label="Editar vehiculo">
                    <x-heroicon-o-pencil-square class="w-5 h-5" />
                </a>
                @endcan
                @can('vehicles.delete')
                <form method="POST" action="{{ route('admin.vehiculos.destroy', $vehiculo) }}" onsubmit="return confirm('¿Eliminar vehiculo?');">
                    @csrf
                    @method('DELETE')
                    <button class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 text-red-700 hover:bg-red-200 transition"
                        title="Eliminar vehiculo" aria-label="Eliminar vehiculo">
                        <x-heroicon-o-trash class="w-5 h-5" />
                    </button>
                </form>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <section class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-gray-800">Vehiculo</h3>
                    @if ($vehiculo->active)
                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Activo</span>
                    @else
                        <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs font-medium">Inactivo</span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-4 text-sm">
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Marca</p>
                        <p class="font-medium text-gray-800 break-words">{{ $vehiculo->resolvedBrand()?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Modelo</p>
                        <p class="font-medium text-gray-800 break-words">{{ $vehiculo->resolvedModel()?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Placa</p>
                        <p class="font-medium text-gray-800">{{ $vehiculo->plate }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Color</p>
                        <p class="font-medium text-gray-800 break-words">{{ $vehiculo->color ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Year</p>
                        <p class="font-medium text-gray-800">{{ $vehiculo->year ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Tipo</p>
                        <p class="font-medium text-gray-800">{{ $vehiculo->resolvedType()?->name ?? '-' }}</p>
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm space-y-4 xl:col-span-2">
                <h3 class="font-semibold text-gray-800">Cliente</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Nombre</p>
                        <p class="font-medium text-gray-800 break-words">{{ $vehiculo->client?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Correo</p>
                        <p class="font-medium text-gray-800 break-all">{{ $vehiculo->client?->email ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Telefono</p>
                        <p class="font-medium text-gray-800 break-words">{{ $vehiculo->client?->telefono ?? '-' }}</p>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="{{ $vehiculo->client ? route('admin.clientes.show', $vehiculo->client) : '#' }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200 transition {{ $vehiculo->client ? '' : 'pointer-events-none opacity-50' }}">
                        <x-heroicon-o-user class="w-4 h-4" />
                        Ver cliente
                    </a>
                </div>
            </section>
        </div>

        <section class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
            <h3 class="font-semibold text-gray-800">Observaciones</h3>
            <p class="text-sm text-gray-600 mt-2 whitespace-pre-line">{{ $vehiculo->observations ?: 'Sin observaciones registradas.' }}</p>
        </section>

        <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Historial de servicios</h3>
                <p class="text-xs text-gray-400 mt-1">Últimos servicios registrados específicamente para este vehículo.</p>
            </div>
            <div class="md:hidden divide-y divide-gray-100">
                @forelse ($vehiculo->orderItems as $orderItem)
                    <article class="p-4 text-sm">
                        <div class="flex justify-between gap-3">
                            <p class="font-semibold text-gray-800">{{ $orderItem->item_display_name }}</p>
                            <p class="font-semibold text-gray-800">${{ number_format((float) $orderItem->unit_price, 2) }}</p>
                        </div>
                        <p class="text-gray-500 mt-1">Orden #{{ $orderItem->order_id }} · {{ $orderItem->created_at?->format('d/m/Y H:i') }}</p>
                    </article>
                @empty
                    <div class="p-8 text-center text-gray-400">Este vehículo aún no tiene servicios registrados.</div>
                @endforelse
            </div>
            <div class="hidden md:block">
                <table class="w-full table-fixed text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="w-[18%] px-4 py-3 text-center">Orden</th>
                            <th class="w-[32%] px-4 py-3 text-center">Servicio</th>
                            <th class="w-[18%] px-4 py-3 text-center">Precio</th>
                            <th class="w-[16%] px-4 py-3 text-center">Estado</th>
                            <th class="w-[16%] px-4 py-3 text-center">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vehiculo->orderItems as $orderItem)
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-3 text-center font-semibold">#{{ $orderItem->order_id }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ $orderItem->item_display_name }}</td>
                                <td class="px-4 py-3 text-center font-semibold">${{ number_format((float) $orderItem->unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-center">{{ ucfirst($orderItem->order?->status ?? '-') }}</td>
                                <td class="px-4 py-3 text-center text-gray-500">{{ $orderItem->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-gray-400">Este vehículo aún no tiene servicios registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
