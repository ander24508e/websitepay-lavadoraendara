@php
    $alertCount = (int) $inventoryStats['out'] + (int) $inventoryStats['low'];
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">
    <div class="bg-white rounded-lg border border-gray-200/70 shadow-sm p-4">
        <p class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Presentaciones</p>
        <p class="text-2xl font-bold text-gray-900 mt-2">{{ $inventoryStats['variants'] }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200/70 shadow-sm p-4">
        <p class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Unidades</p>
        <p class="text-2xl font-bold text-gray-900 mt-2">{{ $inventoryStats['units'] }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200/70 shadow-sm p-4">
        <p class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Valor</p>
        <p class="text-2xl font-bold text-gray-900 mt-2 break-words">
            {{ $canViewCosts ? '$' . number_format($inventoryStats['value'], 2) : 'Restringido' }}</p>
    </div>
    <div class="bg-white rounded-lg border border-gray-200/70 shadow-sm p-4">
        <p class="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">Alertas</p>
        <p class="text-2xl font-bold mt-2 {{ $alertCount > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $alertCount }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $inventoryStats['out'] }} agotados · {{ $inventoryStats['low'] }} bajo stock</p>
    </div>
</div>
