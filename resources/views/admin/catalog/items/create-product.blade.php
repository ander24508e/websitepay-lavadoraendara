@extends('layouts.admin')

@section('title', 'Nuevo Producto')

@push('styles')
    @vite('resources/scss/Catalogo/catalogo-products.scss')
@endpush

@section('content')
    @php
        $fromInventory = $fromInventory ?? false;
        $selectedProductTypeId = (int) old('catalog_type_id', $selectedTypeId ?: ($types->count() === 1 ? $types->first()->id : 0));
        $returnToCategory = $returnToCategory ?? false;
        $inventoryReturnUrl = $fromInventory
            ? route('admin.inventario.index', array_filter(['catalog_type_id' => $selectedProductTypeId ?: null]))
            : null;
        $returnUrl = $fromInventory
            ? $inventoryReturnUrl
            : ($returnToCategory && $selectedCategoryId > 0
                ? route('admin.catalog-types.show', $selectedProductTypeId)
                : ($returnToType && $selectedProductTypeId > 0
                    ? route('admin.catalog-types.show', $selectedProductTypeId)
                    : ($selectedProductTypeId > 0
                        ? route('admin.catalog-types.show', $selectedProductTypeId)
                        : route('admin.catalog.index'))));
    @endphp

    @livewire('admin.catalog.product-form', [
        'selectedTypeId' => $selectedProductTypeId,
        'selectedCategoryId' => $selectedCategoryId ?: null,
        'returnUrl' => $returnUrl,
        'fromInventory' => $fromInventory,
        'returnToType' => $returnToType,
        'returnToCategory' => $returnToCategory,
    ])
@endsection
