<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\CatalogItemSupply;
use App\Models\CatalogItemVariant;
use App\Models\CatalogType;
use App\Models\Empresa;
use App\Models\VehicleType;
use App\Support\Permissions\CatalogPermission;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogItemController extends Controller
{
    public function create(Request $request)
    {
        $empresa = $this->getOrCreateEmpresa();
        $selectedTypeId = (int) $request->query('catalog_type_id', old('catalog_type_id', 0));
        $selectedCategoryId = (int) $request->query('catalog_category_id', old('catalog_category_id', 0));
        $fromInventory = $request->boolean('inventory');
        $selectedType = null;
        $selectedCategory = null;

        if ($selectedCategoryId > 0 && $selectedTypeId === 0) {
            $selectedCategory = CatalogCategory::query()
                ->where('empresa_id', $empresa->id)
                ->with('type')
                ->find($selectedCategoryId);

            if ($selectedCategory) {
                $selectedTypeId = (int) $selectedCategory->catalog_type_id;
                $selectedType = $selectedCategory->type;
            }
        }

        if ($selectedTypeId > 0 && !$selectedType) {
            $selectedType = CatalogType::query()
                ->where('empresa_id', $empresa->id)
                ->find($selectedTypeId);
        }

        if ($fromInventory && (!$selectedType || !$this->isProductBusiness($selectedType))) {
            // Sin negocio valido el usuario lo elige en el formulario.
            $selectedType = null;
            $selectedTypeId = 0;
            $selectedCategoryId = 0;
            $selectedCategory = null;
        }

        CatalogPermission::authorize(
            $fromInventory || ($selectedType && $this->isProductBusiness($selectedType))
                ? CatalogType::BUSINESS_MODEL_PRODUCTS
                : CatalogType::BUSINESS_MODEL_SERVICES,
            'create'
        );

        $restrictToSelectedType = $selectedType && !$fromInventory;

        $types = CatalogType::query()
            ->where('empresa_id', $empresa->id)
            ->when($fromInventory, function ($query) {
                $query->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
            })
            ->when($restrictToSelectedType, fn ($query) => $query->whereKey($selectedType->id))
            ->ordered()
            ->get();

        $categories = CatalogCategory::query()
            ->where('empresa_id', $empresa->id)
            ->when($fromInventory, function ($query) use ($types) {
                $query->whereIn('catalog_type_id', $types->pluck('id'));
            })
            ->when(!$fromInventory && $selectedType, fn ($query) => $query->where('catalog_type_id', $selectedType->id))
            ->with('type')
            ->ordered()
            ->get();
        $returnToCategory = (bool) $request->boolean('return_to_category', false);
        $returnToType = (bool) $request->boolean('return_to_type', $selectedTypeId > 0 && !$returnToCategory);
        $vehicleTypes = $this->getActiveVehicleTypes();
        $supplyVariants = $this->getSupplyVariants($empresa->id);

        $view = $fromInventory || ($selectedType && $this->isProductBusiness($selectedType))
            ? 'admin.catalog.items.create-product'
            : 'admin.catalog.items.create-service';

        return view($view, compact(
            'empresa',
            'types',
            'categories',
            'selectedTypeId',
            'selectedCategoryId',
            'selectedType',
            'selectedCategory',
            'returnToType',
            'returnToCategory',
            'fromInventory',
            'vehicleTypes',
            'supplyVariants'
        ));
    }

    public function store(Request $request)
    {
        $empresa = $this->getOrCreateEmpresa();

        $data = $request->validate([
            'catalog_type_id' => ['required', 'integer', 'exists:catalog_types,id'],
            'catalog_category_id' => ['nullable', 'integer', 'exists:catalog_categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:255'],
            'new_category_description' => ['nullable', 'string', 'max:1000'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'active' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'purchasable' => ['nullable', 'boolean'],
            'reservable' => ['nullable', 'boolean'],
            'uses_inventory' => ['nullable', 'boolean'],
            'redirect_to_inventory' => ['nullable', 'boolean'],
            'redirect_to_category' => ['nullable', 'boolean'],
            'next_action' => ['nullable', 'string', 'in:presentation'],
            'vehicle_type_prices' => ['nullable', 'array'],
            'vehicle_type_prices.*' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'vehicle_type_durations' => ['nullable', 'array'],
            'vehicle_type_durations.*' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'vehicle_type_descriptions' => ['nullable', 'array'],
            'vehicle_type_descriptions.*' => ['nullable', 'string', 'max:1000'],
            'vehicle_type_active' => ['nullable', 'array'],
            'supplies' => ['nullable', 'array'],
            'supplies.*.catalog_item_variant_id' => ['nullable', 'integer', 'exists:catalog_item_variants,id'],
            'supplies.*.quantity' => ['nullable', 'numeric', 'min:0.001', 'max:999999.999'],
            'supplies.*.unit' => ['nullable', 'string', 'max:30'],
        ]);

        $type = CatalogType::query()
            ->where('empresa_id', $empresa->id)
            ->findOrFail($data['catalog_type_id']);

        CatalogPermission::authorize($type->business_model, 'create');

        if ($request->boolean('redirect_to_inventory') && !$this->isProductBusiness($type)) {
            NotificationHelper::error('Inventario solo permite crear productos de negocios tipo producto.');
            return redirect()->back()->withInput();
        }

        $category = $this->resolveCategory($empresa->id, $type->id, $data['catalog_category_id'] ?? null);

        if ($this->isProductBusiness($type) && $this->cleanInput($data['new_category_name'] ?? null)) {
            $category = $this->createInlineCategory(
                $empresa->id,
                $type->id,
                $data['new_category_name'],
                $data['new_category_description'] ?? null
            );
        }

        if ($this->isProductBusiness($type)) {
            $data['base_price'] = null;
            $data['duration_minutes'] = null;
        }

        $slug = $this->resolveSlug($empresa->id, $data['name'], $data['slug'] ?? null);

        $behavior = $this->resolveBehaviorForType($type, $request);

        $payload = [
            'empresa_id' => $empresa->id,
            'catalog_type_id' => $type->id,
            'catalog_category_id' => $category?->id,
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $this->cleanInput($data['description'] ?? null),
            'base_price' => $this->isProductBusiness($type) ? null : ($data['base_price'] ?? null),
            'duration_minutes' => $this->isProductBusiness($type) ? null : ($data['duration_minutes'] ?? null),
            'active' => $request->boolean('active', true),
            'featured' => $request->boolean('featured'),
            'purchasable' => $behavior['purchasable'],
            'reservable' => $behavior['reservable'],
            'uses_inventory' => $behavior['uses_inventory'],
        ];

        if ($request->hasFile('image')) {
            $payload['image'] = $request->file('image')->store('catalog_items', 'public');
        }

        $item = CatalogItem::create($payload);
        $this->syncVehicleTypePrices($item, $type, $data['vehicle_type_prices'] ?? [], $data['vehicle_type_durations'] ?? [], $data['vehicle_type_descriptions'] ?? [], $data['vehicle_type_active'] ?? []);
        $this->syncSupplies($item, $type, $data['supplies'] ?? []);

        NotificationHelper::success($this->isProductBusiness($type) ? 'Producto creado correctamente.' : 'Servicio creado correctamente.');

        if ($this->isProductBusiness($type) && ($data['next_action'] ?? null) === 'presentation') {
            return redirect()->route('admin.catalog-variants.create', [
                'catalog_item_id' => $item->id,
                'catalog_type_id' => $item->catalog_type_id,
                'return_to_type' => $request->boolean('redirect_to_type') ? 1 : null,
                'return_to_category' => $request->boolean('redirect_to_category') ? 1 : null,
            ]);
        }

        if ($request->boolean('redirect_to_inventory')) {
            return redirect()->route('admin.inventario.index', ['catalog_type_id' => $type->id]);
        }

        if ($request->boolean('redirect_to_category') && $item->catalog_category_id) {
            return redirect()->route('admin.catalog-types.show', $item->catalog_type_id);
        }

        if ($request->boolean('redirect_to_type')) {
            return redirect()->route('admin.catalog-types.show', $item->catalog_type_id);
        }

        return redirect()->route('admin.catalog-types.show', $item->catalog_type_id);
    }

    public function show(Request $request, CatalogItem $catalogItem)
    {
        CatalogPermission::authorize($catalogItem->type?->business_model, 'view');
        $catalogItem->load([
            'type',
            'category',
            'variants',
            'vehicleTypePrices.vehicleType',
            'vehicleTypePrices.vehicleSpecification.brand',
            'vehicleTypePrices.vehicleSpecification.model',
            'vehicleTypePrices.vehicleSpecification.type',
            'vehicleTypePrices.supplies.variant.item',
            'supplies.variant.item',
        ]);
        $returnUrl = $this->catalogItemBackUrl($request, $catalogItem);
        $returnContext = $this->catalogItemReturnContext($request);

        return view('admin.catalog.items.show', compact('catalogItem', 'returnUrl', 'returnContext'));
    }

    public function edit(Request $request, CatalogItem $catalogItem)
    {
        CatalogPermission::authorize($catalogItem->type?->business_model, 'update');
        $types = CatalogType::query()
            ->where('empresa_id', $catalogItem->empresa_id)
            ->ordered()
            ->get();
        $categories = CatalogCategory::query()
            ->where('empresa_id', $catalogItem->empresa_id)
            ->with('type')
            ->ordered()
            ->get();
        $vehicleTypes = $this->getActiveVehicleTypes();
        $catalogItem->load([
            'type',
            'variants',
            'vehicleTypePrices.vehicleType',
            'vehicleTypePrices.vehicleSpecification.brand',
            'vehicleTypePrices.vehicleSpecification.model',
            'vehicleTypePrices.vehicleSpecification.type',
            'vehicleTypePrices.supplies.variant.item',
            'supplies.variant.item',
        ]);
        $supplyVariants = $this->getSupplyVariants($catalogItem->empresa_id);
        $returnUrl = $this->catalogItemBackUrl($request, $catalogItem, route('admin.catalog-items.show', $catalogItem));
        $returnContext = $this->catalogItemReturnContext($request);
        $view = $catalogItem->type && $this->isProductBusiness($catalogItem->type)
            ? 'admin.catalog.items.edit-product'
            : 'admin.catalog.items.edit-service';

        return view($view, compact('catalogItem', 'types', 'categories', 'vehicleTypes', 'supplyVariants', 'returnUrl', 'returnContext'));
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        $data = $request->validate([
            'catalog_type_id' => ['required', 'integer', 'exists:catalog_types,id'],
            'catalog_category_id' => ['nullable', 'integer', 'exists:catalog_categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:255'],
            'new_category_description' => ['nullable', 'string', 'max:1000'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'active' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'purchasable' => ['nullable', 'boolean'],
            'reservable' => ['nullable', 'boolean'],
            'uses_inventory' => ['nullable', 'boolean'],
            'redirect_to_inventory' => ['nullable', 'boolean'],
            'redirect_to_type' => ['nullable', 'boolean'],
            'redirect_to_category' => ['nullable', 'boolean'],
            'redirect_to_items' => ['nullable', 'boolean'],
            'vehicle_type_prices' => ['nullable', 'array'],
            'vehicle_type_prices.*' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'vehicle_type_durations' => ['nullable', 'array'],
            'vehicle_type_durations.*' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'vehicle_type_descriptions' => ['nullable', 'array'],
            'vehicle_type_descriptions.*' => ['nullable', 'string', 'max:1000'],
            'vehicle_type_active' => ['nullable', 'array'],
            'supplies' => ['nullable', 'array'],
            'supplies.*.catalog_item_variant_id' => ['nullable', 'integer', 'exists:catalog_item_variants,id'],
            'supplies.*.quantity' => ['nullable', 'numeric', 'min:0.001', 'max:999999.999'],
            'supplies.*.unit' => ['nullable', 'string', 'max:30'],
        ]);

        $type = CatalogType::query()
            ->where('empresa_id', $catalogItem->empresa_id)
            ->findOrFail($data['catalog_type_id']);

        CatalogPermission::authorize($catalogItem->type?->business_model, 'update');
        CatalogPermission::authorize($type->business_model, 'update');

        $category = $this->resolveCategory($catalogItem->empresa_id, $type->id, $data['catalog_category_id'] ?? null);

        if ($this->isProductBusiness($type) && $this->cleanInput($data['new_category_name'] ?? null)) {
            $category = $this->createInlineCategory(
                $catalogItem->empresa_id,
                $type->id,
                $data['new_category_name'],
                $data['new_category_description'] ?? null
            );
        }

        $slug = $this->resolveSlug($catalogItem->empresa_id, $data['name'], $data['slug'] ?? null, $catalogItem->id);

        $behavior = $this->resolveBehaviorForType($type, $request);
        $isProductBusiness = $this->isProductBusiness($type);

        $payload = [
            'catalog_type_id' => $type->id,
            'catalog_category_id' => $category?->id,
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $this->cleanInput($data['description'] ?? null),
            'base_price' => $isProductBusiness ? null : ($data['base_price'] ?? null),
            'duration_minutes' => $isProductBusiness ? null : ($data['duration_minutes'] ?? null),
            'active' => $request->boolean('active'),
            'featured' => $request->boolean('featured'),
            'purchasable' => $behavior['purchasable'],
            'reservable' => $behavior['reservable'],
            'uses_inventory' => $behavior['uses_inventory'],
        ];

        if ($request->hasFile('image')) {
            if ($catalogItem->image && Storage::disk('public')->exists($catalogItem->image)) {
                Storage::disk('public')->delete($catalogItem->image);
            }
            $payload['image'] = $request->file('image')->store('catalog_items', 'public');
        }

        $catalogItem->update($payload);
        $this->syncVehicleTypePrices($catalogItem, $type, $data['vehicle_type_prices'] ?? [], $data['vehicle_type_durations'] ?? [], $data['vehicle_type_descriptions'] ?? [], $data['vehicle_type_active'] ?? []);
        $this->syncSupplies($catalogItem, $type, $data['supplies'] ?? []);

        if (!$isProductBusiness) {
            $catalogItem->variants()->delete();
        }

        NotificationHelper::success($isProductBusiness ? 'Producto actualizado correctamente.' : 'Servicio actualizado correctamente.');

        if ($request->boolean('redirect_to_inventory')) {
            return redirect()->route('admin.inventario.index', ['catalog_type_id' => $catalogItem->catalog_type_id]);
        }

        if ($request->boolean('redirect_to_type')) {
            return redirect()->route('admin.catalog-types.show', $catalogItem->catalog_type_id);
        }

        if ($request->boolean('redirect_to_category') && $catalogItem->catalog_category_id) {
            return redirect()->route('admin.catalog-types.show', $catalogItem->catalog_type_id);
        }

        if ($request->boolean('redirect_to_items')) {
            return redirect()->route('admin.catalog-types.show', $catalogItem->catalog_type_id);
        }

        return redirect()->route('admin.catalog-items.show', $catalogItem);
    }

    public function destroy(Request $request, CatalogItem $catalogItem)
    {
        CatalogPermission::authorize($catalogItem->type?->business_model, 'delete');
        $catalogTypeId = $catalogItem->catalog_type_id;
        $image = $catalogItem->image;

        try {
            $catalogItem->delete();
        } catch (QueryException $exception) {
            NotificationHelper::error('No se puede eliminar este item porque ya tiene movimientos, ventas, compras o paquetes relacionados.');

            if ($request->boolean('return_to_type')) {
                return redirect()->route('admin.catalog-types.show', $catalogTypeId);
            }

            return redirect()->route('admin.catalog-types.show', $catalogTypeId);
        }

        if ($image && Storage::disk('public')->exists($image)) {
            Storage::disk('public')->delete($image);
        }

        NotificationHelper::success('Item universal eliminado correctamente.');

        if ($request->boolean('return_to_type')) {
            return redirect()->route('admin.catalog-types.show', $catalogTypeId);
        }

        return redirect()->route('admin.catalog-types.show', $catalogTypeId);
    }

    private function getOrCreateEmpresa(): Empresa
    {
        return Empresa::query()->first() ?? Empresa::create([
            'nombre' => 'Mi negocio',
        ]);
    }

    private function cleanInput(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function catalogItemReturnContext(Request $request): array
    {
        return array_filter([
            'from_inventory' => $request->boolean('from_inventory') ? 1 : null,
            'return_to_type' => $request->boolean('return_to_type') ? 1 : null,
            'return_to_category' => $request->boolean('return_to_category') ? 1 : null,
            'return_to_items' => $request->boolean('return_to_items') ? 1 : null,
        ]);
    }

    private function catalogItemBackUrl(Request $request, CatalogItem $catalogItem, ?string $defaultUrl = null): string
    {
        if ($request->boolean('from_inventory')) {
            return route('admin.inventario.index', ['catalog_type_id' => $catalogItem->catalog_type_id]);
        }

        if ($request->boolean('return_to_type') && $catalogItem->catalog_type_id) {
            return route('admin.catalog-types.show', $catalogItem->catalog_type_id);
        }

        if ($request->boolean('return_to_category') && $catalogItem->catalog_category_id) {
            return route('admin.catalog-types.show', $catalogItem->catalog_type_id);
        }

        if ($request->boolean('return_to_items')) {
            return route('admin.catalog-types.show', $catalogItem->catalog_type_id);
        }

        return $defaultUrl ?: route('admin.catalog-types.show', $catalogItem->catalog_type_id);
    }

    private function resolveCategory(int $empresaId, int $typeId, ?int $categoryId): ?CatalogCategory
    {
        if (!$categoryId) {
            return null;
        }

        return CatalogCategory::query()
            ->where('empresa_id', $empresaId)
            ->where('catalog_type_id', $typeId)
            ->findOrFail($categoryId);
    }

    private function isProductBusiness(CatalogType $type): bool
    {
        return ($type->business_model ?? CatalogType::BUSINESS_MODEL_SERVICES) === CatalogType::BUSINESS_MODEL_PRODUCTS;
    }

    private function resolveBehaviorForType(CatalogType $type, Request $request): array
    {
        if ($this->isProductBusiness($type)) {
            return [
                'purchasable' => true,
                'reservable' => false,
                'uses_inventory' => true,
            ];
        }

        return [
            'purchasable' => true,
            'reservable' => $request->boolean('reservable'),
            'uses_inventory' => false,
        ];
    }

    private function createInlineCategory(int $empresaId, int $typeId, string $name, ?string $description = null): CatalogCategory
    {
        $name = trim($name);
        $slug = Str::slug($name);
        $candidate = $slug;
        $suffix = 2;

        while (
            $candidate !== ''
            && CatalogCategory::query()
                ->where('catalog_type_id', $typeId)
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }

        return CatalogCategory::create([
            'empresa_id' => $empresaId,
            'catalog_type_id' => $typeId,
            'name' => $name,
            'slug' => $candidate ?: null,
            'description' => $this->cleanInput($description),
            'active' => true,
        ]);
    }

    private function resolveVariantSku(?string $sku, CatalogType $type, string $productName, ?int $ignoreVariantId = null): string
    {
        $cleanSku = $this->cleanInput($sku);

        if ($cleanSku) {
            $exists = CatalogItemVariant::query()
                ->where('sku', $cleanSku)
                ->when($ignoreVariantId, fn ($query) => $query->whereKeyNot($ignoreVariantId))
                ->exists();

            if (!$exists) {
                return $cleanSku;
            }
        }

        $prefix = Str::upper(Str::substr(Str::slug($type->slug ?: $type->name ?: $productName, ''), 0, 4));
        $prefix = $prefix !== '' ? $prefix : 'PROD';

        do {
            $candidate = $prefix . '-' . now()->format('ymd') . '-' . Str::upper(Str::random(5));
        } while (CatalogItemVariant::query()->where('sku', $candidate)->exists());

        return $candidate;
    }

    private function syncVehicleTypePrices(CatalogItem $item, CatalogType $type, array $prices, array $durations = [], array $descriptions = [], array $active = []): void
    {
        if ($this->isProductBusiness($type)) {
            $item->vehicleTypePrices()->delete();
            return;
        }

        $validTypeIds = VehicleType::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $submittedTypeIds = collect(array_merge(array_keys($prices), array_keys($durations), array_keys($descriptions), array_keys($active)))
            ->map(fn ($vehicleTypeId) => (int) $vehicleTypeId)
            ->unique()
            ->filter(fn ($vehicleTypeId) => in_array($vehicleTypeId, $validTypeIds, true));

        $normalized = $submittedTypeIds->mapWithKeys(function (int $vehicleTypeId) use ($prices, $durations, $descriptions, $active) {
            $price = $prices[$vehicleTypeId] ?? null;
            $duration = $durations[$vehicleTypeId] ?? null;
            $description = $descriptions[$vehicleTypeId] ?? null;

            if (($price === null || $price === '') && ($duration === null || $duration === '') && !$this->cleanInput($description)) {
                return [];
            }

            return [
                $vehicleTypeId => [
                    'price' => $price === null || $price === '' ? null : (float) $price,
                    'duration_minutes' => $duration === null || $duration === '' ? null : (int) $duration,
                    'description' => $this->cleanInput($description),
                    'active' => !array_key_exists($vehicleTypeId, $active) || (bool) $active[$vehicleTypeId],
                ],
            ];
        });

        $item->vehicleTypePrices()
            ->whereNotIn('vehicle_type_id', $normalized->keys())
            ->delete();

        foreach ($normalized as $vehicleTypeId => $values) {
            $item->vehicleTypePrices()->updateOrCreate(
                ['vehicle_type_id' => $vehicleTypeId],
                $values
            );
        }
    }

    private function getActiveVehicleTypes()
    {
        return VehicleType::query()
            ->where('active', true)
            ->ordered()
            ->get();
    }

    private function syncSupplies(CatalogItem $item, CatalogType $type, array $supplies): void
    {
        if ($this->isProductBusiness($type)) {
            $item->supplies()->delete();
            return;
        }

        $validVariantIds = CatalogItemVariant::query()
            ->whereHas('item.type', fn ($query) => $query->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $normalized = collect($supplies)
            ->map(function ($supply) {
                return [
                    'catalog_item_variant_id' => (int) ($supply['catalog_item_variant_id'] ?? 0),
                    'quantity' => $supply['quantity'] ?? null,
                    'unit' => $this->cleanInput($supply['unit'] ?? null),
                ];
            })
            ->filter(fn ($supply) => in_array($supply['catalog_item_variant_id'], $validVariantIds, true) && $supply['quantity'] !== null && $supply['quantity'] !== '')
            ->mapWithKeys(fn ($supply) => [
                $supply['catalog_item_variant_id'] => [
                    'quantity' => (float) $supply['quantity'],
                    'unit' => $supply['unit'],
                ],
            ]);

        $item->supplies()
            ->whereNull('service_vehicle_type_price_id')
            ->whereNotIn('catalog_item_variant_id', $normalized->keys())
            ->delete();

        foreach ($normalized as $variantId => $values) {
            CatalogItemSupply::updateOrCreate(
                [
                    'catalog_item_id' => $item->id,
                    'service_vehicle_type_price_id' => null,
                    'catalog_item_variant_id' => $variantId,
                ],
                $values + ['catalog_item_id' => $item->id]
            );
        }
    }

    private function getSupplyVariants(int $empresaId)
    {
        return CatalogItemVariant::query()
            ->with(['item.type'])
            ->whereHas('item', function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId)
                    ->where('active', true)
                    ->whereHas('type', fn ($typeQuery) => $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS));
            })
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    private function resolveSlug(int $empresaId, string $name, ?string $slug, ?int $ignoreId = null): ?string
    {
        $base = Str::slug($slug ?: $name);

        if ($base === '') {
            return null;
        }

        $candidate = $base;
        $suffix = 2;

        while (
            CatalogItem::query()
                ->where('empresa_id', $empresaId)
                ->where('slug', $candidate)
                ->when($ignoreId, fn($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
