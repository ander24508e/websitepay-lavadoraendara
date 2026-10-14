{{--
    Menu de Inventario.
    - variant "sidebar" (PC): <details> con panel flotante encima de los items, igual que Configuracion.
      Por debajo de 768px el mismo panel se despliega en linea.
    - variant "sheet" (bottom sheet movil): dropdown en linea con iconos.
--}}
@php
    $variant = $variant ?? 'sidebar';
    $inventorySectionOpen = request()->routeIs('admin.inventario.*') && ! request()->routeIs('admin.inventario.suppliers*');
    $inventorySubItems = [
        [
            'can' => 'inventory.view',
            'route' => 'admin.inventario.index',
            'active' => ['admin.inventario.index', 'admin.inventario.kardex*', 'admin.inventario.import*', 'admin.inventario.locations*', 'admin.inventario.periods*'],
            'title' => 'Stock',
            'icon' => 'cube',
        ],
        [
            'can' => 'inventory.view',
            'route' => 'admin.inventario.movements',
            'active' => ['admin.inventario.movements*', 'admin.inventario.create'],
            'title' => 'Movimientos',
            'icon' => 'arrows-up-down',
        ],
        ['can' => 'inventory.purchases.view', 'route' => 'admin.inventario.purchases', 'active' => ['admin.inventario.purchases*'], 'title' => 'Compras', 'icon' => 'shopping-cart'],
        ['can' => 'inventory.transfers.view', 'route' => 'admin.inventario.transfers', 'active' => ['admin.inventario.transfers*'], 'title' => 'Transferencias', 'icon' => 'arrows-right-left'],
        ['can' => 'inventory.returns.view', 'route' => 'admin.inventario.returns', 'active' => ['admin.inventario.returns*'], 'title' => 'Devoluciones', 'icon' => 'arrow-uturn-left'],
        ['can' => 'inventory.counts.view', 'route' => 'admin.inventario.counts', 'active' => ['admin.inventario.counts*'], 'title' => 'Conteo físico', 'icon' => 'clipboard-document-check'],
        ['can' => 'inventory.reports.view', 'route' => 'admin.inventario.reports', 'active' => ['admin.inventario.reports*'], 'title' => 'Reportes', 'icon' => 'chart-bar'],
    ];
@endphp

@if ($variant === 'sidebar')
    <details class="sidebar-flyout">
        <summary class="sidebar-flyout-summary {{ $inventorySectionOpen ? 'active' : '' }}" title="Inventario">
            <span><x-heroicon-o-archive-box class="w-5 h-5" /></span>
            <span>Inventario</span>
        </summary>
        <div class="sidebar-subnav sidebar-flyout-panel">
            @include('partials.admin-inventory-nav-links', ['items' => $inventorySubItems])
        </div>
    </details>
@else
    <a href="{{ route($inventoryRoute) }}" class="{{ $inventorySectionOpen ? 'active' : '' }}" data-nav-toggle
        aria-expanded="{{ $inventorySectionOpen ? 'true' : 'false' }}">
        <span><x-heroicon-o-archive-box class="w-5 h-5" /></span>
        <span>Inventario</span>
    </a>

    <div class="sidebar-subnav" @unless ($inventorySectionOpen) hidden @endunless>
        @include('partials.admin-inventory-nav-links', ['items' => $inventorySubItems])
    </div>
@endif
