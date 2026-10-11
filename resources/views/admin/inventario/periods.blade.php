@extends('layouts.admin')

@section('title', 'Cierres de Inventario')

@section('content')
<div class="mx-auto w-full max-w-full overflow-x-hidden px-3 pb-4 sm:px-6 space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Cierres de inventario</h2>
            <p class="text-sm text-gray-500 mt-1">Guarda un resumen de unidades y valor al cerrar un periodo.</p>
        </div>
        <a href="{{ route('admin.inventario.reports') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">Reportes</a>
    </div>

    @can('inventory.periods.create')
    <form method="POST" action="{{ route('admin.inventario.periods.store') }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ now()->startOfMonth()->toDateString() }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" name="date_to" value="{{ now()->endOfMonth()->toDateString() }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nota</label>
                <input type="text" name="notes" maxlength="1000" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm" placeholder="Ej: cierre mensual">
            </div>
        </div>
        <button class="bg-gray-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">Cerrar periodo</button>
    </form>
    @endcan

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-center">Periodo</th>
                    <th class="px-4 py-3 text-center">Presentaciones</th>
                    <th class="px-4 py-3 text-center">Unidades</th>
                    <th class="px-4 py-3 text-center">Valor</th>
                    <th class="px-4 py-3 text-center">Usuario</th>
                    <th class="px-4 py-3 text-center">Cerrado</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($periods as $period)
                    <tr>
                        <td class="px-4 py-3 text-center font-semibold text-gray-800">
                            {{ $period->date_from?->format('d/m/Y') }} - {{ $period->date_to?->format('d/m/Y') }}
                            @if($period->notes)
                                <br><span class="text-xs text-gray-400">{{ $period->notes }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">{{ $period->variants_count }}</td>
                        <td class="px-4 py-3 text-center">{{ $period->total_units }}</td>
                        <td class="px-4 py-3 text-center font-semibold">${{ number_format((float) $period->total_value, 2) }}</td>
                        <td class="px-4 py-3 text-center">{{ $period->user?->name ?? 'Sistema' }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $period->closed_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hay cierres registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $periods->links() }}
</div>
@endsection
