@extends('layouts.admin')

@section('title', 'Vehiculos')

@push('styles')
    @vite('resources/scss/admin/admin-data-tables.scss')
@endpush

@section('content')
    <div class="admin-data-page">
        <div class="admin-data-page__header flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-truck class="w-8 h-8 text-gray-800" />
                    <h2 class="text-2xl font-bold text-gray-800">Vehiculos</h2>
                </div>
                <p class="text-gray-500 text-sm mt-1">Registro de vehiculos enlazados con clientes.</p>
            </div>

            <div class="flex items-center gap-2">
                @can('vehicles.specifications.view')
                <a href="{{ route('admin.vehiculos.specifications.index') }}"
                    class="inline-flex items-center justify-center bg-white border border-gray-200 text-gray-700 w-11 h-11 rounded-lg hover:bg-gray-50 transition"
                    title="Especificaciones de vehiculos" aria-label="Especificaciones de vehiculos">
                    <x-heroicon-o-adjustments-horizontal class="w-5 h-5" />
                </a>
                @endcan
                @can('vehicles.create')
                <a href="{{ route('admin.vehiculos.create') }}"
                    class="inline-flex items-center justify-center bg-gray-900 text-white w-11 h-11 rounded-lg hover:bg-gray-700 transition"
                    title="Nuevo vehiculo" aria-label="Nuevo vehiculo">
                    <x-heroicon-o-plus class="w-5 h-5" />
                </a>
                @endcan
            </div>
        </div>

        <div class="admin-data-page__stats grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                <p class="text-xs text-gray-400 uppercase font-semibold">Vehiculos</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                <p class="text-xs text-gray-400 uppercase font-semibold">Activos</p>
                <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                <p class="text-xs text-gray-400 uppercase font-semibold">Marcas</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['brands'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
                <p class="text-xs text-gray-400 uppercase font-semibold">Clientes con vehiculo</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $stats['clients'] }}</p>
            </div>
        </div>

        <div class="admin-data-page__toolbar bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="relative md:col-span-2">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input type="search" name="q" value="{{ $search }}"
                        placeholder="Buscar por placa, cliente, marca, modelo o color"
                        class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm">
                </div>
                <select name="brand_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                    <option value="">Todas las marcas</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected($brandId == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
                <select name="vehicle_type_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                    <option value="">Todos los tipos</option>
                    @foreach ($vehicleTypes as $vehicleType)
                        <option value="{{ $vehicleType->id }}" @selected($vehicleTypeId == $vehicleType->id)>{{ $vehicleType->name }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <select name="status" class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm">
                        <option value="">Todos</option>
                        <option value="active" @selected($status === 'active')>Activos</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
                    </select>
                    <button class="inline-flex items-center justify-center w-11 h-11 rounded-lg bg-gray-900 text-white hover:bg-gray-700 transition"
                        title="Buscar" aria-label="Buscar">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                    </button>
                    <a href="{{ route('admin.vehiculos.index') }}"
                        class="inline-flex items-center justify-center w-11 h-11 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition"
                        title="Limpiar" aria-label="Limpiar">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </a>
                </div>
            </form>
        </div>

        <div class="admin-data-page__list bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="admin-data-page__mobile-scroll md:hidden divide-y divide-gray-100">
                @forelse ($vehicles as $vehicle)
                    <article class="p-4 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-bold text-gray-800 break-words">{{ $vehicle->plate }}</h3>
                                <p class="text-sm text-gray-500 break-words">{{ $vehicle->resolvedBrand()?->name }} {{ $vehicle->resolvedModel()?->name }}</p>
                            </div>
                            @if ($vehicle->active)
                                <span class="shrink-0 bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs font-medium">Activo</span>
                            @else
                                <span class="shrink-0 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs font-medium">Inactivo</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Cliente</p>
                                <p class="text-gray-700 break-words">{{ $vehicle->client?->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Color</p>
                                <p class="text-gray-700 break-words">{{ $vehicle->color ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Year</p>
                                <p class="text-gray-700">{{ $vehicle->year ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Tipo</p>
                                <p class="text-gray-700">{{ $vehicle->resolvedType()?->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Registro</p>
                                <p class="text-gray-700">{{ $vehicle->created_at?->format('d/m/Y') ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.vehiculos.show', $vehicle) }}"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-blue-600 hover:bg-blue-50 transition"
                                title="Ver vehiculo" aria-label="Ver vehiculo">
                                <x-heroicon-o-eye class="w-5 h-5" />
                            </a>
                            @can('vehicles.update')
                            <a href="{{ route('admin.vehiculos.edit', $vehicle) }}"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-yellow-600 hover:bg-yellow-50 transition"
                                title="Editar vehiculo" aria-label="Editar vehiculo">
                                <x-heroicon-o-pencil-square class="w-5 h-5" />
                            </a>
                            @endcan
                            @can('vehicles.delete')
                            <form method="POST" action="{{ route('admin.vehiculos.destroy', $vehicle) }}" onsubmit="return confirm('¿Eliminar vehiculo?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-red-600 hover:bg-red-50 transition"
                                    title="Eliminar vehiculo" aria-label="Eliminar vehiculo">
                                    <x-heroicon-o-trash class="w-5 h-5" />
                                </button>
                            </form>
                            @endcan
                        </div>
                    </article>
                @empty
                    <div class="px-4 py-8 text-center text-gray-400">No hay vehiculos registrados.</div>
                @endforelse
            </div>

            <div class="admin-data-page__scroll hidden md:block">
                <table class="w-full table-fixed text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="w-[12%] px-3 py-3 text-center">Placa</th>
                            <th class="w-[18%] px-3 py-3 text-center">Cliente</th>
                            <th class="w-[14%] px-3 py-3 text-center">Marca</th>
                            <th class="w-[14%] px-3 py-3 text-center">Modelo</th>
                            <th class="w-[13%] px-3 py-3 text-center">Tipo</th>
                            <th class="w-[8%] px-3 py-3 text-center">Color</th>
                            <th class="w-[7%] px-3 py-3 text-center">Year</th>
                            <th class="w-[10%] px-3 py-3 text-center">Estado</th>
                            <th class="w-[12%] px-3 py-3 text-center">Acc.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vehicles as $vehicle)
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-3 py-3 text-center font-semibold text-gray-800 truncate">{{ $vehicle->plate }}</td>
                                <td class="px-3 py-3 text-center text-gray-700 truncate">{{ $vehicle->client?->name ?? '-' }}</td>
                                <td class="px-3 py-3 text-center text-gray-700 truncate">{{ $vehicle->resolvedBrand()?->name ?? '-' }}</td>
                                <td class="px-3 py-3 text-center text-gray-700 truncate">{{ $vehicle->resolvedModel()?->name ?? '-' }}</td>
                                <td class="px-3 py-3 text-center text-gray-700 truncate">{{ $vehicle->resolvedType()?->name ?? '-' }}</td>
                                <td class="px-3 py-3 text-center text-gray-600 truncate">{{ $vehicle->color ?? '-' }}</td>
                                <td class="px-3 py-3 text-center text-gray-600">{{ $vehicle->year ?? '-' }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if ($vehicle->active)
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Activo</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs font-medium">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('admin.vehiculos.show', $vehicle) }}"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-600 hover:bg-blue-50 transition"
                                            title="Ver vehiculo" aria-label="Ver vehiculo">
                                            <x-heroicon-o-eye class="w-4 h-4" />
                                        </a>
                                        @can('vehicles.update')
                                        <a href="{{ route('admin.vehiculos.edit', $vehicle) }}"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-yellow-600 hover:bg-yellow-50 transition"
                                            title="Editar vehiculo" aria-label="Editar vehiculo">
                                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                                        </a>
                                        @endcan
                                        @can('vehicles.delete')
                                        <form method="POST" action="{{ route('admin.vehiculos.destroy', $vehicle) }}" onsubmit="return confirm('¿Eliminar vehiculo?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 transition"
                                                title="Eliminar vehiculo" aria-label="Eliminar vehiculo">
                                                <x-heroicon-o-trash class="w-4 h-4" />
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-400">No hay vehiculos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-data-page__pagination">{{ $vehicles->links() }}</div>
    </div>
@endsection
