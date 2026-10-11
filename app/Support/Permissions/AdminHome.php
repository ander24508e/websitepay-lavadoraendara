<?php

namespace App\Support\Permissions;

use App\Models\User;

/**
 * Resuelve la primera pantalla del panel que el usuario puede abrir segun
 * los permisos de su rol (ej. un rol "Bodega" entra directo a Inventario).
 */
class AdminHome
{
    /**
     * @var array<int, array{0: array<int, string>, 1: string}>
     */
    private const ROUTES = [
        [['dashboard.view'], 'admin.dashboard'],
        [['sales.view'], 'admin.ventas.index'],
        [['sales.create'], 'admin.ventas.create'],
        [['orders.view'], 'admin.orders.index'],
        [['transactions.view'], 'admin.transactions.index'],
        [['catalog.products.view', 'catalog.services.view'], 'admin.catalog.index'],
        [['inventory.view'], 'admin.inventario.index'],
        [['inventory.purchases.view'], 'admin.inventario.purchases'],
        [['inventory.transfers.view'], 'admin.inventario.transfers'],
        [['inventory.returns.view'], 'admin.inventario.returns'],
        [['inventory.counts.view'], 'admin.inventario.counts'],
        [['inventory.locations.view'], 'admin.inventario.locations'],
        [['inventory.suppliers.view'], 'admin.inventario.suppliers'],
        [['inventory.reports.view'], 'admin.inventario.reports'],
        [['inventory.periods.view'], 'admin.inventario.periods'],
        [['vehicles.view'], 'admin.vehiculos.index'],
        [['vehicles.specifications.view'], 'admin.vehiculos.specifications.index'],
        [['clients.view'], 'admin.clientes.index'],
        [['users.view'], 'admin.usuarios.index'],
        [['users.create'], 'admin.usuarios.create'],
        [['roles.view'], 'admin.roles.index'],
        [['banners.view'], 'admin.banners.index'],
        [['banners.create'], 'admin.banners.create'],
        [['company.view'], 'admin.empresa.edit'],
    ];

    /**
     * Primera pantalla disponible dentro de una seccion del menu.
     *
     * @param  array<int, string>  $routes  nombres de ruta de la seccion
     */
    public static function sectionRoute(User $user, array $routes): ?string
    {
        foreach (self::ROUTES as [$permissions, $route]) {
            if (in_array($route, $routes, true) && $user->canAny($permissions)) {
                return $route;
            }
        }

        return null;
    }

    public static function inventoryRoute(User $user): ?string
    {
        return static::sectionRoute($user, [
            'admin.inventario.index',
            'admin.inventario.purchases',
            'admin.inventario.transfers',
            'admin.inventario.returns',
            'admin.inventario.counts',
            'admin.inventario.locations',
            'admin.inventario.suppliers',
            'admin.inventario.reports',
            'admin.inventario.periods',
        ]);
    }

    public static function staffRoute(User $user): ?string
    {
        return static::sectionRoute($user, ['admin.usuarios.index', 'admin.usuarios.create', 'admin.roles.index']);
    }

    public static function vehiclesRoute(User $user): ?string
    {
        return static::sectionRoute($user, ['admin.vehiculos.index', 'admin.vehiculos.specifications.index']);
    }

    public static function routeName(User $user): ?string
    {
        if (! $user->isStaff()) {
            return null;
        }

        foreach (self::ROUTES as [$permissions, $route]) {
            if ($user->canAny($permissions)) {
                return $route;
            }
        }

        return null;
    }
}
