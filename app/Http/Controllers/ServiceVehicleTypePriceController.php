<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\CatalogItem;
use App\Models\CatalogItemSupply;
use App\Models\CatalogItemVariant;
use App\Models\CatalogType;
use App\Models\ServiceVehicleTypePrice;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\VehicleSpecification;
use App\Models\VehicleType;
use App\Support\Permissions\CatalogPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ServiceVehicleTypePriceController extends Controller
{
    public function create(Request $request, CatalogItem $catalogItem)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_SERVICES, 'update');
        $service = $this->serviceItem($catalogItem);
        $serviceVehicleTypePrice = null;
        $vehicleSpecifications = $this->vehicleSpecifications();
        $vehicleBrands = $this->vehicleBrands();
        $vehicleModels = $this->vehicleModels();
        $vehicleTypes = $this->vehicleTypes();
        $supplyVariants = $this->supplyVariants();
        $supplies = [];
        $returnUrl = route('admin.catalog-items.show', ['catalogItem' => $service, ...$request->only('return_to_type')]);

        return view('admin.catalog.service-prices.create', compact(
            'service',
            'serviceVehicleTypePrice',
            'vehicleSpecifications',
            'vehicleBrands',
            'vehicleModels',
            'vehicleTypes',
            'supplyVariants',
            'supplies',
            'returnUrl'
        ));
    }

    public function store(Request $request, CatalogItem $catalogItem)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_SERVICES, 'update');
        $service = $this->serviceItem($catalogItem);
        $data = $this->validatedData($request);
        $vehicleSpecification = $this->resolveVehicleSpecification($data);
        $vehicleType = $vehicleSpecification->type;

        $duplicate = $this->duplicatePriceExists($service, $vehicleSpecification);

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['vehicle_specification_id' => 'Este vehículo ya tiene precio para este servicio.']);
        }

        $price = ServiceVehicleTypePrice::create($this->priceAttributes($service, $vehicleSpecification, $vehicleType, $data, $request->boolean('active', true)));

        $this->syncSupplies($service->id, $price, $data['supplies'] ?? []);
        NotificationHelper::success('Precio por vehiculo creado correctamente.');

        return redirect()->route('admin.catalog-items.show', $service);
    }

    private function priceAttributes(
        CatalogItem $service,
        VehicleSpecification $vehicleSpecification,
        VehicleType $vehicleType,
        array $data,
        bool $active
    ): array {
        $attributes = [
            'catalog_item_id' => $service->id,
            'vehicle_type_id' => $vehicleType->id,
            'price' => (float) $data['price'],
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'description' => $this->cleanInput($data['description'] ?? null),
            'active' => $active,
        ];

        if ($this->supportsVehicleSpecifications()) {
            $attributes['vehicle_specification_id'] = $vehicleSpecification->id;
        }

        return $attributes;
    }

    public function edit(Request $request, ServiceVehicleTypePrice $serviceVehicleTypePrice)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_SERVICES, 'update');
        $serviceVehicleTypePrice->load([
            'service.type',
            'service.category',
            'supplies',
            'vehicleSpecification.brand',
            'vehicleSpecification.model',
            'vehicleSpecification.type',
            'vehicleType',
        ]);
        $service = $this->serviceItem($serviceVehicleTypePrice->service);
        $vehicleSpecifications = $this->vehicleSpecifications();
        $vehicleBrands = $this->vehicleBrands();
        $vehicleModels = $this->vehicleModels();
        $vehicleTypes = $this->vehicleTypes();
        $supplyVariants = $this->supplyVariants();
        $supplies = $serviceVehicleTypePrice->supplies->values()->all();
        $returnUrl = route('admin.catalog-items.show', ['catalogItem' => $service, ...$request->only('return_to_type')]);

        return view('admin.catalog.service-prices.edit', compact(
            'service',
            'serviceVehicleTypePrice',
            'vehicleSpecifications',
            'vehicleBrands',
            'vehicleModels',
            'vehicleTypes',
            'supplyVariants',
            'supplies',
            'returnUrl'
        ));
    }

    public function update(Request $request, ServiceVehicleTypePrice $serviceVehicleTypePrice)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_SERVICES, 'update');
        $serviceVehicleTypePrice->load('service.type');
        $service = $this->serviceItem($serviceVehicleTypePrice->service);
        $data = $this->validatedData($request);
        $vehicleSpecification = $this->resolveVehicleSpecification($data);
        $vehicleType = $vehicleSpecification->type;

        $duplicate = $this->duplicatePriceExists($service, $vehicleSpecification, $serviceVehicleTypePrice->id);

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['vehicle_specification_id' => 'Este vehículo ya tiene precio para este servicio.']);
        }

        $serviceVehicleTypePrice->update($this->priceAttributes($service, $vehicleSpecification, $vehicleType, $data, $request->boolean('active')));

        $this->syncSupplies($service->id, $serviceVehicleTypePrice, $data['supplies'] ?? []);
        NotificationHelper::success('Precio por vehiculo actualizado correctamente.');

        return redirect()->route('admin.catalog-items.show', $service);
    }

    public function destroy(ServiceVehicleTypePrice $serviceVehicleTypePrice)
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_SERVICES, 'update');
        $serviceVehicleTypePrice->load('service.type');
        $service = $this->serviceItem($serviceVehicleTypePrice->service);
        $serviceVehicleTypePrice->delete();

        NotificationHelper::success('Precio por vehiculo eliminado correctamente.');

        return redirect()->route('admin.catalog-items.show', $service);
    }

    private function serviceItem(CatalogItem $item): CatalogItem
    {
        $item->loadMissing('type');

        if (($item->type?->business_model ?? null) !== CatalogType::BUSINESS_MODEL_SERVICES) {
            abort(404);
        }

        return $item;
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'vehicle_specification_id' => ['nullable', 'integer', 'exists:vehicle_specifications,id'],
            'vehicle_brand_id' => ['nullable', 'integer', 'exists:vehicle_brands,id'],
            'new_vehicle_brand_name' => ['nullable', 'string', 'max:255'],
            'vehicle_model_id' => ['nullable', 'integer', 'exists:vehicle_models,id'],
            'new_vehicle_model_name' => ['nullable', 'string', 'max:255'],
            'vehicle_type_id' => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'new_vehicle_type_name' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['nullable', 'boolean'],
            'supplies' => ['nullable', 'array'],
            'supplies.*.catalog_item_variant_id' => ['nullable', 'integer', 'exists:catalog_item_variants,id'],
            'supplies.*.quantity' => ['nullable', 'numeric', 'min:0.001', 'max:999999.999'],
            'supplies.*.unit' => ['nullable', 'string', 'max:30'],
        ]);
    }

    private function resolveVehicleSpecification(array $data): VehicleSpecification
    {
        $selectedSpecificationId = (int) ($data['vehicle_specification_id'] ?? 0);

        if ($selectedSpecificationId > 0 && !$this->hasManualSpecificationInput($data)) {
            $vehicleSpecification = VehicleSpecification::query()
                ->where('active', true)
                ->with(['brand', 'model', 'type'])
                ->find($selectedSpecificationId);

            if ($vehicleSpecification) {
                return $vehicleSpecification;
            }

            throw ValidationException::withMessages([
                'vehicle_specification_id' => 'La especificación seleccionada no está disponible.',
            ]);
        }

        if ($selectedSpecificationId <= 0 && !$this->hasManualSpecificationInput($data)) {
            throw ValidationException::withMessages([
                'vehicle_specification_id' => 'Selecciona una especificación existente o crea una nueva.',
            ]);
        }

        $brand = $this->resolveVehicleBrand($data);
        $model = $this->resolveVehicleModel($data, $brand);
        $vehicleType = $this->resolveVehicleType($data);

        return VehicleSpecification::query()->firstOrCreate([
            'vehicle_brand_id' => $brand->id,
            'vehicle_model_id' => $model->id,
            'vehicle_type_id' => $vehicleType->id,
        ], ['active' => true]);
    }

    private function duplicatePriceExists(CatalogItem $service, VehicleSpecification $vehicleSpecification, ?int $ignoreId = null): bool
    {
        $query = $service->vehicleTypePrices();

        if ($this->supportsVehicleSpecifications()) {
            $query->where('vehicle_specification_id', $vehicleSpecification->id);
        } else {
            $query->where('vehicle_type_id', $vehicleSpecification->vehicle_type_id);
        }

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    private function supportsVehicleSpecifications(): bool
    {
        static $supports = null;

        if ($supports === null) {
            $supports = Schema::hasColumn('service_vehicle_type_prices', 'vehicle_specification_id');
        }

        return $supports;
    }

    private function hasManualSpecificationInput(array $data): bool
    {
        foreach ([
            'vehicle_brand_id',
            'new_vehicle_brand_name',
            'vehicle_model_id',
            'new_vehicle_model_name',
            'vehicle_type_id',
            'new_vehicle_type_name',
        ] as $key) {
            if ($this->cleanInput((string) ($data[$key] ?? '')) !== null) {
                return true;
            }
        }

        return false;
    }

    private function resolveVehicleBrand(array $data): VehicleBrand
    {
        $newName = $this->cleanInput($data['new_vehicle_brand_name'] ?? null);

        if ($newName) {
            return VehicleBrand::firstOrCreate(['name' => $newName], ['active' => true]);
        }

        $brandId = (int) ($data['vehicle_brand_id'] ?? 0);

        if ($brandId <= 0) {
            throw ValidationException::withMessages([
                'vehicle_brand_id' => 'Selecciona o crea una marca.',
            ]);
        }

        return VehicleBrand::query()->findOrFail($brandId);
    }

    private function resolveVehicleModel(array $data, VehicleBrand $brand): VehicleModel
    {
        $newName = $this->cleanInput($data['new_vehicle_model_name'] ?? null);

        if ($newName) {
            return VehicleModel::firstOrCreate([
                'vehicle_brand_id' => $brand->id,
                'name' => $newName,
            ], ['active' => true]);
        }

        $modelId = (int) ($data['vehicle_model_id'] ?? 0);

        if ($modelId <= 0) {
            throw ValidationException::withMessages([
                'vehicle_model_id' => 'Selecciona o crea un modelo.',
            ]);
        }

        $model = VehicleModel::query()
            ->where('vehicle_brand_id', $brand->id)
            ->whereKey($modelId)
            ->first();

        if (!$model) {
            throw ValidationException::withMessages([
                'vehicle_model_id' => 'El modelo seleccionado no pertenece a la marca.',
            ]);
        }

        return $model;
    }

    private function resolveVehicleType(array $data): VehicleType
    {
        $newName = $this->cleanInput($data['new_vehicle_type_name'] ?? null);

        if ($newName) {
            return VehicleType::firstOrCreate(['name' => $newName], ['active' => true]);
        }

        $vehicleTypeId = (int) ($data['vehicle_type_id'] ?? 0);

        if ($vehicleTypeId <= 0) {
            throw ValidationException::withMessages([
                'vehicle_type_id' => 'Selecciona o crea un tipo de vehiculo.',
            ]);
        }

        return VehicleType::query()->findOrFail($vehicleTypeId);
    }

    private function vehicleBrands()
    {
        return VehicleBrand::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'active']);
    }

    private function vehicleModels()
    {
        return VehicleModel::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'vehicle_brand_id', 'name', 'active']);
    }

    private function vehicleSpecifications()
    {
        return VehicleSpecification::query()
            ->where('active', true)
            ->with(['brand:id,name', 'model:id,name,vehicle_brand_id', 'type:id,name'])
            ->ordered()
            ->get(['id', 'vehicle_brand_id', 'vehicle_model_id', 'vehicle_type_id', 'active']);
    }

    private function syncSupplies(int $serviceId, ServiceVehicleTypePrice $price, array $supplies): void
    {
        $validVariantIds = $this->supplyVariants()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $keptSupplyIds = [];

        foreach ($supplies as $supply) {
            $variantId = (int) ($supply['catalog_item_variant_id'] ?? 0);
            $quantity = $supply['quantity'] ?? null;

            if (!in_array($variantId, $validVariantIds, true) || $quantity === null || $quantity === '') {
                continue;
            }

            $row = CatalogItemSupply::updateOrCreate(
                [
                    'service_vehicle_type_price_id' => $price->id,
                    'catalog_item_variant_id' => $variantId,
                ],
                [
                    'catalog_item_id' => $serviceId,
                    'quantity' => (float) $quantity,
                    'unit' => $this->cleanInput($supply['unit'] ?? null),
                ]
            );

            $keptSupplyIds[] = $row->id;
        }

        $price->supplies()
            ->whereNotIn('id', $keptSupplyIds)
            ->delete();
    }

    private function vehicleTypes()
    {
        return VehicleType::query()
            ->where('active', true)
            ->ordered()
            ->get();
    }

    private function supplyVariants()
    {
        return CatalogItemVariant::query()
            ->where('active', true)
            ->whereHas('item.type', fn ($query) => $query->where('business_model', CatalogType::BUSINESS_MODEL_PRODUCTS))
            ->with('item:id,name')
            ->orderBy('name')
            ->get(['id', 'catalog_item_id', 'name', 'sku', 'active']);
    }

    private function cleanInput(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
