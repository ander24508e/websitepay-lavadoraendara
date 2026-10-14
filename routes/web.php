<?php

use App\Http\Controllers\Admin\ClientesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventarioController;
use App\Http\Controllers\Admin\InventoryOperationsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\UsuariosController;
use App\Http\Controllers\Admin\VehicleSpecificationsController;
use App\Http\Controllers\Admin\VehiculosController;
use App\Http\Controllers\Admin\VentasController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogCategoryController;
use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\CatalogItemVariantController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CatalogTypeController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceVehicleTypePriceController;
use App\Http\Controllers\TransactionController;
use App\Models\CatalogItem;
use App\Models\CatalogItemVariant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Transaction;
use App\Services\CheckoutReceiptService;
use App\Support\Permissions\AdminHome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Rutas publicas
Route::get('/', [CatalogoController::class, 'index'])->name('home');
Route::redirect('/catalogo', '/');
Route::get('/catalogo/buscar', [CatalogoController::class, 'buscar'])->name('catalogo.buscar');

// Carrito publico
Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito.index');
Route::post('/carrito/agregar', [CarritoController::class, 'agregar'])->name('carrito.agregar');
Route::patch('/carrito/actualizar/{id}', [CarritoController::class, 'actualizar'])->name('carrito.actualizar');
Route::delete('/carrito/quitar/{id}', [CarritoController::class, 'quitar'])->name('carrito.quitar');
Route::delete('/carrito/limpiar', [CarritoController::class, 'limpiar'])->name('carrito.limpiar');

// Checkout y pagos publicos (invitado o autenticado)
Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
Route::post('/orden/crear', [OrderController::class, 'store'])->name('orden.store');
Route::post('/orden/cajita', [OrderController::class, 'prepareBox'])->name('orden.cajita');
Route::post('/reservas/catalogo', [OrderController::class, 'reservarCatalogo'])->name('reservas.catalogo');
Route::get('/orden/{order}/confirmacion', [OrderController::class, 'confirmacion'])->name('orden.confirmacion');
Route::get('/orden/{order}/comprobante', [OrderController::class, 'comprobante'])->name('orden.comprobante');
Route::get('/orden/{order}/comprobante/descargar', [OrderController::class, 'descargarComprobante'])->name('orden.comprobante.descargar');
Route::get('/transaccion-exitosa', [TransactionController::class, 'success'])->name('transaccion.exitosa');
Route::get('/payphone/success', [TransactionController::class, 'success'])->name('payphone.success');
Route::get('/payphone/cancel', [TransactionController::class, 'cancel'])->name('payphone.cancel');

if (app()->environment('local')) {
    Route::get('/dev/preview/confirmacion', function () {
        $order = Order::query()
            ->with(['items.itemable', 'transaction', 'user'])
            ->latest()
            ->first();

        if (! $order) {
            $order = new Order([
                'user_id' => null,
                'total' => 15.00,
                'status' => 'paid',
                'order_type' => 'purchase',
            ]);
            $order->id = 999999;
            $order->created_at = now();
            $order->updated_at = now();

            $item = new OrderItem([
                'quantity' => 1,
                'unit_price' => 15.00,
            ]);
            $item->setRelation('itemable', new CatalogItem(['name' => 'Lavada Completa']));

            $transaction = new Transaction([
                'payphone_ref' => 'PREVIEW-'.now()->format('YmdHis'),
                'amount' => 15.00,
                'status' => 'approved',
                'client_transaction_id' => 'preview-'.Str::uuid(),
            ]);

            $order->setRelation('items', collect([$item]));
            $order->setRelation('transaction', $transaction);
            $order->setRelation('user', auth()->user());
        }

        $receipt = app(CheckoutReceiptService::class)->build($order);

        return view('checkout.confirmacion', ['order' => $order, ...$receipt]);
    })->name('dev.preview.confirmacion');

    Route::get('/dev/preview/comprobante', function () {
        $order = Order::query()
            ->with(['items.itemable', 'transaction', 'user'])
            ->latest()
            ->first();

        if (! $order) {
            abort(404, 'Crea una orden o usa primero la previsualizacion de confirmacion.');
        }

        $receipt = app(CheckoutReceiptService::class)->build($order);

        return view('checkout.comprobante', ['order' => $order, ...$receipt]);
    })->name('dev.preview.comprobante');
}

