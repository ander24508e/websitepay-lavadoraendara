<?php

/*
|--------------------------------------------------------------------------
| Catalogo de permisos del sistema
|--------------------------------------------------------------------------
|
| Unica fuente de verdad de los permisos. Cada permiso corresponde a una
| accion que el codigo verifica (rutas, controladores y vistas). Los roles
| se crean desde el modulo Roles y reciben permisos de esta lista.
| El seeder sincroniza la tabla `permissions` con este archivo.
|
*/

return [
    'groups' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'permissions' => [
                'dashboard.view' => 'Ver dashboard',
            ],
        ],
        'banners' => [
            'label' => 'Banners',
            'permissions' => [
                'banners.view' => 'Ver banners',
                'banners.create' => 'Crear banners',
                'banners.update' => 'Editar banners',
                'banners.delete' => 'Eliminar banners',
            ],
        ],
        'sales' => [
            'label' => 'Ventas',
            'permissions' => [
                'sales.view' => 'Ver ventas',
                'sales.create' => 'Registrar ventas',
                'sales.update' => 'Editar ventas',
                'sales.delete' => 'Eliminar ventas',
                'sales.collect' => 'Cobrar ventas y órdenes',
            ],
        ],
        'transactions' => [
            'label' => 'Pagos',
            'permissions' => [
                'transactions.view' => 'Ver pagos y transacciones',
            ],
        ],
        'orders' => [
            'label' => 'Órdenes',
            'permissions' => [
                'orders.view' => 'Ver órdenes',
                'orders.update' => 'Actualizar estado y datos operativos',
                'orders.delete' => 'Eliminar órdenes',
            ],
        ],
        'catalog_products' => [
            'label' => 'Catálogo · Productos',
            'permissions' => [
                'catalog.products.view' => 'Ver productos',
                'catalog.products.create' => 'Crear productos, secciones y categorías',
                'catalog.products.update' => 'Editar productos y presentaciones',
                'catalog.products.delete' => 'Eliminar productos',
            ],
        ],
        'catalog_services' => [
            'label' => 'Catálogo · Servicios',
            'permissions' => [
                'catalog.services.view' => 'Ver servicios',
                'catalog.services.create' => 'Crear servicios, secciones y categorías',
                'catalog.services.update' => 'Editar servicios y precios por vehículo',
                'catalog.services.delete' => 'Eliminar servicios',
            ],
        ],
        'inventory' => [
            'label' => 'Inventario',
            'permissions' => [
                'inventory.view' => 'Ver inventario y kardex',
                'inventory.move' => 'Registrar entradas, salidas y ajustes',
                'inventory.update' => 'Editar datos de movimientos',
                'inventory.void' => 'Anular movimientos',
                'inventory.view_costs' => 'Ver costos',
                'inventory.import' => 'Importar CSV',
                'inventory.export' => 'Exportar CSV',
            ],
        ],
        'inventory_locations' => [
            'label' => 'Inventario · Ubicaciones',
            'permissions' => [
                'inventory.locations.view' => 'Ver ubicaciones',
                'inventory.locations.create' => 'Crear ubicaciones',
            ],
        ],
        'inventory_suppliers' => [
            'label' => 'Inventario · Proveedores',
            'permissions' => [
                'inventory.suppliers.view' => 'Ver proveedores',
                'inventory.suppliers.create' => 'Crear proveedores',
            ],
        ],
        'inventory_purchases' => [
            'label' => 'Inventario · Compras',
            'permissions' => [
                'inventory.purchases.view' => 'Ver compras',
                'inventory.purchases.create' => 'Registrar compras',
            ],
        ],
        'inventory_transfers' => [
            'label' => 'Inventario · Transferencias',
            'permissions' => [
                'inventory.transfers.view' => 'Ver transferencias',
                'inventory.transfers.create' => 'Registrar transferencias',
            ],
        ],
        'inventory_returns' => [
            'label' => 'Inventario · Devoluciones',
            'permissions' => [
                'inventory.returns.view' => 'Ver devoluciones',
                'inventory.returns.create' => 'Registrar devoluciones',
            ],
        ],
        'inventory_counts' => [
            'label' => 'Inventario · Conteo físico',
            'permissions' => [
                'inventory.counts.view' => 'Ver conteos físicos',
                'inventory.counts.create' => 'Registrar conteos físicos',
            ],
        ],
        'inventory_reports' => [
            'label' => 'Inventario · Reportes',
            'permissions' => [
                'inventory.reports.view' => 'Ver reportes de inventario',
            ],
        ],
        'inventory_periods' => [
            'label' => 'Inventario · Cierres',
            'permissions' => [
                'inventory.periods.view' => 'Ver cierres de periodo',
                'inventory.periods.create' => 'Cerrar periodos',
            ],
        ],
        'vehicles' => [
            'label' => 'Vehículos',
            'permissions' => [
                'vehicles.view' => 'Ver vehículos',
                'vehicles.create' => 'Registrar vehículos',
                'vehicles.update' => 'Editar vehículos',
                'vehicles.delete' => 'Eliminar vehículos',
            ],
        ],
        'vehicle_specifications' => [
            'label' => 'Vehículos · Especificaciones',
            'permissions' => [
                'vehicles.specifications.view' => 'Ver marcas, modelos y tipos',
                'vehicles.specifications.create' => 'Crear marcas, modelos y tipos',
                'vehicles.specifications.update' => 'Editar marcas, modelos y tipos',
                'vehicles.specifications.delete' => 'Eliminar marcas, modelos y tipos',
            ],
        ],
        'clients' => [
            'label' => 'Clientes',
            'permissions' => [
                'clients.view' => 'Ver clientes',
                'clients.create' => 'Crear clientes',
                'clients.update' => 'Editar clientes',
                'clients.delete' => 'Eliminar clientes',
            ],
        ],
        'users' => [
            'label' => 'Usuarios',
            'permissions' => [
                'users.view' => 'Ver usuarios',
                'users.create' => 'Crear usuarios',
                'users.update' => 'Editar usuarios',
                'users.deactivate' => 'Desactivar usuarios',
            ],
        ],
        'roles' => [
            'label' => 'Roles',
            'permissions' => [
                'roles.view' => 'Ver roles',
                'roles.create' => 'Crear roles',
                'roles.update' => 'Editar roles',
                'roles.delete' => 'Eliminar roles',
            ],
        ],
        'company' => [
            'label' => 'Empresa',
            'permissions' => [
                'company.view' => 'Ver datos de la empresa',
                'company.update' => 'Editar datos de la empresa',
            ],
        ],
    ],
];
