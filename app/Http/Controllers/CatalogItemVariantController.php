<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\CatalogItem;
use App\Models\CatalogItemVariant;
use App\Models\CatalogType;
use App\Models\Empresa;
use App\Support\Permissions\CatalogPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogItemVariantController extends Controller
{
    public function index(Request $request)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_PRODUCTS, 'view');
        $empresa = $this->getOrCreateEmpresa();
        $search = trim((string) $request->query('q', ''));
        $selectedItemId = (int) $request->query('catalog_item_id', 0);
        $baseQuery = CatalogItemVariant::query()
            ->whereHas('item', function ($query) use ($empresa) {
                $query->where('empresa_id', $empresa->id)
                    ->whereHas('type', function ($typeQuery) {
                        $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
                    });
            })
            ->when($selectedItemId > 0, fn ($query) => $query->where('catalog_item_id', $selectedItemId));

        $variants = (clone $baseQuery)
            ->with(['item.type', 'item.category'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('item', function ($itemQuery) use ($search) {
                            $itemQuery->where('name', 'like', "%{$search}%")
                                ->orWhereHas('type', fn($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"));
                        });
                });
            })
            ->ordered()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('active', true)->count(),
            'default' => (clone $baseQuery)->where('is_default', true)->count(),
            'with_stock' => (clone $baseQuery)->whereNotNull('stock')->count(),
            'low_stock' => (clone $baseQuery)
                ->whereNotNull('stock')
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('min_stock', '>', 0)
                ->count(),
        ];

        return view('admin.catalog.variants.index', compact('empresa', 'variants', 'stats', 'selectedItemId'));
    }

    public function create(Request $request)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_PRODUCTS, 'update');
        $empresa = $this->getOrCreateEmpresa();
        $selectedItemId = (int) $request->query('catalog_item_id', 0);
        $selectedTypeId = (int) $request->query('catalog_type_id', 0);
        $items = CatalogItem::query()
            ->where('empresa_id', $empresa->id)
            ->whereHas('type', function ($typeQuery) {
                $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
            })
            ->when($selectedItemId > 0, fn ($query) => $query->whereKey($selectedItemId))
            ->when($selectedTypeId > 0, fn ($query) => $query->where('catalog_type_id', $selectedTypeId))
            ->with(['type', 'category'])
            ->ordered()
            ->get();
        $returnToType = (bool) $request->boolean('return_to_type', $selectedTypeId > 0);

        return view('admin.catalog.variants.create', compact('empresa', 'items', 'selectedItemId', 'selectedTypeId', 'returnToType'));
    }

    public function store(Request $request)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_PRODUCTS, 'update');
        $empresa = $this->getOrCreateEmpresa();

        $data = $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'profit_margin_percentage' => ['nullable', 'numeric', 'min:-100', 'max:10000'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'redirect_to_item' => ['nullable', 'boolean'],
        ]);

        $item = CatalogItem::query()
            ->with('type')
            ->where('empresa_id', $empresa->id)
            ->whereHas('type', function ($typeQuery) {
                $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
            })
            ->findOrFail($data['catalog_item_id']);

        $variant = CatalogItemVariant::create([
            'catalog_item_id' => $item->id,
            'name' => trim($data['name']),
            'sku' => $this->resolveSku($data['sku'] ?? null, $item, $data['name']),
            'price' => $this->resolveSalePrice($data['cost_price'] ?? null, $data['profit_margin_percentage'] ?? null, $data['price'] ?? null),
            'cost_price' => $data['cost_price'] ?? null,
            'stock' => $data['stock'] ?? null,
            'min_stock' => (int) ($data['min_stock'] ?? 0),
            'active' => $request->boolean('active', true),
            'is_default' => $request->boolean('is_default'),
        ]);

        $this->syncDefaultVariant($variant);

        NotificationHelper::success('Presentacion de producto creada correctamente.');

        if ($request->boolean('redirect_to_type')) {
            return redirect()->route('admin.catalog-types.show', $item->catalog_type_id);
        }

        if ($request->boolean('redirect_to_item')) {
            return redirect()->route('admin.catalog-items.show', $item);
        }

        return redirect()->route('admin.catalog-variants.index');
    }

    public function show(CatalogItemVariant $catalogVariant)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_PRODUCTS, 'view');
        $catalogVariant->load(['item.type', 'item.category']);

        if (!$this->isProductVariant($catalogVariant)) {
            NotificationHelper::error('Las presentaciones solo aplican a productos.');
            return redirect()->route('admin.catalog.index');
        }

        return redirect()->route('admin.catalog-variants.edit', $catalogVariant);
    }

    public function edit(Request $request, CatalogItemVariant $catalogVariant)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_PRODUCTS, 'update');
        $catalogVariant->load('item.type');

        if (!$this->isProductVariant($catalogVariant)) {
            NotificationHelper::error('Las presentaciones solo aplican a productos.');
            return redirect()->route('admin.catalog.index');
        }

        $items = CatalogItem::query()
            ->where('empresa_id', $catalogVariant->item->empresa_id)
            ->whereHas('type', function ($typeQuery) {
                $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
            })
            ->with(['type', 'category'])
            ->ordered()
            ->get();

        $redirectToItem = $request->boolean('redirect_to_item');

        return view('admin.catalog.variants.edit', compact('catalogVariant', 'items', 'redirectToItem'));
    }

    public function update(Request $request, CatalogItemVariant $catalogVariant)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_PRODUCTS, 'update');
        $catalogVariant->load('item.type');

        if (!$this->isProductVariant($catalogVariant)) {
            NotificationHelper::error('Las presentaciones solo aplican a productos.');
            return redirect()->route('admin.catalog.index');
        }

        $empresaId = $catalogVariant->item->empresa_id;

        $data = $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'profit_margin_percentage' => ['nullable', 'numeric', 'min:-100', 'max:10000'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'redirect_to_item' => ['nullable', 'boolean'],
        ]);

        $item = CatalogItem::query()
            ->with('type')
            ->where('empresa_id', $empresaId)
            ->whereHas('type', function ($typeQuery) {
                $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
            })
            ->findOrFail($data['catalog_item_id']);

        $catalogVariant->update([
            'catalog_item_id' => $item->id,
            'name' => trim($data['name']),
            'sku' => $this->resolveSku($data['sku'] ?? null, $item, $data['name'], $catalogVariant->id),
            'price' => $this->resolveSalePrice($data['cost_price'] ?? null, $data['profit_margin_percentage'] ?? null, $data['price'] ?? null),
            'cost_price' => $data['cost_price'] ?? null,
            'stock' => $data['stock'] ?? null,
            'min_stock' => (int) ($data['min_stock'] ?? 0),
            'active' => $request->boolean('active'),
            'is_default' => $request->boolean('is_default'),
        ]);

        $this->syncDefaultVariant($catalogVariant);

        NotificationHelper::success('Presentacion de producto actualizada correctamente.');

        if ($request->boolean('redirect_to_item')) {
            return redirect()->route('admin.catalog-items.show', $item);
        }

        return redirect()->route('admin.catalog-variants.index');
    }

    public function destroy(CatalogItemVariant $catalogVariant)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_PRODUCTS, 'update');
        $catalogVariant->load('item.type');

        if (!$this->isProductVariant($catalogVariant)) {
            NotificationHelper::error('Las presentaciones solo aplican a productos.');
            return redirect()->route('admin.catalog.index');
        }

        $itemId = $catalogVariant->catalog_item_id;
        $wasDefault = (bool) $catalogVariant->is_default;

        $catalogVariant->delete();

        if ($wasDefault) {
            $replacement = CatalogItemVariant::query()
                ->where('catalog_item_id', $itemId)
                ->ordered()
                ->first();

            if ($replacement) {
                $replacement->update(['is_default' => true]);
            }
        }

        NotificationHelper::success('Presentacion de producto eliminada correctamente.');

        return redirect()->route('admin.catalog-variants.index');
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

    private function syncDefaultVariant(CatalogItemVariant $variant): void
    {
        if ($variant->is_default) {
            CatalogItemVariant::query()
                ->where('catalog_item_id', $variant->catalog_item_id)
                ->whereKeyNot($variant->id)
                ->update(['is_default' => false]);

            return;
        }

        $hasDefault = CatalogItemVariant::query()
            ->where('catalog_item_id', $variant->catalog_item_id)
            ->where('is_default', true)
            ->exists();

        if (!$hasDefault) {
            $variant->update(['is_default' => true]);
        }
    }

    private function isProductVariant(CatalogItemVariant $variant): bool
    {
        return ($variant->item?->type?->business_model ?? CatalogType::BUSINESS_MODEL_SERVICES) === CatalogType::BUSINESS_MODEL_PRODUCTS;
    }

    private function resolveSalePrice(mixed $cost, mixed $margin, mixed $fallback): ?float
    {
        if ($fallback !== null && $fallback !== '') {
            return round((float) $fallback, 2);
        }

        $cost = is_numeric($cost) ? (float) $cost : 0;
        $margin = is_numeric($margin) ? (float) $margin : 0;

        if ($cost > 0) {
            return round($cost + ($cost * $margin / 100), 2);
        }

        return null;
    }

    private function resolveSku(?string $sku, CatalogItem $item, string $presentationName, ?int $ignoreVariantId = null): string
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

        $prefixSource = $item->type?->slug ?: $item->type?->name ?: $item->name ?: $presentationName;
        $prefix = Str::upper(Str::substr(Str::slug($prefixSource, ''), 0, 4)) ?: 'PRES';

        do {
            $candidate = $prefix . '-' . now()->format('ymd') . '-' . Str::upper(Str::random(5));
        } while (CatalogItemVariant::query()->where('sku', $candidate)->exists());

        return $candidate;
    }
}
