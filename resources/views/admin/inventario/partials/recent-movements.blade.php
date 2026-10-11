<aside class="{{ $panelClass }}">
    <div class="px-4 sm:px-5 py-4 border-b border-gray-200/70 space-y-3">
        <div>
            <h3 class="font-semibold text-gray-900">Movimientos</h3>
            <p class="text-xs text-gray-400 mt-1">Entradas, salidas y ajustes recientes.</p>
        </div>
        <form method="GET" action="{{ route('admin.inventario.index') }}"
            class="grid grid-cols-1 sm:grid-cols-3 2xl:grid-cols-1 gap-2">
            <input type="hidden" name="catalog_type_id" value="{{ $selectedTypeId ?: '' }}">
            <input type="hidden" name="q" value="{{ request('q') }}">
            <select name="movement_type"
                class="rounded-lg border border-gray-200 bg-white py-2.5 px-3 text-xs text-gray-700">
                <option value="">Todos</option>
                <option value="in" {{ $movementType === 'in' ? 'selected' : '' }}>Entradas</option>
                <option value="out" {{ $movementType === 'out' ? 'selected' : '' }}>Salidas</option>
                <option value="adjust" {{ $movementType === 'adjust' ? 'selected' : '' }}>Ajustes</option>
            </select>
            <input type="date" name="movement_date_from" value="{{ $movementDateFrom }}"
                class="rounded-lg border border-gray-200 bg-white py-2.5 px-3 text-xs text-gray-700">
            <input type="date" name="movement_date_to" value="{{ $movementDateTo }}"
                class="rounded-lg border border-gray-200 bg-white py-2.5 px-3 text-xs text-gray-700">
            <button
                class="sm:col-span-3 2xl:col-span-1 bg-gray-100 text-gray-700 rounded-lg py-2.5 text-xs font-semibold hover:bg-gray-200 transition">Filtrar
                movimientos</button>
        </form>
    </div>
    <div class="max-h-[620px] overflow-y-auto">
        @forelse($recentMovements as $movement)
            <div class="px-4 py-3 border-b border-gray-100">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 break-words">
                            {{ $movement->variant->item->name ?? '-' }}</p>
                        <p class="text-xs text-gray-500 break-words">{{ $movement->variant->name ?? '-' }}</p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        @can('inventory.update')
                        <a href="{{ route('admin.inventario.movements.edit', $movement) }}"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-700 hover:bg-gray-100 transition"
                            title="Editar movimiento" aria-label="Editar movimiento">
                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                        </a>
                        @endcan
                        @can('inventory.void')
                        <form method="POST" action="{{ route('admin.inventario.movements.destroy', $movement) }}"
                            onsubmit="return confirm('¿Anular este movimiento y registrar una reversa?');">
                            @csrf
                            @method('DELETE')
                            <button
                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-600 hover:bg-red-50 transition"
                                title="Eliminar movimiento" aria-label="Eliminar movimiento">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
                <p class="text-xs mt-2 text-gray-500 {{ $movement->voided_at ? 'line-through text-gray-400' : '' }}">
                    <span class="font-semibold text-gray-700">{{ strtoupper($movement->type) }}</span>
                    · Cant: {{ $movement->quantity }}
                    · {{ $movement->stock_before ?? 0 }} -> {{ $movement->stock_after ?? 0 }}
                    @if ($movement->voided_at)
                        · ANULADO
                    @endif
                </p>
                <p class="text-xs text-gray-400 mt-1">{{ $movement->created_at?->format('d/m/Y H:i') }} ·
                    {{ $movement->user->name ?? 'Sistema' }}</p>
                @if ($movement->reason || $movement->reference)
                    <p class="text-xs text-gray-400 mt-1">{{ $movement->reason ?: '-' }} ·
                        {{ $movement->reference ?: '-' }}</p>
                @endif
                @if ($movement->location || $movement->fromLocation || $movement->toLocation)
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $movement->location?->name ?? ($movement->fromLocation?->name ?? '-') . '->' . ($movement->toLocation?->name ?? '-') }}
                    </p>
                @endif
            </div>
        @empty
            <div class="px-4 py-10 text-center text-gray-400 text-sm">Sin movimientos.</div>
        @endforelse
    </div>
    @if ($recentMovements->hasPages())
        <div class="p-3 border-t border-gray-100">{{ $recentMovements->links() }}</div>
    @endif
</aside>
