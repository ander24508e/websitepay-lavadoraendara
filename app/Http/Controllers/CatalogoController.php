<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\CatalogType;
use App\Models\Empresa;
use App\Models\Vehicle;
use App\Models\VehicleSpecification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CatalogoController extends Controller
{
    private function isUniversalFilter(string $tipo): bool
    {
        return str_starts_with($tipo, 'tipo:');
    }

    private function extractUniversalFilterSlug(string $tipo): ?string
    {
        if (!$this->isUniversalFilter($tipo)) {
            return null;
        }

        $slug = trim(substr($tipo, 5));

        return $slug !== '' ? $slug : null;
    }

    /**
     * Obtiene los items del catalogo universal con filtros y paginacion.
     */
    private function getCatalogItems(string $tipo = 'todos', string $search = '', int $page = 1, int $perPage = 12): array
    {
        $search = trim($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $universalFilterSlug = $this->extractUniversalFilterSlug($tipo);

        if ($tipo !== 'todos' && !$universalFilterSlug) {
            $tipo = 'todos';
        }

        $catalogoUniversales = collect();

        if (Schema::hasTable('catalog_items') && Schema::hasTable('catalog_types')) {
            $universalesQuery = CatalogItem::query()
                ->select('catalog_items.*')
                ->join('catalog_types', 'catalog_types.id', '=', 'catalog_items.catalog_type_id')
                ->with([
                    'type',
                    'category',
                    'activeVariants',
                    'vehicleTypePrices.vehicleType',
                    'vehicleTypePrices.vehicleSpecification.brand',
                    'vehicleTypePrices.vehicleSpecification.model',
                    'vehicleTypePrices.vehicleSpecification.type',
                ])
                ->where('catalog_items.active', true)
                ->where('catalog_types.active', true)
                ->where(function ($query) {
                    $query->where('catalog_types.business_model', CatalogType::BUSINESS_MODEL_SERVICES)
                        ->orWhere(function ($productQuery) {
                            $productQuery->where('catalog_types.business_model', CatalogType::BUSINESS_MODEL_PRODUCTS);
                        });
                })
                ->when($universalFilterSlug, function ($query) use ($universalFilterSlug) {
                    $query->where('catalog_types.slug', $universalFilterSlug);
                })
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('catalog_items.name', 'LIKE', "%{$search}%")
                            ->orWhere('catalog_items.description', 'LIKE', "%{$search}%")
                            ->orWhere('catalog_types.name', 'LIKE', "%{$search}%")
                            ->orWhereHas('category', fn($cq) => $cq->where('name', 'LIKE', "%{$search}%"));
                    });
                })
                ->orderBy('catalog_types.name')
                ->orderBy('catalog_items.name');

            $catalogoUniversales = $universalesQuery->get()->map(function ($item) {
                $isInventariable = (bool) $item->uses_inventory;
                $defaultVariant = $this->resolveDefaultPublicVariant($item);
                $stockDisponible = $this->resolveAvailableStock($item, $defaultVariant);
                $vehiclePrices = $item->vehicleTypePrices
                    ->filter(function ($vehiclePrice) {
                        $specification = $vehiclePrice->vehicleSpecification;

                        if ($specification) {
                            return $vehiclePrice->active
                                && $specification->active
                                && $specification->brand?->active
                                && $specification->model?->active
                                && $specification->type?->active;
                        }

                        return $vehiclePrice->active && $vehiclePrice->vehicleType?->active;
                    })
                    ->sortBy(fn ($vehiclePrice) => (string) $vehiclePrice->vehicle_label)
                    ->map(fn ($vehiclePrice) => [
                        'id' => (int) $vehiclePrice->id,
                        'vehicle_specification_id' => $vehiclePrice->vehicle_specification_id ? (int) $vehiclePrice->vehicle_specification_id : null,
                        'vehicle_type_id' => (int) $vehiclePrice->vehicle_type_id,
                        'vehicle_name' => $vehiclePrice->vehicle_label,
                        'vehicle_type_name' => $vehiclePrice->vehicleType?->name,
                        'price' => $vehiclePrice->price === null ? null : (float) $vehiclePrice->price,
                        'duration_minutes' => $vehiclePrice->duration_minutes,
                        'description' => $vehiclePrice->description,
                    ])
                    ->values();
                $basePrice = (float) ($item->base_price ?? 0);
                $configuredPrices = $vehiclePrices->pluck('price')->filter(fn ($price) => $price !== null);
                $publicPrice = $configuredPrices->isNotEmpty()
                    ? (float) $configuredPrices->min()
                    : ($isInventariable ? (float) ($defaultVariant?->price ?? 0) : $item->display_price);

                return [
                    'id' => $item->id,
                    'nombre' => $item->name,
                    'descripcion' => $item->description,
                    'precio' => $publicPrice,
                    'precio_base' => $basePrice,
                    'duration_minutes' => $item->duration_minutes,
                    'imagen' => $item->image,
                    'categoria' => $item->category->name ?? ($item->type->name ?? 'Catalogo'),
                    'tipo' => 'catalog',
                    'tipo_label' => $item->type->name ?? 'Catalogo',
                    'tipo_descripcion' => $item->type->description ?? null,
                    'business_model' => $item->type->business_model ?? null,
                    'comprable' => (bool) $item->purchasable,
                    'reservable' => (bool) $item->reservable,
                    'inventariable' => $isInventariable,
                    'stock_disponible' => $stockDisponible,
                    'agotado' => $isInventariable && $stockDisponible <= 0,
                    'requiere_tipo_vehiculo' => !$isInventariable && $vehiclePrices->isNotEmpty(),
                    'precios_vehiculo' => $vehiclePrices,
                    'variantes' => $item->activeVariants
                        ->sortBy(fn ($variant) => sprintf(
                            '%d-%s',
                            $variant->is_default ? 0 : 1,
                            (string) $variant->name
                        ))
                        ->values()
                        ->map(function ($variant) {
                            return [
                                'id' => $variant->id,
                                'name' => $variant->name,
                                'price' => (float) ($variant->price ?? 0),
                                'stock' => (int) ($variant->stock ?? 0),
                                'is_default' => (bool) $variant->is_default,
                            ];
                        }),
                ];
            });
        }

        $catalogo = $catalogoUniversales->values();

        $total = $catalogo->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $items = $catalogo->forPage($page, $perPage)->values();

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => (int) $page,
                'last_page'    => (int) $lastPage,
                'per_page'     => (int) $perPage,
                'total'        => (int) $total,
            ],
        ];
    }

    private function resolveDefaultPublicVariant(CatalogItem $item)
    {
        if (!$item->uses_inventory) {
            return null;
        }

        $availableVariant = $item->activeVariants
            ->filter(fn ($itemVariant) => (int) ($itemVariant->stock ?? 0) > 0)
            ->sortBy(fn ($itemVariant) => sprintf(
                '%d-%s',
                $itemVariant->is_default ? 0 : 1,
                (string) $itemVariant->name
            ))
            ->first();

        if ($availableVariant) {
            return $availableVariant;
        }

        return $item->activeVariants
            ->sortBy(fn ($itemVariant) => sprintf(
                '%d-%s',
                $itemVariant->is_default ? 0 : 1,
                (string) $itemVariant->name
            ))
            ->first();
    }

    private function resolveAvailableStock(CatalogItem $item, $defaultVariant = null): int
    {
        if (!$item->uses_inventory) {
            return 9999;
        }

        return max(0, (int) ($defaultVariant?->stock ?? 0));
    }

    private function getCatalogFilters(): array
    {
        $filters = [
            ['value' => 'todos', 'label' => 'Todos'],
        ];

        if (!Schema::hasTable('catalog_types')) {
            return $filters;
        }

        $empresa = Empresa::query()->first();
        if (!$empresa) {
            return $filters;
        }

        $types = CatalogType::query()
            ->where('empresa_id', $empresa->id)
            ->where('active', true)
            ->where(function ($query) {
                $query->where('business_model', '!=', CatalogType::BUSINESS_MODEL_PRODUCTS)
                    ->orWhereHas('items', fn ($items) => $items->where('active', true));
            })
            ->ordered()
            ->get(['name', 'slug']);

        foreach ($types as $type) {
            if (!$type->slug) {
                continue;
            }

            $filters[] = [
                'value' => 'tipo:' . $type->slug,
                'label' => $type->name,
            ];
        }

        return $filters;
    }

    /**
     * Muestra la pagina principal del catalogo (carga inicial).
     */
    public function index(Request $request)
    {
        $tipo = (string) $request->get('tipo', 'todos');
        $search = (string) $request->get('search', '');
        $page = (int) $request->get('page', 1);

        $data = $this->getCatalogItems($tipo, $search, $page);
        $reservableData = $this->getCatalogItems('todos', '', 1, 500);
        $empresaQuery = Empresa::query();

        if (Schema::hasTable('landing_banners')) {
            $empresaQuery->with('landingBanners');
        }

        $empresa = $empresaQuery->first() ?? new Empresa();

        return view('website.home.index', [
            'empresa'    => $empresa,
            'catalogo'   => $data['items'],
            'contactReservableItems' => collect($reservableData['items'])
                ->filter(fn ($item) => data_get($item, 'business_model') === CatalogType::BUSINESS_MODEL_SERVICES)
                ->values(),
            'pagination' => $data['pagination'],
            'catalogFilters' => $this->getCatalogFilters(),
            'tipo'       => $tipo,
            'search'     => $search,
            'customerVehicles' => $this->getCustomerVehicles(),
            'vehicleSpecifications' => $this->getActiveVehicleSpecifications(),
        ]);
    }

    /**
     * Endpoint AJAX para busqueda y filtrado (devuelve JSON).
     */
    public function buscar(Request $request)
    {
        $tipo = (string) $request->get('tipo', 'todos');
        $search = (string) $request->get('search', '');
        $page = (int) $request->get('page', 1);

        $data = $this->getCatalogItems($tipo, $search, $page);

        if ($request->ajax()) {
            return response()->json($data);
        }

        return redirect()->route('home', [
            'tipo' => $tipo,
            'search' => $search,
            'page' => $page,
        ]);
    }

    private function getCustomerVehicles()
    {
        if (!auth()->check()) {
            return collect();
        }

        return Vehicle::query()
            ->where('user_id', auth()->id())
            ->where('active', true)
            ->whereHas('specification.type', fn ($type) => $type->where('active', true))
            ->with([
                'specification.brand:id,name',
                'specification.model:id,name',
                'specification.type:id,name',
            ])
            ->orderBy('plate')
            ->get()
            ->map(fn ($vehicle) => [
                'id' => (int) $vehicle->id,
                'vehicle_specification_id' => (int) $vehicle->vehicle_specification_id,
                'vehicle_type_id' => (int) $vehicle->resolvedType()?->id,
                'label' => trim(sprintf(
                    '%s - %s %s',
                    $vehicle->plate,
                    $vehicle->resolvedBrand()?->name ?? '',
                    $vehicle->resolvedModel()?->name ?? ''
                )),
                'type_name' => $vehicle->resolvedType()?->name,
            ])
            ->filter(fn ($vehicle) => $vehicle['vehicle_type_id'] > 0)
            ->values();
    }

    private function getActiveVehicleSpecifications()
    {
        return VehicleSpecification::query()
            ->where('active', true)
            ->whereHas('brand', fn ($query) => $query->where('active', true))
            ->whereHas('model', fn ($query) => $query->where('active', true))
            ->whereHas('type', fn ($query) => $query->where('active', true))
            ->with(['brand:id,name', 'model:id,name', 'type:id,name'])
            ->ordered()
            ->get()
            ->map(fn ($specification) => [
                'id' => (int) $specification->id,
                'vehicle_type_id' => (int) $specification->type?->id,
                'name' => $specification->label,
                'type_name' => $specification->type?->name,
            ]);
    }
}
