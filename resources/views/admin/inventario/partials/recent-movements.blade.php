<aside class="{{ $panelClass }} self-start">
    <div class="px-4 sm:px-5 py-4 border-b border-gray-200/70 flex items-center justify-between gap-2">
        <h3 class="font-semibold text-gray-900">Últimos movimientos</h3>
        <a href="{{ route('admin.inventario.movements', array_filter(['catalog_type_id' => $selectedTypeId ?: null])) }}"
            class="text-xs font-semibold text-gray-700 hover:text-gray-900 hover:underline">Ver todos</a>
    </div>
    <div>
        @forelse($recentMovements as $movement)
            @php
                $movementLabel = ['in' => 'Entrada', 'out' => 'Salida', 'adjust' => 'Ajuste'][$movement->type] ?? ucfirst($movement->type);
                $movementSign = ['in' => '+', 'out' => '−'][$movement->type] ?? '';
            @endphp
            <div class="px-4 sm:px-5 py-3 border-b border-gray-100 last:border-b-0 {{ $movement->voided_at ? 'opacity-60' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $movement->variant->item->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $movement->variant->name ?? '—' }}</p>
                    </div>
                    <p class="shrink-0 text-right text-sm font-semibold text-gray-900 {{ $movement->voided_at ? 'line-through' : '' }}">
                        {{ $movementSign }}{{ $movement->quantity }}
                    </p>
                </div>
                <p class="mt-1 text-xs text-gray-400">
                    {{ $movementLabel }} · {{ $movement->stock_before ?? 0 }} → {{ $movement->stock_after ?? 0 }}
                    · {{ $movement->created_at?->format('d/m H:i') }}
                    @if ($movement->voided_at)
                        · Anulado
                    @endif
                </p>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-gray-400 text-sm">Sin movimientos.</div>
        @endforelse
    </div>
</aside>