// Redireccion post-login: el personal entra al primer modulo que su rol permite
Route::get('/dashboard', function () {
    $route = AdminHome::routeName(Auth::user());

    return redirect()->route($route ?? 'home');
})->middleware(['auth', 'active'])->name('dashboard');

// Rutas autenticadas
Route::middleware(['auth', 'active'])->group(function () {
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/account', [ProfileController::class, 'updateAccount'])->name('profile.account.update');
    Route::patch('/profile/security', [ProfileController::class, 'updateSecurity'])->name('profile.security.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Detalle de orden para usuarios autenticados
    Route::get('/orden/{order}', [OrderController::class, 'show'])->name('orden.show');
});

// Panel cliente
Route::middleware(['auth', 'active', 'role:cliente'])
    ->prefix('/customer')
    ->name('customer.')
    ->group(function () {
        Route::get('/compras', [ClienteController::class, 'compras'])->name('compras');
    });

// Panel admin: acceso para el personal (rol interno activo); cada ruta exige su permiso
Route::middleware(['auth', 'active', 'staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');

        // Ventas y pagos
        Route::get('/ventas/pagos', [TransactionController::class, 'index'])->middleware('permission:transactions.view')->name('transactions.index');
        Route::get('/ventas/pagos/{transaction}', [TransactionController::class, 'show'])->middleware('permission:transactions.view')->name('transactions.show');
        Route::get('/ventas', [VentasController::class, 'index'])->middleware('permission:sales.view')->name('ventas.index');
        Route::get('/ventas/create', [VentasController::class, 'create'])->middleware('permission:sales.create')->name('ventas.create');
        Route::post('/ventas', [VentasController::class, 'store'])->middleware('permission:sales.create')->name('ventas.store');
        Route::get('/ventas/{venta}', [VentasController::class, 'show'])->middleware('permission:sales.view')->name('ventas.show');
        Route::get('/ventas/{venta}/edit', [VentasController::class, 'edit'])->middleware('permission:sales.update')->name('ventas.edit');
        Route::match(['put', 'patch'], '/ventas/{venta}', [VentasController::class, 'update'])->middleware('permission:sales.update')->name('ventas.update');
        Route::delete('/ventas/{venta}', [VentasController::class, 'destroy'])->middleware('permission:sales.delete')->name('ventas.destroy');

        // Clientes
        Route::post('/clientes/quick-store', [ClientesController::class, 'quickStore'])->middleware('permission:clients.create')->name('clientes.quick-store');
        Route::get('/clientes', [ClientesController::class, 'index'])->middleware('permission:clients.view')->name('clientes.index');
        Route::get('/clientes/create', [ClientesController::class, 'create'])->middleware('permission:clients.create')->name('clientes.create');
        Route::post('/clientes', [ClientesController::class, 'store'])->middleware('permission:clients.create')->name('clientes.store');
        Route::get('/clientes/{cliente}', [ClientesController::class, 'show'])->middleware('permission:clients.view')->name('clientes.show');
        Route::get('/clientes/{cliente}/edit', [ClientesController::class, 'edit'])->middleware('permission:clients.update')->name('clientes.edit');
        Route::match(['put', 'patch'], '/clientes/{cliente}', [ClientesController::class, 'update'])->middleware('permission:clients.update')->name('clientes.update');
        Route::delete('/clientes/{cliente}', [ClientesController::class, 'destroy'])->middleware('permission:clients.delete')->name('clientes.destroy');

        // Vehiculos y especificaciones
        Route::post('/vehiculos/quick-store', [VehiculosController::class, 'quickStore'])->middleware('permission:vehicles.create')->name('vehiculos.quick-store');
        Route::get('/vehiculos/especificaciones', [VehicleSpecificationsController::class, 'index'])
            ->middleware('permission:vehicles.specifications.view')->name('vehiculos.specifications.index');
        Route::post('/vehiculos/especificaciones/relaciones', [VehicleSpecificationsController::class, 'storeSpecification'])
            ->middleware('permission:vehicles.specifications.create')->name('vehiculos.specifications.store');
        Route::put('/vehiculos/especificaciones/relaciones/{vehicleSpecification}', [VehicleSpecificationsController::class, 'updateSpecification'])
            ->middleware('permission:vehicles.specifications.update')->name('vehiculos.specifications.update');
        Route::delete('/vehiculos/especificaciones/relaciones/{vehicleSpecification}', [VehicleSpecificationsController::class, 'destroySpecification'])
            ->middleware('permission:vehicles.specifications.delete')->name('vehiculos.specifications.destroy');
        Route::post('/vehiculos/especificaciones/tipos', [VehicleSpecificationsController::class, 'storeType'])
            ->middleware('permission:vehicles.specifications.create')->name('vehiculos.specifications.types.store');
        Route::put('/vehiculos/especificaciones/tipos/{vehicleType}', [VehicleSpecificationsController::class, 'updateType'])
            ->middleware('permission:vehicles.specifications.update')->name('vehiculos.specifications.types.update');
        Route::delete('/vehiculos/especificaciones/tipos/{vehicleType}', [VehicleSpecificationsController::class, 'destroyType'])
            ->middleware('permission:vehicles.specifications.delete')->name('vehiculos.specifications.types.destroy');
        Route::post('/vehiculos/especificaciones/marcas', [VehicleSpecificationsController::class, 'storeBrand'])
            ->middleware('permission:vehicles.specifications.create')->name('vehiculos.specifications.brands.store');
        Route::put('/vehiculos/especificaciones/marcas/{vehicleBrand}', [VehicleSpecificationsController::class, 'updateBrand'])
            ->middleware('permission:vehicles.specifications.update')->name('vehiculos.specifications.brands.update');
        Route::delete('/vehiculos/especificaciones/marcas/{vehicleBrand}', [VehicleSpecificationsController::class, 'destroyBrand'])
            ->middleware('permission:vehicles.specifications.delete')->name('vehiculos.specifications.brands.destroy');
        Route::post('/vehiculos/especificaciones/modelos', [VehicleSpecificationsController::class, 'storeModel'])
            ->middleware('permission:vehicles.specifications.create')->name('vehiculos.specifications.models.store');
        Route::put('/vehiculos/especificaciones/modelos/{vehicleModel}', [VehicleSpecificationsController::class, 'updateModel'])
            ->middleware('permission:vehicles.specifications.update')->name('vehiculos.specifications.models.update');
        Route::delete('/vehiculos/especificaciones/modelos/{vehicleModel}', [VehicleSpecificationsController::class, 'destroyModel'])
            ->middleware('permission:vehicles.specifications.delete')->name('vehiculos.specifications.models.destroy');
        Route::get('/vehiculos', [VehiculosController::class, 'index'])->middleware('permission:vehicles.view')->name('vehiculos.index');
        Route::get('/vehiculos/create', [VehiculosController::class, 'create'])->middleware('permission:vehicles.create')->name('vehiculos.create');
        Route::post('/vehiculos', [VehiculosController::class, 'store'])->middleware('permission:vehicles.create')->name('vehiculos.store');
        Route::get('/vehiculos/{vehiculo}', [VehiculosController::class, 'show'])->middleware('permission:vehicles.view')->name('vehiculos.show');
        Route::get('/vehiculos/{vehiculo}/edit', [VehiculosController::class, 'edit'])->middleware('permission:vehicles.update')->name('vehiculos.edit');
        Route::match(['put', 'patch'], '/vehiculos/{vehiculo}', [VehiculosController::class, 'update'])->middleware('permission:vehicles.update')->name('vehiculos.update');
        Route::delete('/vehiculos/{vehiculo}', [VehiculosController::class, 'destroy'])->middleware('permission:vehicles.delete')->name('vehiculos.destroy');

        // Usuarios (personal interno)
        Route::get('/usuarios', [UsuariosController::class, 'index'])->middleware('permission:users.view')->name('usuarios.index');
        Route::get('/usuarios/create', [UsuariosController::class, 'create'])->middleware('permission:users.create')->name('usuarios.create');
        Route::post('/usuarios', [UsuariosController::class, 'store'])->middleware('permission:users.create')->name('usuarios.store');
        Route::get('/usuarios/{usuario}', [UsuariosController::class, 'show'])->middleware('permission:users.view')->name('usuarios.show');
        Route::get('/usuarios/{usuario}/edit', [UsuariosController::class, 'edit'])->middleware('permission:users.update')->name('usuarios.edit');
        Route::match(['put', 'patch'], '/usuarios/{usuario}', [UsuariosController::class, 'update'])->middleware('permission:users.update')->name('usuarios.update');
        Route::delete('/usuarios/{usuario}', [UsuariosController::class, 'destroy'])->middleware('permission:users.deactivate')->name('usuarios.destroy');

        // Roles
        Route::get('/roles', [RolesController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
        Route::get('/roles/create', [RolesController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
        Route::post('/roles', [RolesController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
        Route::get('/roles/{role}/edit', [RolesController::class, 'edit'])->whereNumber('role')->middleware('permission:roles.update')->name('roles.edit');
        Route::match(['put', 'patch'], '/roles/{role}', [RolesController::class, 'update'])->whereNumber('role')->middleware('permission:roles.update')->name('roles.update');
        Route::delete('/roles/{role}', [RolesController::class, 'destroy'])->whereNumber('role')->middleware('permission:roles.delete')->name('roles.destroy');

        // Inventario
        Route::get('/inventario', [InventarioController::class, 'index'])->middleware('permission:inventory.view')->name('inventario.index');
        Route::get('/inventario/movimientos', [InventarioController::class, 'movements'])->middleware('permission:inventory.view')->name('inventario.movements');
        Route::get('/inventario/exportar',[InventarioController::class, 'export'])->middleware('permission:inventory.export')->name('inventario.export');
        Route::get('/inventario/importar', [InventarioController::class, 'import'])->middleware('permission:inventory.import')->name('inventario.import');
        Route::post('/inventario/importar/preview', [InventarioController::class, 'previewImport'])->middleware('permission:inventory.import')->name('inventario.import.preview');
        Route::post('/inventario/importar', [InventarioController::class, 'storeImport'])->middleware('permission:inventory.import')->name('inventario.import.store');
        Route::get('/inventario/reportes', [InventoryOperationsController::class, 'reports'])->middleware('permission:inventory.reports.view')->name('inventario.reports');
        Route::get('/inventario/reportes/exportar/{section}', [InventoryOperationsController::class, 'exportReport'])->middleware('permission:inventory.reports.view')->name('inventario.reports.export');
        Route::get('/inventario/cierres', [InventoryOperationsController::class, 'periods'])->middleware('permission:inventory.periods.view')->name('inventario.periods');
        Route::post('/inventario/cierres', [InventoryOperationsController::class, 'storePeriod'])->middleware('permission:inventory.periods.create')->name('inventario.periods.store');
        Route::get('/inventario/ubicaciones', [InventoryOperationsController::class, 'locations'])->middleware('permission:inventory.locations.view')->name('inventario.locations');
        Route::post('/inventario/ubicaciones', [InventoryOperationsController::class, 'storeLocation'])->middleware('permission:inventory.locations.create')->name('inventario.locations.store');
        Route::get('/inventario/proveedores', [InventoryOperationsController::class, 'suppliers'])->middleware('permission:inventory.suppliers.view')->name('inventario.suppliers');
        Route::post('/inventario/proveedores', [InventoryOperationsController::class, 'storeSupplier'])->middleware('permission:inventory.suppliers.create')->name('inventario.suppliers.store');
        Route::get('/inventario/compras', [InventoryOperationsController::class, 'purchases'])->middleware('permission:inventory.purchases.view')->name('inventario.purchases');
        Route::post('/inventario/compras', [InventoryOperationsController::class, 'storePurchase'])->middleware('permission:inventory.purchases.create')->name('inventario.purchases.store');
        Route::get('/inventario/transferencias', [InventoryOperationsController::class, 'transfers'])->middleware('permission:inventory.transfers.view')->name('inventario.transfers');
        Route::post('/inventario/transferencias', [InventoryOperationsController::class, 'storeTransfer'])->middleware('permission:inventory.transfers.create')->name('inventario.transfers.store');
        Route::get('/inventario/devoluciones', [InventoryOperationsController::class, 'returns'])->middleware('permission:inventory.returns.view')->name('inventario.returns');
        Route::post('/inventario/devoluciones', [InventoryOperationsController::class, 'storeReturn'])->middleware('permission:inventory.returns.create')->name('inventario.returns.store');
        Route::get('/inventario/conteos', [InventoryOperationsController::class, 'counts'])->middleware('permission:inventory.counts.view')->name('inventario.counts');
        Route::post('/inventario/conteos', [InventoryOperationsController::class, 'storeCount'])->middleware('permission:inventory.counts.create')->name('inventario.counts.store');
        Route::get('/inventario/kardex/{variant}/exportar', [InventoryOperationsController::class, 'exportKardex'])->middleware('permission:inventory.export')->name('inventario.kardex.export');
        Route::get('/inventario/kardex/{variant}', [InventoryOperationsController::class, 'kardex'])->middleware('permission:inventory.view')->name('inventario.kardex');
        Route::get('/inventario/create', [InventarioController::class, 'create'])->middleware('permission:inventory.move')->name('inventario.create');
        Route::post('/inventario/movimientos', [InventarioController::class, 'storeMovement'])->middleware('permission:inventory.move')->name('inventario.movements.store');
        Route::get('/inventario/movimientos/{movement}/edit', [InventarioController::class, 'edit'])->middleware('permission:inventory.update')->name('inventario.movements.edit');
        Route::put('/inventario/movimientos/{movement}', [InventarioController::class, 'update'])->middleware('permission:inventory.update')->name('inventario.movements.update');
        Route::delete('/inventario/movimientos/{movement}', [InventarioController::class, 'destroy'])->middleware('permission:inventory.void')->name('inventario.movements.destroy');

        // Catalogo: la ruta exige ver productos o servicios; cada controlador valida
        // la accion segun el modelo de negocio (catalog.products.* / catalog.services.*)
        Route::middleware('permission:catalog.products.view|catalog.services.view')->group(function () {
            Route::view('/catalogo', 'admin.catalog.index')->name('catalog.index');
            Route::resource('/catalogo/tipos', CatalogTypeController::class)
                ->parameters(['tipos' => 'catalogType'])
                ->names('catalog-types');
            Route::resource('/catalogo/categorias', CatalogCategoryController::class)
                ->parameters(['categorias' => 'catalogCategory'])
                ->names('catalog-categories');
            Route::get('/catalogo/items/{catalogItem}/precios-vehiculo/create', [ServiceVehicleTypePriceController::class, 'create'])
                ->name('catalog-service-prices.create');
            Route::post('/catalogo/items/{catalogItem}/precios-vehiculo', [ServiceVehicleTypePriceController::class, 'store'])
                ->name('catalog-service-prices.store');
            Route::get('/catalogo/precios-vehiculo/{serviceVehicleTypePrice}/edit', [ServiceVehicleTypePriceController::class, 'edit'])
                ->name('catalog-service-prices.edit');
            Route::put('/catalogo/precios-vehiculo/{serviceVehicleTypePrice}', [ServiceVehicleTypePriceController::class, 'update'])
                ->name('catalog-service-prices.update');
            Route::delete('/catalogo/precios-vehiculo/{serviceVehicleTypePrice}', [ServiceVehicleTypePriceController::class, 'destroy'])
                ->name('catalog-service-prices.destroy');
            Route::resource('/catalogo/items', CatalogItemController::class)
                ->parameters(['items' => 'catalogItem'])
                ->except(['index'])
                ->names('catalog-items');
            Route::get('/catalogo/variantes', fn () => redirect()->route('admin.catalog-variants.index'));
            Route::get('/catalogo/variantes/create', fn (Request $request) => redirect()->route('admin.catalog-variants.create', $request->query()));
            Route::get('/catalogo/variantes/{catalogVariant}/edit', fn (Request $request, CatalogItemVariant $catalogVariant) => redirect()->route('admin.catalog-variants.edit', ['catalogVariant' => $catalogVariant] + $request->query()));
            Route::get('/catalogo/variantes/{catalogVariant}', fn (Request $request, CatalogItemVariant $catalogVariant) => redirect()->route('admin.catalog-variants.show', ['catalogVariant' => $catalogVariant] + $request->query()));
            Route::resource('/catalogo/presentaciones', CatalogItemVariantController::class)
                ->parameters(['presentaciones' => 'catalogVariant'])
                ->names('catalog-variants');
        });

        // Empresa
        Route::get('/empresa', [EmpresaController::class, 'edit'])->middleware('permission:company.view')->name('empresa.edit');
        Route::put('/empresa', [EmpresaController::class, 'update'])->middleware('permission:company.update')->name('empresa.update');
        Route::delete('/empresa/logo', [EmpresaController::class, 'deleteLogo'])->middleware('permission:company.update')->name('empresa.deleteLogo');

        // Landing Banners
        Route::get('/banners', [BannerController::class, 'index'])
            ->middleware('permission:banners.view')->name('banners.index');
        Route::get('/banners/create', [BannerController::class, 'create'])
            ->middleware('permission:banners.create')->name('banners.create');
        Route::post('/banners', [BannerController::class, 'store'])
            ->middleware('permission:banners.create')->name('banners.store');
        Route::get('/banners/{banner}', [BannerController::class, 'show'])
            ->whereNumber('banner')->middleware('permission:banners.view')->name('banners.show');
        Route::get('/banners/{banner}/edit', [BannerController::class, 'edit'])
            ->whereNumber('banner')->middleware('permission:banners.update')->name('banners.edit');
        Route::match(['put', 'patch'], '/banners/{banner}', [BannerController::class, 'update'])
            ->whereNumber('banner')->middleware('permission:banners.update')->name('banners.update');
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])
            ->whereNumber('banner')->middleware('permission:banners.delete')->name('banners.destroy');

        // Ordenes
        Route::resource('/orders', OrderController::class)->only(['index', 'show'])->middleware('permission:orders.view');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->middleware('permission:orders.delete')->name('orders.destroy');
        Route::patch('/orders/{order}/marcar-pagada', [OrderController::class, 'marcarPagada'])->middleware('permission:sales.collect')->name('orders.marcar-pagada');
        Route::patch('/orders/{order}/estado-operativo', [OrderController::class, 'updateWorkStatus'])->middleware('permission:orders.update')->name('orders.work-status');
        Route::patch('/orders/{order}/datos-operativos', [OrderController::class, 'updateOperationalDetails'])->middleware('permission:orders.update')->name('orders.operational-details');
    });

require __DIR__.'/auth.php';
