@if ($hasStockMismatch)
    <span class="inline-flex shrink-0 bg-amber-100 text-amber-700 px-2 py-1 rounded-full text-xs font-medium"
        title="La suma por ubicaciones ({{ $locationTotal }}) no coincide con el stock">Revisar</span>
@elseif ($isOutOfStock)
    <span class="inline-flex shrink-0 bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-medium">Agotado</span>
@elseif ($isLowStock)
    <span class="inline-flex shrink-0 bg-amber-100 text-amber-700 px-2 py-1 rounded-full text-xs font-medium">Bajo stock</span>
@elseif ($variant->active && ($product?->active ?? false))
    <span class="inline-flex shrink-0 bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Activo</span>
@else
    <span class="inline-flex shrink-0 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs font-medium">Oculto</span>
@endif
