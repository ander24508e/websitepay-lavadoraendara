@extends('layouts.admin')

@section('title', 'Clientes')

@push('styles')
    @vite('resources/scss/admin/admin-data-tables.scss')
@endpush

@section('content')
    <div class="admin-data-page">

        {{-- Encabezado --}}
        <div class="admin-data-page__header flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <x-heroicon-o-users class="h-8 w-8 text-gray-800" />

                    <h2 class="text-2xl font-bold text-gray-800">
                        Clientes
                    </h2>
                </div>

                <p class="mt-1 text-sm text-gray-500">
                    Listado de clientes registrados con historial de compras.
                </p>
            </div>

            @can('clients.create')
            <a
                href="{{ route('admin.clientes.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700"
                title="Nuevo cliente"
                aria-label="Nuevo cliente"
            >
                <x-heroicon-o-plus class="h-5 w-5" />

                <span class="ml-2 hidden sm:inline">
                    Nuevo cliente
                </span>
            </a>
            @endcan
        </div>

        {{-- Estadísticas --}}
        <div class="admin-data-page__stats grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <article class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-400">
                    Total clientes
                </p>

                <p class="mt-1 text-2xl font-bold text-gray-800">
                    {{ $stats['total_clientes'] }}
                </p>
            </article>

            <article class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-400">
                    Nuevos este mes
                </p>

                <p class="mt-1 text-2xl font-bold text-gray-800">
                    {{ $stats['nuevos_mes'] }}
                </p>
            </article>

            <article class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-400">
                    Con compras
                </p>

                <p class="mt-1 text-2xl font-bold text-gray-800">
                    {{ $stats['clientes_con_compras'] }}
                </p>
            </article>

            <article class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-400">
                    Ingreso total
                </p>

                <p class="mt-1 text-2xl font-bold text-gray-800">
                    ${{ number_format((float) $stats['ingreso_total_clientes'], 2) }}
                </p>
            </article>

        </div>

        {{-- Buscador --}}
        <div class="admin-data-page__toolbar rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <form
                method="GET"
                action="{{ route('admin.clientes.index') }}"
                class="flex flex-col gap-3 md:flex-row"
            >
                <div class="relative flex-1">
                    <label for="q" class="sr-only">
                        Buscar cliente
                    </label>

                    <x-heroicon-o-magnifying-glass
                        class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                    />

                    <input
                        id="q"
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Buscar por nombre, correo o teléfono"
                        class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-3 text-sm outline-none transition focus:border-gray-500 focus:ring-2 focus:ring-gray-200"
                    >
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-700"
                >
                    <x-heroicon-o-magnifying-glass class="mr-2 h-4 w-4" />
                    Buscar
                </button>

                <a
                    href="{{ route('admin.clientes.index') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200"
                >
                    <x-heroicon-o-x-mark class="mr-2 h-4 w-4" />
                    Limpiar
                </a>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="admin-data-page__list overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="admin-data-page__mobile-scroll md:hidden divide-y divide-gray-100">
                @forelse ($clientes as $cliente)
                    <article class="p-4 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-gray-400">#{{ $cliente->id }}</p>
                                <h3 class="font-semibold text-gray-800 break-words">{{ $cliente->name }}</h3>
                                <p class="text-sm text-gray-500 break-all">{{ $cliente->email }}</p>
                            </div>
                            <p class="shrink-0 font-semibold text-gray-900">${{ number_format((float) ($cliente->total_compras ?? 0), 2) }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Teléfono</p>
                                <p class="text-gray-700 break-words">{{ $cliente->telefono ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Órdenes</p>
                                <p class="text-gray-700">{{ $cliente->orders_count }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase text-gray-400 font-semibold">Registro</p>
                                <p class="text-gray-700">{{ $cliente->created_at?->format('d/m/Y') ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.clientes.show', $cliente) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-600 transition hover:bg-blue-50"
                                title="Ver cliente" aria-label="Ver cliente">
                                <x-heroicon-o-eye class="h-5 w-5" />
                            </a>
                            @can('clients.update')
                            <a href="{{ route('admin.clientes.edit', $cliente) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-yellow-600 transition hover:bg-yellow-50"
                                title="Editar cliente" aria-label="Editar cliente">
                                <x-heroicon-o-pencil-square class="h-5 w-5" />
                            </a>
                            @endcan
                            @can('clients.delete')
                            <form method="POST" action="{{ route('admin.clientes.destroy', $cliente) }}"
                                onsubmit="return confirm('¿Eliminar cliente?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50"
                                    title="Eliminar cliente" aria-label="Eliminar cliente">
                                    <x-heroicon-o-trash class="h-5 w-5" />
                                </button>
                            </form>
                            @endcan
                        </div>
                    </article>
                @empty
                    <div class="px-4 py-10 text-center text-gray-400">No hay clientes registrados.</div>
                @endforelse
            </div>

            <div class="admin-data-page__scroll hidden md:block">
                <table class="min-w-[1000px] w-full text-sm">

                    <thead class="border-b border-gray-100 bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="w-[7%] px-4 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                ID
                            </th>

                            <th
                                scope="col"
                                class="w-[18%] px-4 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Cliente
                            </th>

                            <th
                            
                                scope="col"
                                class="w-[24%] px-4 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Correo
                            </th>

                            <th
                                scope="col"
                                class="w-[14%] px-4 py-4 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Teléfono
                            </th>

                            <th
                                scope="col"
                                class="w-[8%] px-4 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Órdenes
                            </th>

                            <th
                                scope="col"
                                class="w-[12%] px-4 py-4 text-right text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Total
                            </th>

                            <th
                                scope="col"
                                class="w-[10%] px-4 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Registro
                            </th>

                            <th
                                scope="col"
                                class="w-[7%] px-4 py-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Acc.
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($clientes as $cliente)
                            <tr class="transition hover:bg-gray-50">

                                {{-- ID --}}
                                <td class="whitespace-nowrap px-4 py-4 text-center align-middle">
                                    <span class="font-semibold text-gray-700">
                                        #{{ $cliente->id }}
                                    </span>
                                </td>

                                {{-- Cliente --}}
                                <td class="px-4 py-4 align-middle">
                                    <p
                                        class="truncate font-medium text-gray-700"
                                        title="{{ $cliente->name }}"
                                    >
                                        {{ $cliente->name }}
                                    </p>
                                </td>

                                {{-- Correo --}}
                                <td class="px-4 py-4 align-middle">
                                    <p
                                        class="truncate text-gray-600"
                                        title="{{ $cliente->email }}"
                                    >
                                        {{ $cliente->email }}
                                    </p>
                                </td>

                                {{-- Teléfono --}}
                                <td class="whitespace-nowrap px-4 py-4 text-gray-600 align-middle">
                                    {{ $cliente->telefono ?? '-' }}
                                </td>

                                {{-- Órdenes --}}
                                <td class="px-4 py-4 text-center text-gray-700 align-middle">
                                    {{ $cliente->orders_count }}
                                </td>

                                {{-- Total --}}
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold text-gray-800 align-middle">
                                    ${{ number_format((float) ($cliente->total_compras ?? 0), 2) }}
                                </td>

                                {{-- Registro --}}
                                <td class="whitespace-nowrap px-4 py-4 text-center text-gray-500 align-middle">
                                    {{ $cliente->created_at?->format('d/m/Y') ?? '-' }}
                                </td>

                                {{-- Acciones --}}
                                <td class="px-4 py-4 align-middle">
                                    <div class="flex items-center justify-center gap-1">
                                        <a
                                            href="{{ route('admin.clientes.show', $cliente) }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-600 transition hover:bg-blue-50 hover:text-blue-800"
                                            title="Ver cliente"
                                            aria-label="Ver cliente"
                                        >
                                            <x-heroicon-o-eye class="h-4 w-4" />
                                        </a>

                                        @can('clients.update')
                                        <a
                                            href="{{ route('admin.clientes.edit', $cliente) }}"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-yellow-600 transition hover:bg-yellow-50 hover:text-yellow-800"
                                            title="Editar cliente"
                                            aria-label="Editar cliente"
                                        >
                                            <x-heroicon-o-pencil-square class="h-4 w-4" />
                                        </a>
                                        @endcan

                                        @can('clients.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('admin.clientes.destroy', $cliente) }}"
                                            onsubmit="return confirm('¿Eliminar cliente?');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50 hover:text-red-800"
                                                title="Eliminar cliente"
                                                aria-label="Eliminar cliente"
                                            >
                                                <x-heroicon-o-trash class="h-4 w-4" />
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                            <x-heroicon-o-users class="h-6 w-6 text-gray-400" />
                                        </div>
                                        <p class="mt-3 font-medium text-gray-600">
                                            No hay clientes registrados
                                        </p>
                                        <p class="mt-1 text-sm text-gray-400">
                                            Registra un cliente para comenzar.
                                        </p>
                                        @can('clients.create')
                                        <a
                                            href="{{ route('admin.clientes.create') }}"
                                            class="mt-4 inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700"
                                        >
                                            <x-heroicon-o-plus class="mr-2 h-4 w-4" />
                                            Nuevo cliente
                                        </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
        {{-- Paginación --}}
        @if ($clientes->hasPages())
            <div class="admin-data-page__pagination">
                {{ $clientes->links() }}
            </div>
        @endif
    </div>
@endsection
