@extends('layouts.admin')

@section('title', 'Ubicaciones de Inventario')

@section('content')
<div class="mx-auto w-full max-w-full overflow-x-hidden px-3 pb-4 sm:px-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Ubicaciones</h2>
            <p class="text-sm text-gray-500 mt-1">Sucursales, bodegas o puntos donde existe stock físico.</p>
        </div>
        <a href="{{ route('admin.inventario.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Volver</a>
    </div>

    @can('inventory.locations.create')
    <form method="POST" action="{{ route('admin.inventario.locations.store') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 grid grid-cols-1 md:grid-cols-5 gap-4">
        @csrf
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
            <select name="type" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
                <option value="warehouse">Bodega</option>
                <option value="branch">Sucursal</option>
                <option value="vehicle">Vehículo</option>
                <option value="other">Otro</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
            <input type="text" name="address" value="{{ old('address') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        </div>
        <div class="flex items-end">
            <button class="w-full bg-gray-900 text-white px-4 py-2.5 rounded-lg text-sm font-semibold">Guardar</button>
        </div>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 md:col-span-2">
            <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-gray-900">
            Ubicación principal
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="active" value="1" checked class="rounded border-gray-300 text-gray-900">
            Activa
        </label>
    </form>
    @endcan

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-center">Nombre</th>
                    <th class="px-4 py-3 text-center">Tipo</th>
                    <th class="px-4 py-3 text-center">Dirección</th>
                    <th class="px-4 py-3 text-center">Stocks</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($locations as $location)
                    <tr>
                        <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ $location->name }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $location->type }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $location->address ?: '-' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $location->stocks_count }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($location->is_default)
                                <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded-full text-xs font-semibold">Principal</span>
                            @elseif($location->active)
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-semibold">Activa</span>
                            @else
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs font-semibold">Oculta</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No hay ubicaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $locations->links() }}
</div>
@endsection
