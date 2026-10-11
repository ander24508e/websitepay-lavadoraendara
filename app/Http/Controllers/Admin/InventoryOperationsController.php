<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\CatalogItemVariant;
use App\Models\CatalogType;
use App\Models\Empresa;
use App\Models\InventoryCount;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\InventoryPeriod;
use App\Models\InventoryReturn;
use App\Models\InventoryStock;
use App\Models\InventoryTransfer;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryOperationsController extends Controller
{
    public function locations()
    {
        $empresa = $this->getOrCreateEmpresa();
        $locations = InventoryLocation::query()
            ->where('empresa_id', $empresa->id)
            ->withCount('stocks')
            ->ordered()
            ->paginate(15);

        return view('admin.inventario.locations', compact('locations'));
    }

    public function storeLocation(Request $request)
    {
        abort_unless($this->canInventory('inventory.locations.create'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['warehouse', 'branch', 'vehicle', 'other'])],
            'address' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($empresa, $data, $request) {
            if ($request->boolean('is_default')) {
                InventoryLocation::query()->where('empresa_id', $empresa->id)->update(['is_default' => false]);
            }

            InventoryLocation::create([
                'empresa_id' => $empresa->id,
                'name' => trim($data['name']),
                'type' => $data['type'],
                'address' => $this->cleanInput($data['address'] ?? null),
                'is_default' => $request->boolean('is_default'),
                'active' => $request->boolean('active', true),
            ]);
        });

        NotificationHelper::success('Ubicacion creada correctamente.');

        return redirect()->route('admin.inventario.locations');
    }

    public function suppliers()
    {
        $empresa = $this->getOrCreateEmpresa();
        $suppliers = Supplier::query()
            ->where('empresa_id', $empresa->id)
            ->withCount('purchases')
            ->ordered()
            ->paginate(15);

        return view('admin.inventario.suppliers', compact('suppliers'));
    }

    public function storeSupplier(Request $request)
    {
        abort_unless($this->canInventory('inventory.suppliers.create'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
        ]);

        Supplier::create([
            'empresa_id' => $empresa->id,
            'name' => trim($data['name']),
            'document' => $this->cleanInput($data['document'] ?? null),
            'phone' => $this->cleanInput($data['phone'] ?? null),
            'email' => $this->cleanInput($data['email'] ?? null),
            'address' => $this->cleanInput($data['address'] ?? null),
            'notes' => $this->cleanInput($data['notes'] ?? null),
            'active' => $request->boolean('active', true),
        ]);

        NotificationHelper::success('Proveedor creado correctamente.');

        return redirect()->route('admin.inventario.suppliers');
    }

    public function purchases()
    {
        $empresa = $this->getOrCreateEmpresa();
        $purchases = Purchase::query()
            ->where('empresa_id', $empresa->id)
            ->with(['supplier', 'location', 'items.variant.item'])
            ->latest()
            ->paginate(15);
        $suppliers = Supplier::query()->where('empresa_id', $empresa->id)->active()->ordered()->get();
        $locations = $this->activeLocations($empresa);
        $variants = $this->inventoryVariants($empresa);

        return view('admin.inventario.purchases', compact('purchases', 'suppliers', 'locations', 'variants'));
    }

    public function storePurchase(Request $request, InventoryService $inventoryService)
    {
        abort_unless($this->canInventory('inventory.purchases.create'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $data = $request->validate([
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'inventory_location_id' => ['required', 'integer', 'exists:inventory_locations,id'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'tax_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_variant_id' => ['nullable', 'integer', 'exists:catalog_item_variants,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.expires_at' => ['nullable', 'date'],
        ]);

        $location = InventoryLocation::query()
            ->where('empresa_id', $empresa->id)
            ->findOrFail($data['inventory_location_id']);

        DB::transaction(function () use ($empresa, $data, $location, $inventoryService) {
            $items = collect($data['items'])
                ->filter(fn ($item) => ! empty($item['catalog_item_variant_id']) && (int) ($item['quantity'] ?? 0) > 0);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Agrega al menos un producto a la compra.']);
            }

            $subtotal = $items->sum(fn ($item) => round((float) $item['unit_cost'] * (int) $item['quantity'], 2));
            $discountTotal = round((float) ($data['discount_total'] ?? 0), 2);
            $taxTotal = round((float) ($data['tax_total'] ?? 0), 2);
            $total = max(0, round($subtotal - $discountTotal + $taxTotal, 2));

            $purchase = Purchase::create([
                'empresa_id' => $empresa->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'inventory_location_id' => $location->id,
                'user_id' => auth()->id(),
                'document_number' => $this->cleanInput($data['document_number'] ?? null),
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'status' => 'received',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'total' => $total,
                'notes' => $this->cleanInput($data['notes'] ?? null),
            ]);

            foreach ($items as $row) {
                $variant = $this->findInventoryVariant($empresa, (int) $row['catalog_item_variant_id']);
                $quantity = (int) $row['quantity'];
                $unitCost = round((float) $row['unit_cost'], 2);

                $purchaseItem = $purchase->items()->create([
                    'catalog_item_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => round($unitCost * $quantity, 2),
                ]);

                $inventoryService->applyMovement(
                    $variant,
                    'in',
                    $quantity,
                    'Compra #'.$purchase->id,
                    [
                        'inventory_location_id' => $location->id,
                        'purchase_id' => $purchase->id,
                        'purchase_item_id' => $purchaseItem->id,
                        'reason' => 'compra',
                        'reference' => $purchase->document_number ?: 'purchase:'.$purchase->id,
                        'batch_number' => $this->cleanInput($row['batch_number'] ?? null),
                        'expires_at' => $row['expires_at'] ?? null,
                        'unit_cost' => $unitCost,
                    ]
                );
            }
        });

        NotificationHelper::success('Compra registrada e inventario actualizado.');

        return redirect()->route('admin.inventario.purchases');
    }

    public function transfers()
    {
        $empresa = $this->getOrCreateEmpresa();
        $transfers = InventoryTransfer::query()
            ->where('empresa_id', $empresa->id)
            ->with(['fromLocation', 'toLocation', 'items.variant.item'])
            ->latest()
            ->paginate(15);
        $locations = $this->activeLocations($empresa);
        $variants = $this->inventoryVariants($empresa);

        return view('admin.inventario.transfers', compact('transfers', 'locations', 'variants'));
    }

    public function storeTransfer(Request $request, InventoryService $inventoryService)
    {
        abort_unless($this->canInventory('inventory.transfers.create'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $data = $request->validate([
            'from_location_id' => ['required', 'integer', 'exists:inventory_locations,id'],
            'to_location_id' => ['required', 'integer', 'exists:inventory_locations,id', 'different:from_location_id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_variant_id' => ['nullable', 'integer', 'exists:catalog_item_variants,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $fromLocation = InventoryLocation::query()->where('empresa_id', $empresa->id)->findOrFail($data['from_location_id']);
        $toLocation = InventoryLocation::query()->where('empresa_id', $empresa->id)->findOrFail($data['to_location_id']);

        DB::transaction(function () use ($empresa, $data, $fromLocation, $toLocation, $inventoryService) {
            $transfer = InventoryTransfer::create([
                'empresa_id' => $empresa->id,
                'from_location_id' => $fromLocation->id,
                'to_location_id' => $toLocation->id,
                'user_id' => auth()->id(),
                'reference' => $this->cleanInput($data['reference'] ?? null),
                'status' => 'completed',
                'notes' => $this->cleanInput($data['notes'] ?? null),
            ]);

            $rows = collect($data['items'])
                ->filter(fn ($item) => ! empty($item['catalog_item_variant_id']) && (int) ($item['quantity'] ?? 0) > 0);

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Agrega al menos un producto a transferir.']);
            }

            foreach ($rows as $row) {
                $variant = $this->findInventoryVariant($empresa, (int) $row['catalog_item_variant_id']);
                $item = $transfer->items()->create([
                    'catalog_item_variant_id' => $variant->id,
                    'quantity' => (int) $row['quantity'],
                ]);

                $inventoryService->transfer($transfer, $item, $fromLocation, $toLocation);
            }
        });

        NotificationHelper::success('Transferencia registrada correctamente.');

        return redirect()->route('admin.inventario.transfers');
    }

    public function kardex(Request $request, CatalogItemVariant $variant)
    {
        $empresa = $this->getOrCreateEmpresa();
        $variant = $this->findInventoryVariant($empresa, $variant->id);
        $locations = $this->activeLocations($empresa);
        $canViewCosts = $this->canInventory('inventory.view_costs');

        $movements = InventoryMovement::query()
            ->with(['location', 'fromLocation', 'toLocation', 'user', 'purchase', 'inventoryReturn', 'inventoryCount', 'transfer'])
            ->where('catalog_item_variant_id', $variant->id)
            ->when($request->filled('inventory_location_id'), fn ($query) => $query->where('inventory_location_id', $request->query('inventory_location_id')))
            ->when(in_array($request->query('type'), ['in', 'out', 'adjust'], true), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->filled('reason'), fn ($query) => $query->where('reason', $request->query('reason')))
            ->when($request->filled('reference'), fn ($query) => $query->where('reference', 'like', '%'.trim((string) $request->query('reference')).'%'))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->query('date_to')))
            ->oldest()
            ->paginate(50)
            ->withQueryString();

        $stocks = InventoryStock::query()
            ->with('location')
            ->where('catalog_item_variant_id', $variant->id)
            ->get();

        $reasons = InventoryMovement::query()
            ->where('catalog_item_variant_id', $variant->id)
            ->whereNotNull('reason')
            ->distinct()
            ->orderBy('reason')
            ->pluck('reason');

        return view('admin.inventario.kardex', compact('variant', 'movements', 'stocks', 'locations', 'reasons', 'canViewCosts'));
    }

    public function exportKardex(Request $request, CatalogItemVariant $variant)
    {
        abort_unless($this->canInventory('inventory.export'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $variant = $this->findInventoryVariant($empresa, $variant->id);
        $canViewCosts = $this->canInventory('inventory.view_costs');

        $movements = InventoryMovement::query()
            ->with(['location', 'fromLocation', 'toLocation', 'user'])
            ->where('catalog_item_variant_id', $variant->id)
            ->when($request->filled('inventory_location_id'), fn ($query) => $query->where('inventory_location_id', $request->query('inventory_location_id')))
            ->when(in_array($request->query('type'), ['in', 'out', 'adjust'], true), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->filled('reason'), fn ($query) => $query->where('reason', $request->query('reason')))
            ->when($request->filled('reference'), fn ($query) => $query->where('reference', 'like', '%'.trim((string) $request->query('reference')).'%'))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->query('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->query('date_to')))
            ->oldest()
            ->get();

        $rows = [[
            'fecha',
            'producto',
            'presentacion',
            'sku',
            'tipo',
            'ubicacion',
            'motivo',
            'referencia',
            'lote',
            'vencimiento',
            'entrada',
            'salida',
            'costo_unitario',
            'total_movimiento',
            'saldo_cantidad',
            'saldo_costo',
            'saldo_total',
            'usuario',
            'estado',
        ]];

        foreach ($movements as $movement) {
            $rows[] = [
                $movement->created_at?->format('Y-m-d H:i:s'),
                $variant->item?->name ?? '',
                $variant->name ?? '',
                $variant->sku ?? '',
                $movement->type,
                $movement->location?->name ?? ($movement->fromLocation?->name && $movement->toLocation?->name ? $movement->fromLocation->name.' -> '.$movement->toLocation->name : ''),
                $movement->reason ?? '',
                $movement->reference ?? '',
                $movement->batch_number ?? '',
                $movement->expires_at?->format('Y-m-d') ?? '',
                $movement->type === 'in' ? $movement->quantity : '',
                $movement->type === 'out' ? $movement->quantity : '',
                $canViewCosts ? ($movement->unit_cost ?? 0) : '',
                $canViewCosts ? ($movement->total_cost ?? 0) : '',
                $movement->balance_quantity ?? $movement->stock_after ?? 0,
                $canViewCosts ? ($movement->balance_unit_cost ?? 0) : '',
                $canViewCosts ? ($movement->balance_total_cost ?? 0) : '',
                $movement->user?->name ?? 'Sistema',
                $movement->voided_at ? 'anulado' : 'activo',
            ];
        }

        return $this->csvResponse($rows, 'kardex-'.($variant->sku ?: $variant->id).'-'.now()->format('Ymd-His').'.csv');
    }

    public function returns()
    {
        $empresa = $this->getOrCreateEmpresa();
        $returns = InventoryReturn::query()
            ->where('empresa_id', $empresa->id)
            ->with(['location', 'supplier', 'items.variant.item'])
            ->latest()
            ->paginate(15);
        $locations = $this->activeLocations($empresa);
        $suppliers = Supplier::query()->where('empresa_id', $empresa->id)->active()->ordered()->get();
        $variants = $this->inventoryVariants($empresa);

        return view('admin.inventario.returns', compact('returns', 'locations', 'suppliers', 'variants'));
    }

    public function storeReturn(Request $request, InventoryService $inventoryService)
    {
        abort_unless($this->canInventory('inventory.returns.create'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $data = $request->validate([
            'type' => ['required', Rule::in(['customer', 'supplier'])],
            'inventory_location_id' => ['required', 'integer', 'exists:inventory_locations,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_variant_id' => ['nullable', 'integer', 'exists:catalog_item_variants,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.expires_at' => ['nullable', 'date'],
        ]);

        $location = InventoryLocation::query()->where('empresa_id', $empresa->id)->findOrFail($data['inventory_location_id']);

        DB::transaction(function () use ($empresa, $data, $location, $inventoryService) {
            $rows = collect($data['items'])
                ->filter(fn ($item) => ! empty($item['catalog_item_variant_id']) && (int) ($item['quantity'] ?? 0) > 0);

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Agrega al menos un producto a devolver.']);
            }

            $return = InventoryReturn::create([
                'empresa_id' => $empresa->id,
                'inventory_location_id' => $location->id,
                'supplier_id' => $data['type'] === 'supplier' ? ($data['supplier_id'] ?? null) : null,
                'user_id' => auth()->id(),
                'type' => $data['type'],
                'reference' => $this->cleanInput($data['reference'] ?? null),
                'status' => 'completed',
                'notes' => $this->cleanInput($data['notes'] ?? null),
            ]);

            foreach ($rows as $row) {
                $variant = $this->findInventoryVariant($empresa, (int) $row['catalog_item_variant_id']);
                $unitCost = isset($row['unit_cost']) && $row['unit_cost'] !== null && $row['unit_cost'] !== ''
                    ? round((float) $row['unit_cost'], 2)
                    : (float) ($variant->cost_price ?? 0);
                $quantity = (int) $row['quantity'];

                $returnItem = $return->items()->create([
                    'catalog_item_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                ]);

                $inventoryService->applyMovement(
                    $variant,
                    $data['type'] === 'customer' ? 'in' : 'out',
                    $quantity,
                    ($data['type'] === 'customer' ? 'Devolucion cliente #' : 'Devolucion proveedor #').$return->id,
                    [
                        'inventory_location_id' => $location->id,
                        'inventory_return_id' => $return->id,
                        'inventory_return_item_id' => $returnItem->id,
                        'reason' => $data['type'] === 'customer' ? 'devolucion_cliente' : 'devolucion_proveedor',
                        'reference' => $return->reference ?: 'return:'.$return->id,
                        'batch_number' => $this->cleanInput($row['batch_number'] ?? null),
                        'expires_at' => $row['expires_at'] ?? null,
                        'unit_cost' => $unitCost,
                    ]
                );
            }
        });

        NotificationHelper::success('Devolucion registrada correctamente.');

        return redirect()->route('admin.inventario.returns');
    }

    public function counts()
    {
        $empresa = $this->getOrCreateEmpresa();
        $counts = InventoryCount::query()
            ->where('empresa_id', $empresa->id)
            ->with(['location', 'items.variant.item'])
            ->latest()
            ->paginate(15);
        $locations = $this->activeLocations($empresa);
        $variants = $this->inventoryVariants($empresa);

        return view('admin.inventario.counts', compact('counts', 'locations', 'variants'));
    }

    public function storeCount(Request $request, InventoryService $inventoryService)
    {
        abort_unless($this->canInventory('inventory.counts.create'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $data = $request->validate([
            'inventory_location_id' => ['nullable', 'integer', 'exists:inventory_locations,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_variant_id' => ['nullable', 'integer', 'exists:catalog_item_variants,id'],
            'items.*.counted_quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $location = ! empty($data['inventory_location_id'])
            ? InventoryLocation::query()->where('empresa_id', $empresa->id)->findOrFail($data['inventory_location_id'])
            : null;

        DB::transaction(function () use ($empresa, $data, $location, $inventoryService) {
            $rows = collect($data['items'])
                ->filter(fn ($item) => ! empty($item['catalog_item_variant_id']) && $item['counted_quantity'] !== null && $item['counted_quantity'] !== '');

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Agrega al menos un producto contado.']);
            }

            $count = InventoryCount::create([
                'empresa_id' => $empresa->id,
                'inventory_location_id' => $location?->id,
                'user_id' => auth()->id(),
                'reference' => $this->cleanInput($data['reference'] ?? null),
                'status' => 'completed',
                'notes' => $this->cleanInput($data['notes'] ?? null),
            ]);

            foreach ($rows as $row) {
                $variant = $this->findInventoryVariant($empresa, (int) $row['catalog_item_variant_id']);
                $expected = $location
                    ? (int) InventoryStock::query()
                        ->where('inventory_location_id', $location->id)
                        ->where('catalog_item_variant_id', $variant->id)
                        ->value('quantity')
                    : (int) ($variant->stock ?? 0);
                $counted = (int) $row['counted_quantity'];
                $difference = $counted - $expected;

                $countItem = $count->items()->create([
                    'catalog_item_variant_id' => $variant->id,
                    'expected_quantity' => $expected,
                    'counted_quantity' => $counted,
                    'difference_quantity' => $difference,
                ]);

                if ($difference === 0) {
                    continue;
                }

                $inventoryService->applyMovement(
                    $variant,
                    $difference > 0 ? 'in' : 'out',
                    abs($difference),
                    'Conteo fisico #'.$count->id,
                    [
                        'inventory_location_id' => $location?->id,
                        'inventory_count_id' => $count->id,
                        'inventory_count_item_id' => $countItem->id,
                        'reason' => 'conteo_fisico',
                        'reference' => $count->reference ?: 'count:'.$count->id,
                        'unit_cost' => $variant->cost_price,
                    ]
                );
            }
        });

        NotificationHelper::success('Conteo fisico registrado y diferencias ajustadas.');

        return redirect()->route('admin.inventario.counts');
    }

    public function reports(Request $request)
    {
        $empresa = $this->getOrCreateEmpresa();
        $locations = $this->activeLocations($empresa);
        $canViewCosts = $this->canInventory('inventory.view_costs');
        $report = $this->inventoryReportData($empresa, $request);

        return view('admin.inventario.reports', [
            ...$report,
            'locations' => $locations,
            'canViewCosts' => $canViewCosts,
        ]);
    }

    public function exportReport(Request $request, string $section)
    {
        abort_unless($this->canInventory('inventory.reports.view'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $report = $this->inventoryReportData($empresa, $request);
        $canViewCosts = $this->canInventory('inventory.view_costs');

        $rows = match ($section) {
            'stock' => $this->stockReportRows($report['variants'], $canViewCosts),
            'locations' => $this->locationReportRows($report['locationStocks'], $canViewCosts),
            'movements' => $this->movementReportRows($report['movements'], $canViewCosts),
            'alerts' => $this->alertReportRows($report['alerts'], $canViewCosts),
            'profit' => $this->profitReportRows($report['profitRows'], $canViewCosts),
            default => throw ValidationException::withMessages(['section' => 'Reporte no valido.']),
        };

        return $this->csvResponse($rows, 'inventario-'.$section.'-'.now()->format('Ymd-His').'.csv');
    }

    public function periods()
    {
        $empresa = $this->getOrCreateEmpresa();
        $periods = InventoryPeriod::query()
            ->where('empresa_id', $empresa->id)
            ->with('user')
            ->latest('date_to')
            ->paginate(15);

        return view('admin.inventario.periods', compact('periods'));
    }

    public function storePeriod(Request $request)
    {
        abort_unless($this->canInventory('inventory.periods.create'), 403);

        $empresa = $this->getOrCreateEmpresa();
        $data = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $from = Carbon::parse($data['date_from'])->toDateString();
        $to = Carbon::parse($data['date_to'])->toDateString();
        $variants = $this->inventoryVariants($empresa);

        InventoryPeriod::updateOrCreate(
            [
                'empresa_id' => $empresa->id,
                'date_from' => $from,
                'date_to' => $to,
            ],
            [
                'user_id' => auth()->id(),
                'status' => 'closed',
                'variants_count' => $variants->count(),
                'total_units' => (int) $variants->sum(fn ($variant) => (int) ($variant->stock ?? 0)),
                'total_value' => round($variants->sum(fn ($variant) => (int) ($variant->stock ?? 0) * (float) ($variant->cost_price ?? 0)), 2),
                'notes' => $this->cleanInput($data['notes'] ?? null),
                'closed_at' => now(),
            ]
        );

        NotificationHelper::success('Periodo de inventario cerrado correctamente.');

        return redirect()->route('admin.inventario.periods');
    }

    private function getOrCreateEmpresa(): Empresa
    {
        return Empresa::query()->first() ?? Empresa::create(['nombre' => 'Mi negocio']);
    }

    private function activeLocations(Empresa $empresa)
    {
        $locations = InventoryLocation::query()->where('empresa_id', $empresa->id)->active()->ordered()->get();

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

    private function inventoryVariants(Empresa $empresa)
    {
        return CatalogItemVariant::query()
            ->with(['item.type', 'item.category'])
            ->whereHas('item', function ($query) use ($empresa) {
                $query->where('empresa_id', $empresa->id)
                    ->whereHas('type', fn ($typeQuery) => $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS));
            })
            ->ordered()
            ->get();
    }

    private function findInventoryVariant(Empresa $empresa, int $variantId): CatalogItemVariant
    {
        return CatalogItemVariant::query()
            ->with(['item.type', 'item.category'])
            ->whereKey($variantId)
            ->whereHas('item', function ($query) use ($empresa) {
                $query->where('empresa_id', $empresa->id)
                    ->whereHas('type', fn ($typeQuery) => $typeQuery->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS));
            })
            ->firstOrFail();
    }

    private function cleanInput(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function inventoryReportData(Empresa $empresa, Request $request): array
    {
        $variants = $this->inventoryVariants($empresa)->load(['item.type', 'item.category', 'locationStocks.location']);
        $locationId = (int) $request->query('inventory_location_id', 0);
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $movementType = trim((string) $request->query('type', ''));
        $reason = trim((string) $request->query('reason', ''));

        $locationStocks = InventoryStock::query()
            ->with(['location', 'variant.item.type', 'variant.item.category'])
            ->whereHas('variant.item', fn ($query) => $query->where('empresa_id', $empresa->id))
            ->when($locationId > 0, fn ($query) => $query->where('inventory_location_id', $locationId))
            ->get();

        $movements = InventoryMovement::query()
            ->with(['variant.item.type', 'location', 'fromLocation', 'toLocation', 'user'])
            ->whereHas('variant.item', fn ($query) => $query->where('empresa_id', $empresa->id))
            ->when($locationId > 0, fn ($query) => $query->where('inventory_location_id', $locationId))
            ->when(in_array($movementType, ['in', 'out', 'adjust'], true), fn ($query) => $query->where('type', $movementType))
            ->when($reason !== '', fn ($query) => $query->where('reason', $reason))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->limit(500)
            ->get();

        $alerts = $variants->filter(function ($variant) {
            $stock = (int) ($variant->stock ?? 0);
            $minStock = (int) ($variant->min_stock ?? 0);
            $locationStock = (int) $variant->locationStocks->sum('quantity');
            $hasMismatch = $variant->locationStocks->isNotEmpty() && $locationStock !== $stock;

            return $stock <= 0
                || ($minStock > 0 && $stock <= $minStock)
                || (float) ($variant->cost_price ?? 0) <= 0
                || trim((string) ($variant->sku ?? '')) === ''
                || $hasMismatch;
        })->values();

        $profitRows = InventoryMovement::query()
            ->with(['variant.item', 'saleItem', 'orderItem'])
            ->whereHas('variant.item', fn ($query) => $query->where('empresa_id', $empresa->id))
            ->where('type', 'out')
            ->whereIn('reason', ['venta', 'orden_web'])
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->limit(500)
            ->get()
            ->map(function ($movement) {
                $salePrice = (float) ($movement->saleItem?->unit_price ?? $movement->orderItem?->unit_price ?? 0);
                $quantity = (int) ($movement->quantity ?? 0);
                $revenue = round($salePrice * $quantity, 2);
                $cost = round((float) ($movement->total_cost ?? 0), 2);

                $movement->profit_revenue = $revenue;
                $movement->profit_cost = $cost;
                $movement->profit_amount = round($revenue - $cost, 2);

                return $movement;
            });

        $reasons = InventoryMovement::query()
            ->whereHas('variant.item', fn ($query) => $query->where('empresa_id', $empresa->id))
            ->whereNotNull('reason')
            ->distinct()
            ->orderBy('reason')
            ->pluck('reason');

        return [
            'variants' => $variants,
            'locationStocks' => $locationStocks,
            'movements' => $movements,
            'alerts' => $alerts,
            'profitRows' => $profitRows,
            'reasons' => $reasons,
            'stats' => [
                'units' => (int) $variants->sum(fn ($variant) => (int) ($variant->stock ?? 0)),
                'value' => round($variants->sum(fn ($variant) => (int) ($variant->stock ?? 0) * (float) ($variant->cost_price ?? 0)), 2),
                'low' => $alerts->filter(fn ($variant) => (int) ($variant->stock ?? 0) > 0 && (int) ($variant->min_stock ?? 0) > 0 && (int) ($variant->stock ?? 0) <= (int) ($variant->min_stock ?? 0))->count(),
                'out' => $alerts->filter(fn ($variant) => (int) ($variant->stock ?? 0) <= 0)->count(),
                'no_cost' => $alerts->filter(fn ($variant) => (float) ($variant->cost_price ?? 0) <= 0)->count(),
                'profit' => round($profitRows->sum('profit_amount'), 2),
            ],
        ];
    }

    private function stockReportRows($variants, bool $canViewCosts): array
    {
        $rows = [['producto', 'negocio', 'categoria', 'presentacion', 'sku', 'stock', 'stock_minimo', 'costo_promedio', 'valor_total', 'precio_venta']];

        foreach ($variants as $variant) {
            $rows[] = [
                $variant->item?->name ?? '',
                $variant->item?->type?->name ?? '',
                $variant->item?->category?->name ?? '',
                $variant->name ?? '',
                $variant->sku ?? '',
                $variant->stock ?? 0,
                $variant->min_stock ?? 0,
                $canViewCosts ? ($variant->cost_price ?? 0) : '',
                $canViewCosts ? round((int) ($variant->stock ?? 0) * (float) ($variant->cost_price ?? 0), 2) : '',
                $variant->price ?? 0,
            ];
        }

        return $rows;
    }

    private function locationReportRows($stocks, bool $canViewCosts): array
    {
        $rows = [['ubicacion', 'producto', 'presentacion', 'sku', 'cantidad', 'minimo', 'costo_promedio', 'valor']];

        foreach ($stocks as $stock) {
            $rows[] = [
                $stock->location?->name ?? '',
                $stock->variant?->item?->name ?? '',
                $stock->variant?->name ?? '',
                $stock->variant?->sku ?? '',
                $stock->quantity ?? 0,
                $stock->min_stock ?? 0,
                $canViewCosts ? ($stock->variant?->cost_price ?? 0) : '',
                $canViewCosts ? round((int) ($stock->quantity ?? 0) * (float) ($stock->variant?->cost_price ?? 0), 2) : '',
            ];
        }

        return $rows;
    }

    private function movementReportRows($movements, bool $canViewCosts): array
    {
        $rows = [['fecha', 'producto', 'presentacion', 'tipo', 'cantidad', 'ubicacion', 'motivo', 'referencia', 'costo_unitario', 'total', 'usuario']];

        foreach ($movements as $movement) {
            $rows[] = [
                $movement->created_at?->format('Y-m-d H:i:s'),
                $movement->variant?->item?->name ?? '',
                $movement->variant?->name ?? '',
                $movement->type,
                $movement->quantity,
                $movement->location?->name ?? '',
                $movement->reason ?? '',
                $movement->reference ?? '',
                $canViewCosts ? ($movement->unit_cost ?? 0) : '',
                $canViewCosts ? ($movement->total_cost ?? 0) : '',
                $movement->user?->name ?? 'Sistema',
            ];
        }

        return $rows;
    }

    private function alertReportRows($alerts, bool $canViewCosts): array
    {
        $rows = [['producto', 'presentacion', 'sku', 'stock', 'stock_ubicaciones', 'minimo', 'costo_promedio', 'alerta']];

        foreach ($alerts as $variant) {
            $stock = (int) ($variant->stock ?? 0);
            $minStock = (int) ($variant->min_stock ?? 0);
            $locationStock = (int) $variant->locationStocks->sum('quantity');
            $hasMismatch = $variant->locationStocks->isNotEmpty() && $locationStock !== $stock;
            $alert = match (true) {
                $hasMismatch => 'revisar_stock_ubicaciones',
                trim((string) ($variant->sku ?? '')) === '' => 'sin_sku',
                $stock <= 0 => 'agotado',
                $minStock > 0 && $stock <= $minStock => 'bajo_stock',
                default => 'sin_costo',
            };

            $rows[] = [
                $variant->item?->name ?? '',
                $variant->name ?? '',
                $variant->sku ?? '',
                $stock,
                $locationStock,
                $minStock,
                $canViewCosts ? ($variant->cost_price ?? 0) : '',
                $alert,
            ];
        }

        return $rows;
    }

    private function profitReportRows($profitRows, bool $canViewCosts): array
    {
        $rows = [['fecha', 'producto', 'presentacion', 'cantidad', 'ingreso', 'costo', 'utilidad', 'referencia']];

        foreach ($profitRows as $movement) {
            $rows[] = [
                $movement->created_at?->format('Y-m-d H:i:s'),
                $movement->variant?->item?->name ?? '',
                $movement->variant?->name ?? '',
                $movement->quantity,
                $canViewCosts ? $movement->profit_revenue : '',
                $canViewCosts ? $movement->profit_cost : '',
                $canViewCosts ? $movement->profit_amount : '',
                $movement->reference ?? '',
            ];
        }

        return $rows;
    }

    private function csvResponse(array $rows, string $fileName)
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::make((string) $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    private function canInventory(string $permission): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isOwner() || $user->can($permission)));
    }
}
