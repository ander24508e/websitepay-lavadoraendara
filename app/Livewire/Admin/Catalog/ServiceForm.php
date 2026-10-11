<?php

namespace App\Livewire\Admin\Catalog;

use App\Helpers\NotificationHelper;
use App\Models\CatalogCategory;
use App\Models\CatalogItem;
use App\Models\CatalogType;
use App\Models\Empresa;
use App\Models\ServiceVehicleTypePrice;
use App\Support\Permissions\CatalogPermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ServiceForm extends Component
{
    use WithFileUploads;

    public ?int $catalogItemId = null;
    public int $selectedTypeId = 0;
    public $selectedCategoryId = null;
    public string $name = '';
    public string $description = '';
    public bool $active = true;
    public bool $featured = false;
    public bool $reservable = false;
    public $image = null;
    public ?string $currentImage = null;
    public string $returnUrl;
    public bool $showCategoryModal = false;
    public string $newCategoryName = '';
    public string $newCategoryDescription = '';
    public bool $highlightPrices = false;
    public int $highlightKey = 0;

    public function mount(
        ?int $catalogItemId = null,
        int $selectedTypeId = 0,
        ?int $selectedCategoryId = null,
        ?string $returnUrl = null
    ): void {
        $this->catalogItemId = $catalogItemId;
        $this->selectedTypeId = $selectedTypeId;
        $this->selectedCategoryId = $selectedCategoryId ?: null;

        if ($catalogItemId) {
            $item = CatalogItem::query()
                ->findOrFail($catalogItemId);

            $this->selectedTypeId = (int) $item->catalog_type_id;
            $this->selectedCategoryId = $item->catalog_category_id ? (int) $item->catalog_category_id : null;
            $this->name = (string) $item->name;
            $this->description = (string) ($item->description ?? '');
            $this->active = (bool) $item->active;
            $this->featured = (bool) $item->featured;
            $this->reservable = (bool) $item->reservable;
            $this->currentImage = $item->image;
            $this->returnUrl = $returnUrl ?: route('admin.catalog-items.show', $item);
            return;
        }

        $type = $this->selectedTypeId > 0
            ? CatalogType::query()->where('business_model', CatalogType::BUSINESS_MODEL_SERVICES)->find($this->selectedTypeId)
            : null;

        if (!$type) {
            $type = CatalogType::query()
                ->where('business_model', CatalogType::BUSINESS_MODEL_SERVICES)
                ->ordered()
                ->first();
        }

        $this->selectedTypeId = (int) ($type?->id ?? 0);
        $this->returnUrl = $returnUrl ?: ($this->selectedTypeId > 0
            ? route('admin.catalog-types.show', $this->selectedTypeId)
            : route('admin.catalog.index'));
    }

    public function save(): void
    {
        CatalogPermission::authorize(CatalogType::BUSINESS_MODEL_SERVICES, $this->catalogItemId ? 'update' : 'create');
        $this->selectedCategoryId = $this->selectedCategoryId ?: null;
        $data = $this->validate($this->serviceRules());
        $empresa = $this->getOrCreateEmpresa();
        $type = CatalogType::query()
            ->where('empresa_id', $empresa->id)
            ->where('business_model', CatalogType::BUSINESS_MODEL_SERVICES)
            ->findOrFail($this->selectedTypeId);

        $category = $this->resolveCategory($empresa->id, $type->id, $this->selectedCategoryId);

        if ($this->cleanInput($this->newCategoryName)) {
            $category = $this->createInlineCategory($empresa->id, $type->id, $this->newCategoryName, $this->newCategoryDescription);
            $this->selectedCategoryId = $category->id;
            $this->newCategoryName = '';
            $this->newCategoryDescription = '';
        }

        $payload = [
            'empresa_id' => $empresa->id,
            'catalog_type_id' => $type->id,
            'catalog_category_id' => $category?->id,
            'name' => trim($data['name']),
            'slug' => $this->resolveSlug($empresa->id, $data['name'], null, $this->catalogItemId),
            'description' => $this->cleanInput($data['description'] ?? null),
            'base_price' => null,
            'duration_minutes' => null,
            'active' => (bool) $data['active'],
            'featured' => (bool) $data['featured'],
            'purchasable' => true,
            'reservable' => (bool) $data['reservable'],
            'uses_inventory' => false,
        ];

        if ($this->image) {
            if ($this->currentImage && Storage::disk('public')->exists($this->currentImage)) {
                Storage::disk('public')->delete($this->currentImage);
            }

            $payload['image'] = $this->image->store('catalog_items', 'public');
        }

        if ($this->catalogItemId) {
            $item = CatalogItem::query()->findOrFail($this->catalogItemId);
            $item->update($payload);
            NotificationHelper::success('Servicio actualizado correctamente.');
        } else {
            $item = CatalogItem::create($payload);
            $this->catalogItemId = $item->id;
            NotificationHelper::success('Servicio guardado correctamente. Ahora puedes agregar sus precios por vehiculo.');
        }

        $this->currentImage = $item->fresh()->image;
        $this->image = null;
        $this->highlightPrices = true;
        $this->highlightKey++;
        $this->dispatch('catalog-service-saved');
    }

    public function openCategoryModal(): void
    {
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
    }

    public function applyCategory(): void
    {
        if (!$this->cleanInput($this->newCategoryName)) {
            $this->addError('newCategoryName', 'El nombre de la categoria es requerido.');
            return;
        }

        $this->selectedCategoryId = null;
        $this->showCategoryModal = false;
        $this->resetErrorBag('newCategoryName');
    }

    public function clearInlineCategory(): void
    {
        if ($this->selectedCategoryId) {
            $this->newCategoryName = '';
            $this->newCategoryDescription = '';
        }
    }

    public function render()
    {
        $types = $this->serviceTypes();
        $selectedType = $types->firstWhere('id', $this->selectedTypeId);
        $categories = $this->categories();
        $priceCount = $this->catalogItemId
            ? ServiceVehicleTypePrice::query()->where('catalog_item_id', $this->catalogItemId)->count()
            : 0;

        return view('livewire.admin.catalog.service-form', [
            'types' => $types,
            'selectedType' => $selectedType,
            'categories' => $categories,
            'priceCount' => $priceCount,
            'priceManagementUrl' => $this->priceManagementUrl(),
        ]);
    }

    private function priceManagementUrl(): ?string
    {
        if (!$this->catalogItemId) {
            return null;
        }

        return route('admin.catalog-service-prices.create', [
            'catalogItem' => $this->catalogItemId,
        ]);
    }

    private function serviceRules(): array
    {
        return [
            'selectedTypeId' => ['required', 'integer', 'exists:catalog_types,id'],
            'selectedCategoryId' => ['nullable', 'integer', 'exists:catalog_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
            'featured' => ['boolean'],
            'reservable' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ];
    }

    private function serviceTypes(): Collection
    {
        return CatalogType::query()
            ->where('empresa_id', $this->getOrCreateEmpresa()->id)
            ->where('business_model', CatalogType::BUSINESS_MODEL_SERVICES)
            ->ordered()
            ->get();
    }

    private function categories(): Collection
    {
        if (!$this->selectedTypeId) {
            return collect();
        }

        return CatalogCategory::query()
            ->where('empresa_id', $this->getOrCreateEmpresa()->id)
            ->where('catalog_type_id', $this->selectedTypeId)
            ->ordered()
            ->get();
    }

    private function getOrCreateEmpresa(): Empresa
    {
        return Empresa::query()->first() ?? Empresa::create([
            'nombre' => 'Mi negocio',
        ]);
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

    private function resolveSlug(int $empresaId, string $name, ?string $slug, ?int $ignoreId = null): ?string
    {
        $base = Str::slug($this->cleanInput($slug) ?: $name);
        $candidate = $base;
        $suffix = 2;

        while (
            $candidate !== ''
            && CatalogItem::query()
                ->where('empresa_id', $empresaId)
                ->where('slug', $candidate)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate ?: null;
    }

    private function cleanInput(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
