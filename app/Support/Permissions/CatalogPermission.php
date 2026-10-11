<?php

namespace App\Support\Permissions;

use App\Models\CatalogType;
use App\Models\User;

/**
 * Los permisos del catalogo se separan por modelo de negocio:
 * catalog.products.* y catalog.services.*
 */
class CatalogPermission
{
    public static function name(?string $businessModel, string $action): string
    {
        $segment = $businessModel === CatalogType::BUSINESS_MODEL_PRODUCTS ? 'products' : 'services';

        return "catalog.{$segment}.{$action}";
    }

    public static function allows(?string $businessModel, string $action, ?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $user?->can(static::name($businessModel, $action));
    }

    public static function authorize(?string $businessModel, string $action): void
    {
        abort_unless(static::allows($businessModel, $action), 403);
    }

    public static function allowsAny(string $action, ?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $user?->canAny([
            "catalog.products.{$action}",
            "catalog.services.{$action}",
        ]);
    }

    public static function authorizeAny(string $action): void
    {
        abort_unless(static::allowsAny($action), 403);
    }

    /**
     * Modelos de negocio que el usuario puede ver.
     *
     * @return array<int, string>
     */
    public static function visibleBusinessModels(?User $user = null): array
    {
        return array_values(array_filter([
            static::allows(CatalogType::BUSINESS_MODEL_PRODUCTS, 'view', $user) ? CatalogType::BUSINESS_MODEL_PRODUCTS : null,
            static::allows(CatalogType::BUSINESS_MODEL_SERVICES, 'view', $user) ? CatalogType::BUSINESS_MODEL_SERVICES : null,
        ]));
    }
}
