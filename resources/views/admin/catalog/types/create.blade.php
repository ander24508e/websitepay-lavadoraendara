@extends('layouts.admin')

@section('title', 'Nueva Seccion')

@section('content')
@php
    $catalogType = null;
    $selectedBusinessModel = old('business_model', request('business_model', \App\Support\Permissions\CatalogPermission::allows('services', 'create') ? 'services' : 'products'));
@endphp

<div class="mx-auto w-full max-w-full overflow-x-hidden px-2 pb-4 sm:px-4 xl:px-6">
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="{{ route('admin.catalog.index') }}"
            class="flex items-center justify-center w-9 h-9 bg-white rounded-lg shadow-sm hover:bg-gray-50 transition text-gray-500 hover:text-gray-800">
            <span aria-hidden="true">&larr;</span>
        </a>
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">Crear Negocio</h2>
        </div>
    </div>

    <div class="w-full">
        <div class="bg-white rounded-xl shadow-sm p-5 sm:p-8 xl:p-10">
            <form action="{{ route('admin.catalog-types.store') }}" method="POST">
                @csrf

                @include('admin.catalog.types._form')

                <div class="flex flex-col gap-3 pt-6 mt-6 border-t border-gray-100 sm:flex-row">
                    <button type="submit"
                        class="w-full bg-gray-900 text-white px-6 py-2.5 rounded-lg hover:bg-gray-700 transition font-medium text-sm sm:w-auto">
                        Guardar
                    </button>
                    <a href="{{ route('admin.catalog.index') }}"
                        class="w-full bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-200 transition font-medium text-sm text-center sm:w-auto">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('admin.catalog.partials._slug-script', ['nameInputId' => 'catalog_type_name', 'slugInputId' => 'catalog_type_slug'])
