<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\CatalogType;
use App\Models\Empresa;
use App\Support\Permissions\CatalogPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CatalogTypeController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.catalog.index');
    }

    public function create()
    {
        CatalogPermission::authorizeAny('create');
        $empresa = $this->getOrCreateEmpresa();

        return view('admin.catalog.types.create', compact('empresa'));
    }

    public function store(Request $request)
    {
        $empresa = $this->getOrCreateEmpresa();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'business_model' => ['required', Rule::in([
                CatalogType::BUSINESS_MODEL_SERVICES,
                CatalogType::BUSINESS_MODEL_PRODUCTS,
            ])],
            'active' => ['nullable', 'boolean'],
        ]);

        CatalogPermission::authorize($data['business_model'], 'create');

        $slug = $this->resolveSlug($empresa->id, $data['name'], $data['slug'] ?? null);

        $catalogType = CatalogType::create([
            'empresa_id' => $empresa->id,
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $this->cleanInput($data['description'] ?? null),
            'business_model' => $data['business_model'],
            'active' => $request->boolean('active', true),
        ]);

        NotificationHelper::success('Seccion creada correctamente. Continua agregando categorias o productos.');

        return redirect()->route('admin.catalog-types.show', $catalogType);
    }

    public function show(CatalogType $catalogType)
    {
        CatalogPermission::authorize($catalogType->business_model, 'view');
        $catalogType->loadCount('items');

        return view('admin.catalog.types.show', compact('catalogType'));
    }

    public function edit(CatalogType $catalogType)
    {
        CatalogPermission::authorize($catalogType->business_model, 'update');
        return view('admin.catalog.types.edit', compact('catalogType'));
    }

    public function update(Request $request, CatalogType $catalogType)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'business_model' => ['required', Rule::in([
                CatalogType::BUSINESS_MODEL_SERVICES,
                CatalogType::BUSINESS_MODEL_PRODUCTS,
            ])],
            'active' => ['nullable', 'boolean'],
        ]);

        CatalogPermission::authorize($catalogType->business_model, 'update');
        CatalogPermission::authorize($data['business_model'], 'update');

        $slug = $this->resolveSlug($catalogType->empresa_id, $data['name'], $data['slug'] ?? null, $catalogType->id);

        $catalogType->update([
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $this->cleanInput($data['description'] ?? null),
            'business_model' => $data['business_model'],
            'active' => $request->boolean('active'),
        ]);

        NotificationHelper::success('Tipo de catalogo actualizado correctamente.');

        return redirect()->route('admin.catalog-types.show', $catalogType);
    }

    public function destroy(CatalogType $catalogType)
    {
        CatalogPermission::authorize($catalogType->business_model, 'delete');
        $catalogType->delete();

        NotificationHelper::success('Tipo de catalogo eliminado correctamente.');

        return redirect()->route('admin.catalog.index');
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

    private function resolveSlug(int $empresaId, string $name, ?string $slug, ?int $ignoreId = null): ?string
    {
        $base = Str::slug($slug ?: $name);

        if ($base === '') {
            return null;
        }

        $candidate = $base;
        $suffix = 2;

        while (
            CatalogType::query()
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
