@php
    $currentCost = (float) old('cost_price', $catalogVariant?->cost_price ?? 0);
    $currentPrice = (float) old('price', $catalogVariant?->price ?? 0);
    $computedMargin = $currentCost > 0 ? round((($currentPrice - $currentCost) / $currentCost) * 100, 2) : 40;
    $selectedMargin = (string) old('profit_margin_percentage', $computedMargin);
    $presetMargins = ['20', '30', '40', '50'];
    $presentationPresets = ['Unidad', '250 ml', '500 ml', '800 ml', '1 litro', 'Galon', 'Caneca', 'Caja', 'Paquete'];
    $inputClass = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-gray-300';
    $labelClass = 'mb-1 block text-xs font-semibold text-gray-700';
@endphp

<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
    <div class="space-y-4">
        <section class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Producto padre</p>
            <label class="{{ $labelClass }}">Producto *</label>
            <select name="catalog_item_id" id="presentation_item_id"
                class="{{ $inputClass }} bg-white @error('catalog_item_id') border-red-400 bg-red-50 @enderror">
                @if ($showEmptyItemOption)
                    <option value="">Selecciona un producto</option>
                @endif
                @foreach($items as $item)
                    @php
                        $prefixSource = $item->type?->slug ?: $item->type?->name ?: $item->name;
                        $prefix = \Illuminate\Support\Str::substr(str_replace('-', '', \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($prefixSource))), 0, 4) ?: 'PRES';
                    @endphp
                    <option value="{{ $item->id }}" data-prefix="{{ $prefix }}"
                        {{ old('catalog_item_id', $selectedItemId ?? $catalogVariant?->catalog_item_id) == $item->id ? 'selected' : '' }}>
                        {{ $item->type?->name ?? 'Seccion' }} / {{ $item->name }}
                    </option>
                @endforeach
            </select>
            @error('catalog_item_id')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </section>

        <section class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Presentacion</p>
            <p class="mb-3 text-xs text-gray-500">Define como se vendera o contara este producto.</p>

            <label class="{{ $labelClass }}">Presentacion *</label>
            <input type="text" name="name" id="presentation_name"
                value="{{ old('name', $catalogVariant?->name) }}"
                class="{{ $inputClass }} bg-white @error('name') border-red-400 bg-red-50 @enderror"
                placeholder="Ej: 1 litro, Galon, Caneca">
            @error('name')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($presentationPresets as $preset)
                    <button type="button" data-presentation-preset="{{ $preset }}"
                        class="inline-flex h-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 transition hover:bg-gray-100">
                        {{ $preset }}
                    </button>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Precios</p>
            <p class="mb-3 text-xs text-gray-500">Calcula la ganancia desde el precio de venta o el precio de venta desde la ganancia.</p>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelClass }}">Precio de compra</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-sm font-semibold text-gray-400">$</span>
                        <input type="number" name="cost_price" id="presentation_cost_price"
                            value="{{ old('cost_price', $catalogVariant?->cost_price) }}" step="0.01" min="0"
                            class="{{ $inputClass }} bg-white pl-8 @error('cost_price') border-red-400 bg-red-50 @enderror"
                            placeholder="0.00">
                    </div>
                    @error('cost_price')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">Ganancia</label>
                    <div class="relative">
                        <input type="text" id="presentation_margin_input"
                            value="{{ $selectedMargin !== '' ? rtrim(rtrim(number_format((float) $selectedMargin, 2, '.', ''), '0'), '.') . '%' : '' }}"
                            inputmode="decimal"
                            class="{{ $inputClass }} bg-white pr-10 @error('profit_margin_percentage') border-red-400 bg-red-50 @enderror"
                            placeholder="Ej: 40%">
                        <button type="button" id="presentation_margin_toggle" aria-label="Mostrar porcentajes guardados"
                            aria-expanded="false" aria-controls="presentation_margin_options"
                            class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-500 hover:text-gray-900">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div id="presentation_margin_options" role="listbox" aria-label="Porcentajes guardados"
                            class="absolute left-0 right-0 top-full z-20 mt-1 hidden overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                            @foreach ($presetMargins as $margin)
                                <button type="button" role="option" data-margin-option="{{ $margin }}"
                                    class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
                                    {{ $margin }}%
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" name="profit_margin_percentage" id="presentation_margin" value="{{ $selectedMargin }}">
                    @error('profit_margin_percentage')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">Precio de venta</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-sm font-semibold text-gray-400">$</span>
                        <input type="number" name="price" id="presentation_sale_price"
                            value="{{ old('price', $catalogVariant?->price) }}" step="0.01" min="0"
                            class="{{ $inputClass }} bg-white pl-8 @error('price') border-red-400 bg-red-50 @enderror"
                            placeholder="0.00">
                    </div>
                    @error('price')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>
    </div>

    <aside class="space-y-4">
        <section class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Inventario</p>
            <p class="mb-3 text-xs text-gray-500">Cada presentacion tiene su propio SKU y stock.</p>

            <div class="space-y-3">
                <div>
                    <label class="{{ $labelClass }}">SKU automatico</label>
                    <input type="text" name="sku" id="presentation_sku"
                        value="{{ old('sku', $catalogVariant?->sku) }}"
                        class="{{ $inputClass }} bg-white text-gray-500 @error('sku') border-red-400 bg-red-50 @enderror"
                        readonly>
                    @error('sku')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">Stock inicial</label>
                    <input type="number" name="stock" value="{{ old('stock', $catalogVariant?->stock) }}" min="0"
                        class="{{ $inputClass }} bg-white @error('stock') border-red-400 bg-red-50 @enderror"
                        placeholder="0">
                    @error('stock')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="{{ $labelClass }}">Stock minimo</label>
                    <input type="number" name="min_stock" value="{{ old('min_stock', $catalogVariant?->min_stock ?? 0) }}" min="0"
                        class="{{ $inputClass }} bg-white @error('min_stock') border-red-400 bg-red-50 @enderror"
                        placeholder="0">
                    @error('min_stock')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-gray-100 bg-gray-50 p-4">
            <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Estado</p>
            <div class="space-y-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-100 bg-white p-3">
                    <input type="checkbox" name="active" value="1" {{ old('active', $catalogVariant?->active ?? true) ? 'checked' : '' }}
                        class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                    <span>
                        <span class="block text-sm font-semibold text-gray-700">Activa</span>
                        <span class="block text-xs text-gray-400">Disponible para ventas e inventario</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-100 bg-white p-3">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $catalogVariant?->is_default ?? false) ? 'checked' : '' }}
                        class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                    <span>
                        <span class="block text-sm font-semibold text-gray-700">Principal</span>
                        <span class="block text-xs text-gray-400">Se selecciona primero en web y ventas</span>
                    </span>
                </label>
            </div>
        </section>
    </aside>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const skuInput = document.getElementById('presentation_sku');
                const itemSelect = document.getElementById('presentation_item_id');
                const costInput = document.getElementById('presentation_cost_price');
                const marginTextInput = document.getElementById('presentation_margin_input');
                const marginInput = document.getElementById('presentation_margin');
                const marginToggle = document.getElementById('presentation_margin_toggle');
                const marginOptions = document.getElementById('presentation_margin_options');
                const saleInput = document.getElementById('presentation_sale_price');
                const nameInput = document.getElementById('presentation_name');

                function randomSku() {
                    const selected = itemSelect?.selectedOptions?.[0];
                    const prefix = selected?.dataset?.prefix || 'PRES';
                    const date = new Date();
                    const ymd = String(date.getFullYear()).slice(-2) +
                        String(date.getMonth() + 1).padStart(2, '0') +
                        String(date.getDate()).padStart(2, '0');
                    const token = Math.random().toString(36).substring(2, 7).toUpperCase();

                    return `${prefix}-${ymd}-${token}`;
                }

                let lastEditedPriceField = saleInput?.value ? 'sale' : 'margin';

                function formatDecimal(value) {
                    if (!Number.isFinite(value)) return '';

                    return value.toFixed(2);
                }

                function formatPercentage(value) {
                    if (!Number.isFinite(value)) return '';

                    return String(Number(value.toFixed(2)));
                }

                function normalizePercentage(value) {
                    const normalized = String(value || '')
                        .replace('%', '')
                        .replace(',', '.')
                        .trim();
                    const parsed = parseFloat(normalized);

                    return Number.isFinite(parsed) && parsed >= -100 ? parsed : 0;
                }

                function setMargin(margin) {
                    if (!marginInput) return;

                    const normalizedMargin = formatDecimal(Math.max(-100, margin));
                    marginInput.value = normalizedMargin;
                    if (marginTextInput) marginTextInput.value = `${formatPercentage(Math.max(-100, margin))}%`;
                }

                function calculatePrice() {
                    const cost = parseFloat(costInput?.value || '0');
                    const margin = normalizePercentage(marginInput?.value);

                    if (!saleInput) return;

                    if (cost > 0) {
                        const sale = cost + (cost * margin / 100);
                        saleInput.value = sale.toFixed(2);
                    } else if (!saleInput.value) {
                        saleInput.value = '0.00';
                    }
                }

                function calculateMargin() {
                    const cost = parseFloat(costInput?.value || '0');
                    const sale = parseFloat(saleInput?.value || '0');

                    if (cost <= 0 || !saleInput?.value) return;

                    setMargin(((sale - cost) / cost) * 100);
                }

                function syncMargin(shouldCalculatePrice = true) {
                    if (!marginTextInput || !marginInput) return;

                    if (shouldCalculatePrice) {
                        lastEditedPriceField = 'margin';
                    }

                    const margin = normalizePercentage(marginTextInput.value);
                    marginInput.value = formatDecimal(margin);

                    if (shouldCalculatePrice) {
                        calculatePrice();
                    }
                }

                function syncFromCost() {
                    if (lastEditedPriceField === 'sale') {
                        calculateMargin();
                        return;
                    }

                    calculatePrice();
                }

                function closeMarginOptions() {
                    marginOptions?.classList.add('hidden');
                    marginToggle?.setAttribute('aria-expanded', 'false');
                }

                if (skuInput && !skuInput.value) {
                    skuInput.value = randomSku();
                }

                itemSelect?.addEventListener('change', () => {
                    if (skuInput) skuInput.value = randomSku();
                });

                document.querySelectorAll('[data-presentation-preset]').forEach((button) => {
                    button.addEventListener('click', () => {
                        if (nameInput) nameInput.value = button.dataset.presentationPreset || '';
                        nameInput?.focus();
                    });
                });

                costInput?.addEventListener('input', syncFromCost);
                marginTextInput?.addEventListener('input', () => syncMargin());
                marginTextInput?.addEventListener('change', () => {
                    syncMargin();
                    if (marginTextInput.value && !marginTextInput.value.includes('%')) {
                        marginTextInput.value = `${formatPercentage(normalizePercentage(marginTextInput.value))}%`;
                    }
                });
                marginToggle?.addEventListener('click', () => {
                    const isOpen = !marginOptions?.classList.contains('hidden');
                    if (isOpen) {
                        closeMarginOptions();
                        return;
                    }

                    marginOptions?.classList.remove('hidden');
                    marginToggle.setAttribute('aria-expanded', 'true');
                });
                marginOptions?.querySelectorAll('[data-margin-option]').forEach((option) => {
                    option.addEventListener('click', () => {
                        if (marginTextInput) marginTextInput.value = `${option.dataset.marginOption}%`;
                        syncMargin();
                        closeMarginOptions();
                        marginTextInput?.focus();
                    });
                });
                document.addEventListener('click', (event) => {
                    if (!marginOptions?.parentElement?.contains(event.target)) closeMarginOptions();
                });
                saleInput?.addEventListener('input', () => {
                    lastEditedPriceField = 'sale';
                    calculateMargin();
                });
                syncMargin(false);
                if (lastEditedPriceField === 'sale') {
                    calculateMargin();
                } else {
                    calculatePrice();
                }
            });
        </script>
    @endpush
@endonce
