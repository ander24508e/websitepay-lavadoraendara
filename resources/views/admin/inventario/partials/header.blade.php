<div class="mb-6 space-y-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <span
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-900 shadow-sm">
                <x-heroicon-o-archive-box class="h-6 w-6" />
            </span>
            <div class="min-w-0">
                <h3 class="text-2xl sm:text-3xl font-medium leading-tight text-gray-900">Inventario</h3>
                <p class="mt-1.5 max-w-3xl text-sm text-gray-500">Gestiona el stock de tus productos.</p>
            </div>
        </div>

        @if ($hasProductTypes)
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                @if ($newMovementUrl)
                <a href="{{ $newMovementUrl }}"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    <x-heroicon-o-archive-box class="h-5 w-5" />
                    Movimiento
                </a>
                @endif

                @if (count($moreActions) > 0)
                <details class="relative">
                    <summary
                        class="inline-flex h-11 cursor-pointer list-none items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 [&::-webkit-details-marker]:hidden"
                        aria-label="Más acciones">
                        <x-heroicon-o-ellipsis-horizontal class="h-5 w-5" />
                        <span>Más acciones</span>
                    </summary>
                    <div
                        class="absolute right-0 z-20 mt-2 w-64 overflow-hidden rounded-lg border border-gray-200 bg-white py-2 shadow-lg">
                        @foreach ($moreActions as $tool)
                            <a href="{{ $tool['route'] }}"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                title="{{ $tool['title'] }}">
                                @switch($tool['icon'])
                                    @case('building-storefront')
                                        <x-heroicon-o-building-storefront class="h-4 w-4 text-gray-400" />
                                    @break

                                    @case('users')
                                        <x-heroicon-o-users class="h-4 w-4 text-gray-400" />
                                    @break

                                    @case('shopping-bag')
                                        <x-heroicon-o-shopping-bag class="h-4 w-4 text-gray-400" />
                                    @break

                                    @case('arrows-right-left')
                                        <x-heroicon-o-arrows-right-left class="h-4 w-4 text-gray-400" />
                                    @break

                                    @case('arrow-path-rounded-square')
                                        <x-heroicon-o-arrow-path-rounded-square class="h-4 w-4 text-gray-400" />
                                    @break

                                    @case('clipboard-document-check')
                                        <x-heroicon-o-clipboard-document-check class="h-4 w-4 text-gray-400" />
                                    @break

                                    @case('chart-bar')
                                        <x-heroicon-o-chart-bar class="h-4 w-4 text-gray-400" />
                                    @break

                                    @case('lock-closed')
                                        <x-heroicon-o-lock-closed class="h-4 w-4 text-gray-400" />
                                    @break

                                    @case('arrow-up-tray')
                                        <x-heroicon-o-arrow-up-tray class="h-4 w-4 text-gray-400" />
                                    @break

                                    @case('arrow-down-tray')
                                        <x-heroicon-o-arrow-down-tray class="h-4 w-4 text-gray-400" />
                                    @break
                                @endswitch
                                <span>{{ $tool['title'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
                @endif

                @if ($primaryProductUrl)
                <a href="{{ $primaryProductUrl }}"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gray-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-700">
                    <x-heroicon-o-plus class="h-5 w-5" />
                    Agregar producto
                </a>
                @endif
            </div>
        @endif
    </div>

    @if ($hasProductTypes)
        <form method="GET" action="{{ route('admin.inventario.index') }}" class="relative">
            <input type="hidden" name="catalog_type_id" value="{{ $selectedTypeId ?: '' }}">
            <x-heroicon-o-magnifying-glass
                class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
            <input type="search" name="q" value="{{ request('q') }}"
                class="h-12 w-full rounded-lg border border-gray-200 bg-white py-3 pl-11 pr-24 text-sm text-gray-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-gray-300"
                placeholder="Buscar producto, negocio, categoría, presentación o SKU">
            <button
                class="absolute right-1.5 top-1/2 inline-flex h-9 -translate-y-1/2 items-center justify-center rounded-md bg-gray-100 px-4 text-xs font-semibold text-gray-700 transition hover:bg-gray-200">
                Buscar
            </button>
        </form>
    @endif
</div>
