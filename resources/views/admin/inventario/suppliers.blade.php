@extends('layouts.admin')

@section('title', 'Proveedores')

@section('content')
<div class="mx-auto w-full max-w-full overflow-x-hidden px-3 pb-4 sm:px-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Proveedores</h2>
            <p class="text-sm text-gray-500 mt-1">Contactos usados para registrar compras de inventario.</p>
        </div>
        <a href="{{ route('admin.inventario.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Volver</a>
    </div>

    @can('inventory.suppliers.create')
    <form method="POST" action="{{ route('admin.inventario.suppliers.store') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 grid grid-cols-1 md:grid-cols-4 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Documento</label>
            <input type="text" name="document" value="{{ old('document') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        </div>
        <div class="md:col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
            <input type="text" name="address" value="{{ old('address') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        </div>
        <div class="flex items-end">
            <button class="w-full bg-gray-900 text-white px-4 py-2.5 rounded-lg text-sm font-semibold">Guardar</button>
        </div>
    </form>
    @endcan

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-center">Proveedor</th>
                    <th class="px-4 py-3 text-center">Contacto</th>
                    <th class="px-4 py-3 text-center">Documento</th>
                    <th class="px-4 py-3 text-center">Compras</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($suppliers as $supplier)
                    <tr>
                        <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ $supplier->name }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $supplier->phone ?: '-' }}<br>{{ $supplier->email ?: '' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $supplier->document ?: '-' }}</td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $supplier->purchases_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="{{ $supplier->active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }} px-2 py-1 rounded-full text-xs font-semibold">
                                {{ $supplier->active ? 'Activo' : 'Oculto' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No hay proveedores.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $suppliers->links() }}
</div>
@endsection
