<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\CatalogItemVariant;
use App\Models\CatalogType;
use App\Models\Empresa;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $empresa = $this->getOrCreateEmpresa();
        $q = trim((string) $request->query('q', ''));
        $selectedTypeId = (int) $request->query('catalog_type_id', 0);
        $movementType = trim((string) $request->query('movement_type', ''));
        $movementDateFrom = trim((string) $request->query('movement_date_from', ''));
        $movementDateTo = trim((string) $request->query('movement_date_to', ''));
        $canViewCosts = $this->canInventory('inventory.view_costs');

        $productTypes = CatalogType::query()
            ->where('empresa_id', $empresa->id)
            ->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS)
            ->ordered()
            ->get();

        if ($selectedTypeId > 0 && ! $productTypes->contains('id', $selectedTypeId)) {
            $selectedTypeId = 0;
        }

        $variants = CatalogItemVariant::query()
            ->with(['item.type', 'item.category', 'locationStocks.location'])
            ->whereHas('item', function ($itemQuery) use ($empresa, $selectedTypeId) {
                $itemQuery->where('empresa_id', $empresa->id)
                    ->whereHas('type', function ($typeQuery) use ($selectedTypeId) {
                        $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS)
                            ->when($selectedTypeId > 0, function ($filteredTypeQuery) use ($selectedTypeId) {
                                $filteredTypeQuery->whereKey($selectedTypeId);
                            });
                    });
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('name', 'like', "%{$q}%")
                        ->orWhere('presentation', 'like', "%{$q}%")
                        ->orWhere('specification', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%")
                        ->orWhereHas('item', function ($itemQuery) use ($q) {
                            $itemQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('description', 'like', "%{$q}%");
                        })
                        ->orWhereHas('item.type', function ($typeQuery) use ($q) {
                            $typeQuery->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('item.category', function ($categoryQuery) use ($q) {
                            $categoryQuery->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->ordered()
            ->paginate(15)
            ->withQueryString();

        $inventoryStatsQuery = CatalogItemVariant::query()
            ->whereHas('item', function ($itemQuery) use ($empresa, $selectedTypeId) {
                $itemQuery->where('empresa_id', $empresa->id)
                    ->whereHas('type', function ($typeQuery) use ($selectedTypeId) {
                        $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS)
                            ->when($selectedTypeId > 0, fn ($filteredTypeQuery) => $filteredTypeQuery->whereKey($selectedTypeId));
                    });
            });

        $statsVariants = (clone $inventoryStatsQuery)->with('locationStocks')->get();

        $inventoryStats = [
            'variants' => $statsVariants->count(),
            'units' => (int) $statsVariants->sum(fn ($variant) => (int) ($variant->stock ?? 0)),
            'value' => round($statsVariants->sum(fn ($variant) => (int) ($variant->stock ?? 0) * (float) ($variant->cost_price ?? 0)), 2),
            'out' => (clone $inventoryStatsQuery)->where(function ($query) {
                $query->whereNull('stock')->orWhere('stock', '<=', 0);
            })->count(),
            'low' => (clone $inventoryStatsQuery)
                ->whereNotNull('stock')
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('min_stock', '>', 0)
                ->count(),
            'no_cost' => (clone $inventoryStatsQuery)
                ->where(function ($query) {
                    $query->whereNull('cost_price')->orWhere('cost_price', '<=', 0);
                })
                ->count(),
            'missing_sku' => (clone $inventoryStatsQuery)
                ->where(function ($query) {
                    $query->whereNull('sku')->orWhere('sku', '');
                })
                ->count(),
            'mismatches' => $statsVariants->filter(function ($variant) {
                return $variant->locationStocks->isNotEmpty()
                    && (int) $variant->locationStocks->sum('quantity') !== (int) ($variant->stock ?? 0);
            })->count(),
        ];

        $recentMovements = InventoryMovement::query()
            ->with(['variant.item.type', 'user', 'location', 'fromLocation', 'toLocation'])
            ->whereHas('variant.item', function ($itemQuery) use ($empresa, $selectedTypeId) {
                $itemQuery->where('empresa_id', $empresa->id)
                    ->whereHas('type', function ($typeQuery) use ($selectedTypeId) {
                        $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS)
                            ->when($selectedTypeId > 0, function ($filteredTypeQuery) use ($selectedTypeId) {
                                $filteredTypeQuery->whereKey($selectedTypeId);
                            });
                    });
            })
            ->when(in_array($movementType, ['in', 'out', 'adjust'], true), fn ($query) => $query->where('type', $movementType))
            ->when($movementDateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $movementDateFrom))
            ->when($movementDateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $movementDateTo))
            ->latest()
            ->paginate(20, ['*'], 'movements_page')
            ->withQueryString();

        $selectedType = $productTypes->firstWhere('id', $selectedTypeId);
        $locations = $this->activeLocations($empresa);

        return view('admin.inventario.index', compact(
            'variants',
            'recentMovements',
            'productTypes',
            'selectedTypeId',
            'selectedType',
            'inventoryStats',
            'movementType',
            'movementDateFrom',
            'movementDateTo',
            'locations',
            'canViewCosts'
        ));
    }

    public function export(Request $request)
    {
        abort_unless($this->canInventory('inventory.export'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $selectedTypeId = (int) $request->query('catalog_type_id', 0);

        $variants = CatalogItemVariant::query()
            ->with(['item.type', 'item.category'])
            ->whereHas('item', function ($query) use ($empresa, $selectedTypeId) {
                $query->where('empresa_id', $empresa->id)
                    ->whereHas('type', function ($typeQuery) use ($selectedTypeId) {
                        $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS)
                            ->when($selectedTypeId > 0, fn ($filteredTypeQuery) => $filteredTypeQuery->whereKey($selectedTypeId));
                    });
            })
            ->ordered()
            ->get();

        $rows = [[
            'negocio',
            'categoria',
            'producto',
            'presentacion',
            'sku',
            'precio',
            'costo',
            'stock',
            'stock_minimo',
            'estado',
        ]];

        foreach ($variants as $variant) {
            $rows[] = [
                $variant->item?->type?->name ?? '',
                $variant->item?->category?->name ?? '',
                $variant->item?->name ?? '',
                $variant->name,
                $variant->sku ?? '',
                $variant->price ?? '',
                $this->canInventory('inventory.view_costs') ? ($variant->cost_price ?? '') : '',
                $variant->stock ?? 0,
                $variant->min_stock ?? 0,
                $variant->active ? 'activo' : 'oculto',
            ];
        }

        $csv = $this->buildCsv($rows);
        $fileName = 'inventario-'.now()->format('Ymd-His').'.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function import()
    {
        abort_unless($this->canInventory('inventory.import'), 403);

        return view('admin.inventario.import', [
            'previewRows' => collect(),
            'hasErrors' => false,
            'rawRows' => [],
        ]);
    }

    public function previewImport(Request $request)
    {
        abort_unless($this->canInventory('inventory.import'), 403);

        $request->validate([
            'inventory_file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $rows = $this->readCsv($request->file('inventory_file')->getRealPath());
        $previewRows = $this->buildImportPreview($rows);

        return view('admin.inventario.import', [
            'previewRows' => $previewRows,
            'hasErrors' => $previewRows->contains(fn ($row) => ! $row['valid']),
            'rawRows' => $rows,
        ]);
    }

    public function storeImport(Request $request, InventoryService $inventoryService)
    {
        abort_unless($this->canInventory('inventory.import'), 403);

        $data = $request->validate([
            'rows' => ['required', 'string'],
        ]);

        $rows = json_decode($data['rows'], true);

        if (! is_array($rows)) {
            throw ValidationException::withMessages(['rows' => 'La vista previa ya no es valida. Vuelve a subir el archivo.']);
        }

        $previewRows = $this->buildImportPreview($rows);

        if ($previewRows->contains(fn ($row) => ! $row['valid'])) {
            return view('admin.inventario.import', [
                'previewRows' => $previewRows,
                'hasErrors' => true,
                'rawRows' => $rows,
            ])->withErrors(['rows' => 'Corrige el archivo antes de importar.']);
        }

        foreach ($previewRows as $row) {
            /** @var CatalogItemVariant $variant */
            $variant = $row['variant'];
            $updates = [];

            foreach (['price', 'cost_price', 'min_stock'] as $field) {
                if ($row[$field] !== null) {
                    $updates[$field] = $row[$field];
                }
            }

            if ($updates) {
                $variant->update($updates);
            }

            if ($row['stock'] !== null && (int) $variant->stock !== (int) $row['stock']) {
                $inventoryService->applyMovement(
                    $variant,
                    'adjust',
                    (int) $row['stock'],
                    'Importacion CSV',
                    [
                        'reason' => 'importacion',
                        'reference' => 'csv:'.now()->format('YmdHis'),
                    ]
                );
            }
        }

        NotificationHelper::success('Inventario importado correctamente.');

        return redirect()->route('admin.inventario.index');
    }

    public function create()
    {
        abort_unless($this->canInventory('inventory.move'), 403);

        $empresa = $this->getOrCreateEmpresa();

        $variants = CatalogItemVariant::query()
            ->with('item')
            ->whereHas('item', function ($query) use ($empresa) {
                $query->where('empresa_id', $empresa->id);
                $query->whereHas('type', function ($typeQuery) {
                    $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
                });
            })
            ->ordered()
            ->get();
        $locations = $this->activeLocations($empresa);

        return view('admin.inventario.create', compact('variants', 'locations'));
    }

    public function storeMovement(Request $request, InventoryService $inventoryService)
    {
        abort_unless($this->canInventory('inventory.move'), 403);

        $data = $request->validate([
            'catalog_item_id' => ['nullable', 'required_without:catalog_item_variant_id', 'integer', 'exists:catalog_items,id'],
            'catalog_item_variant_id' => ['nullable', 'required_without:catalog_item_id', 'integer', 'exists:catalog_item_variants,id'],
            'type' => ['required', 'in:in,out,adjust'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'inventory_location_id' => ['nullable', 'integer', 'exists:inventory_locations,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $variant = $this->resolveInventoryVariant($data['catalog_item_variant_id'] ?? null, $data['catalog_item_id'] ?? null);

        if (
            ! $variant->item
            || ($variant->item->type?->business_model !== CatalogType::BUSINESS_MODEL_PRODUCTS)
        ) {
            NotificationHelper::error('Este producto no tiene inventario habilitado.');

            return redirect()->back();
        }

        if (! $variant->item->uses_inventory) {
            $variant->item->update(['uses_inventory' => true]);
        }

        $inventoryService->applyMovement(
            $variant,
            $data['type'],
            (int) $data['quantity'],
            $data['notes'] ?? null,
            [
                'inventory_location_id' => $data['inventory_location_id'] ?? null,
                'reason' => $this->cleanInput($data['reason'] ?? null),
                'reference' => $this->cleanInput($data['reference'] ?? null),
                'batch_number' => $this->cleanInput($data['batch_number'] ?? null),
                'expires_at' => $data['expires_at'] ?? null,
                'unit_cost' => $data['unit_cost'] ?? null,
            ]
        );

        NotificationHelper::success('Movimiento de inventario registrado.');

        return redirect()->route('admin.inventario.index');
    }

    public function edit(InventoryMovement $movement)
    {
        abort_unless($this->canInventory('inventory.update'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $this->ensureInventoryMovement($movement, $empresa);

        $variants = CatalogItemVariant::query()
            ->with('item')
            ->whereHas('item', function ($query) use ($empresa) {
                $query->where('empresa_id', $empresa->id);
                $query->whereHas('type', function ($typeQuery) {
                    $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
                });
            })
            ->ordered()
            ->get();

        return view('admin.inventario.edit', compact('movement', 'variants'));
    }

    public function update(Request $request, InventoryMovement $movement)
    {
        abort_unless($this->canInventory('inventory.update'), 403);

        $this->ensureInventoryMovement($movement, $this->getOrCreateEmpresa());

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $movement->update([
            'reason' => $this->cleanInput($data['reason'] ?? null),
            'reference' => $this->cleanInput($data['reference'] ?? null),
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
        ]);

        NotificationHelper::success('Movimiento actualizado.');

        return redirect()->route('admin.inventario.index');
    }

    public function destroy(InventoryMovement $movement, InventoryService $inventoryService)
    {
        abort_unless($this->canInventory('inventory.void'), 403);

        $this->ensureInventoryMovement($movement, $this->getOrCreateEmpresa());

        $inventoryService->reverseMovement($movement);
        NotificationHelper::success('Movimiento anulado y reversa registrada.');

        return redirect()->route('admin.inventario.index');
    }

    private function resolveInventoryVariant(?int $variantId, ?int $itemId): CatalogItemVariant
    {
        $empresa = $this->getOrCreateEmpresa();

        if ($variantId) {
            return CatalogItemVariant::query()
                ->with('item.type')
                ->whereHas('item', function ($query) use ($empresa) {
                    $query->where('empresa_id', $empresa->id);
                })
                ->findOrFail($variantId);
        }

        $item = CatalogItem::query()
            ->with(['type', 'variants'])
            ->where('empresa_id', $empresa->id)
            ->whereHas('type', function ($query) {
                $query->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
            })
            ->findOrFail($itemId);

        if (! $item->uses_inventory) {
            $item->update(['uses_inventory' => true]);
        }

        $variant = $item->variants->first();

        if ($variant) {
            $variant->load('item.type');

            return $variant;
        }

        return CatalogItemVariant::create([
            'catalog_item_id' => $item->id,
            'name' => 'General',
            'price' => $item->base_price,
            'stock' => 0,
            'min_stock' => 0,
            'active' => true,
            'is_default' => true,
        ])->load('item.type');
    }

    private function getOrCreateEmpresa(): Empresa
    {
        return Empresa::query()->first() ?? Empresa::create([
            'nombre' => 'Mi negocio',
        ]);
    }

    private function activeLocations(Empresa $empresa)
    {
        $locations = InventoryLocation::query()
            ->where('empresa_id', $empresa->id)
            ->active()
            ->ordered()
            ->get();

        if ($locations->isNotEmpty()) {
            return $locations;
        }

        return collect([InventoryLocation::create([
            'empresa_id' => $empresa->id,
            'name' => 'Bodega principal',
            'type' => 'warehouse',
            'is_default' => true,
            'active' => true,
        ])]);
    }

    private function ensureInventoryMovement(InventoryMovement $movement, Empresa $empresa): void
    {
        $movement->loadMissing('variant.item.type');

        abort_unless(
            $movement->variant
            && $movement->variant->item
            && (int) $movement->variant->item->empresa_id === (int) $empresa->id
            && $movement->variant->item->type?->business_model === CatalogType::BUSINESS_MODEL_PRODUCTS,
            404
        );
    }

    private function buildCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn ($header) => trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $header)), $headers);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $normalized = [];
            foreach ($headers as $index => $header) {
                $normalized[$header] = $row[$index] ?? null;
            }
            $rows[] = $normalized;
        }

        fclose($handle);

        return $rows;
    }

    private function buildImportPreview(array $rows): Collection
    {
        $empresa = $this->getOrCreateEmpresa();
        $fileSkuCounts = collect($rows)
            ->map(fn (array $row) => trim((string) ($row['sku'] ?? '')))
            ->filter()
            ->countBy();

        return collect($rows)->map(function (array $row, int $index) use ($empresa, $fileSkuCounts) {
            $sku = trim((string) ($row['sku'] ?? ''));
            $variantQuery = CatalogItemVariant::query()
                ->with('item')
                ->where('sku', $sku)
                ->whereHas('item', function ($itemQuery) use ($empresa) {
                    $itemQuery->where('empresa_id', $empresa->id)
                        ->whereHas('type', fn ($typeQuery) => $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS));
                });
            $skuMatches = $sku !== '' ? (clone $variantQuery)->count() : 0;
            $variant = $skuMatches === 1 ? $variantQuery->first() : null;
            $errors = [];
            $stock = $this->nullableInteger($row['stock'] ?? null, 'stock', $errors);
            $minStock = $this->nullableInteger($row['stock_minimo'] ?? null, 'stock_minimo', $errors);
            $price = $this->nullableDecimal($row['precio'] ?? null, 'precio', $errors);
            $costPrice = $this->nullableDecimal($row['costo'] ?? null, 'costo', $errors);

            if ($sku === '') {
                $errors[] = 'SKU requerido';
            } elseif (($fileSkuCounts[$sku] ?? 0) > 1) {
                $errors[] = 'SKU repetido en archivo';
            } elseif ($skuMatches > 1) {
                $errors[] = 'SKU duplicado en catalogo';
            } elseif (! $variant) {
                $errors[] = 'SKU no encontrado';
            }

            return [
                'line' => $index + 2,
                'sku' => $sku,
                'variant' => $variant,
                'product' => $variant?->item?->name,
                'presentation' => $variant?->name,
                'current_stock' => $variant?->stock,
                'stock' => $stock,
                'min_stock' => $minStock,
                'price' => $price,
                'cost_price' => $costPrice,
                'valid' => empty($errors),
                'errors' => $errors,
            ];
        });
    }

    private function nullableInteger(mixed $value, string $field, array &$errors): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (! ctype_digit((string) $value)) {
            $errors[] = "{$field} debe ser entero positivo";

            return null;
        }

        return (int) $value;
    }

    private function nullableDecimal(mixed $value, string $field, array &$errors): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (! is_numeric($value) || (float) $value < 0) {
            $errors[] = "{$field} debe ser numerico positivo";

            return null;
        }

        return round((float) $value, 2);
    }

    private function cleanInput(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function canInventory(string $permission): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isOwner() || $user->can($permission)));
    }
}
