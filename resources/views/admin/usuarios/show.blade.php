@extends('layouts.admin')

@section('title', 'Detalle del trabajador')

@section('content')

    <div class="mx-auto w-full max-w-6xl overflow-x-hidden px-3 pb-4 sm:px-6">
        <div class="mb-4 flex items-start gap-3">
            <a href="{{ route('admin.usuarios.index') }}"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-gray-800"
                aria-label="Volver al personal">
                <x-heroicon-o-arrow-left class="h-4 w-4" />
            </a>
            <div class="min-w-0">
                <h2 class="break-words text-xl font-bold text-gray-800 sm:text-2xl">{{ $usuario->name }}</h2>
                <p class="text-sm text-gray-400">Consulta su perfil, actividad y permisos dentro del sistema.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="min-w-0">
                <div class="rounded-xl bg-white p-4 shadow-sm sm:p-6">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gray-900 text-base font-bold text-white">
                            {{ str($usuario->name)->trim()->substr(0, 1)->upper() }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="break-words text-lg font-bold text-gray-800">Información del trabajador</h3>
                            <p class="text-xs text-gray-400">{{ $role?->name ?? 'Sin rol' }}</p>
                        </div>
                    </div>

                    <div class="mb-6 min-w-0 space-y-4">
                        <div><p class="text-xs uppercase tracking-wide text-gray-400">Correo</p><p class="break-all text-sm font-semibold text-gray-800">{{ $usuario->email }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-gray-400">Teléfono</p><p class="text-sm font-semibold text-gray-800">{{ $usuario->telefono ?: 'Sin registrar' }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-gray-400">Dirección</p><p class="break-words text-sm text-gray-700">{{ $usuario->direccion ?: 'Sin registrar' }}</p></div>
                        <div><p class="text-xs uppercase tracking-wide text-gray-400">Rol</p><span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">{{ $role?->name ?? 'Sin rol' }}</span>@if($role && ! $role->active)<span class="ml-1 inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700">Rol inactivo</span>@endif</div>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-400">Estado</p>
                            @if($usuario->active)
                                <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">Activo</span>
                            @else
                                <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700">Inactivo</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><p class="text-xs uppercase tracking-wide text-gray-400">Creado por</p><p class="break-words text-sm font-semibold text-gray-800">{{ $usuario->createdBy?->name ?? 'Sistema' }}</p></div>
                            <div><p class="text-xs uppercase tracking-wide text-gray-400">Registro</p><p class="text-sm font-semibold text-gray-800">{{ $resumen['registro']?->format('d/m/Y') }}</p></div>
                        </div>
                    </div>

                    @unless($usuario->is(auth()->user()))
                    <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row">
                            @can('users.update')
                                <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="w-full rounded-lg bg-gray-900 px-5 py-2.5 text-center text-sm font-medium text-white transition hover:bg-gray-700 sm:w-auto">Editar</a>
                            @endcan
                            @if($usuario->active)
                                @can('users.deactivate')
                                    <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}" onsubmit="return confirm('¿Desactivar el acceso de este trabajador?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="w-full rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-100 sm:w-auto">Desactivar</button>
                                    </form>
                                @endcan
                            @endif
                    </div>
                    @endunless
                </div>
            </aside>

            <section class="min-w-0 overflow-hidden rounded-xl bg-white shadow-sm">
                <div class="border-b border-gray-100 p-4 sm:p-5">
                    <div class="mb-4">
                        <h3 class="font-bold text-gray-800">Actividad operativa</h3>
                        <p class="text-xs text-gray-400">Resumen del desempeño registrado en el sistema.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-gray-50 p-4">
                            <div class="mb-2 flex items-center justify-between gap-2"><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Órdenes asignadas</p><x-heroicon-o-clipboard-document-list class="h-5 w-5 text-gray-400" /></div>
                            <p class="text-2xl font-bold text-gray-900">{{ $resumen['ordenes_asignadas'] }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4">
                            <div class="mb-2 flex items-center justify-between gap-2"><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Ventas atendidas</p><x-heroicon-o-shopping-bag class="h-5 w-5 text-gray-400" /></div>
                            <p class="text-2xl font-bold text-gray-900">{{ $resumen['ventas_atendidas'] }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4">
                            <div class="mb-2 flex items-center justify-between gap-2"><p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Total vendido</p><x-heroicon-o-banknotes class="h-5 w-5 text-gray-400" /></div>
                            <p class="text-2xl font-bold text-gray-900">${{ number_format($resumen['total_vendido'], 2) }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-b border-gray-100 p-4 sm:p-5">
                    <div class="mb-4">
                        <h3 class="font-bold text-gray-800">Últimas órdenes asignadas</h3>
                        <p class="text-xs text-gray-400">Movimientos vinculados directamente con este trabajador.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                        @forelse($usuario->assignedOrders as $order)
                            @php
                                $statusClass = [
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'paid' => 'bg-green-100 text-green-700',
                                    'reserved' => 'bg-blue-100 text-blue-700',
                                    'failed' => 'bg-red-100 text-red-700',
                                    'cancelled' => 'bg-gray-100 text-gray-600',
                                ][$order->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <article class="overflow-hidden rounded-lg border border-gray-200">
                                <div class="flex items-start justify-between gap-3 px-4 py-3">
                                    <div><h4 class="font-bold text-gray-800">Orden #{{ $order->id }}</h4><p class="text-xs text-gray-400">{{ $order->created_at->format('d/m/Y H:i') }}</p></div>
                                    @can('orders.view')
                                        <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-50 hover:text-gray-800" aria-label="Ver orden #{{ $order->id }}"><x-heroicon-o-eye class="h-4 w-4" /></a>
                                    @endcan
                                </div>
                                <div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-gray-50/60 px-4 py-2.5">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                                    <span class="font-bold text-gray-900">${{ number_format($order->total, 2) }}</span>
                                </div>
                            </article>
                        @empty
                            <div class="col-span-full rounded-lg border border-dashed border-gray-200 px-4 py-10 text-center">
                                <x-heroicon-o-clipboard-document-list class="mx-auto mb-2 h-7 w-7 text-gray-300" />
                                <p class="text-sm text-gray-400">Sin órdenes registradas.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div><h3 class="font-bold text-gray-800">Permisos del rol</h3><p class="text-xs text-gray-400">Acciones que este usuario puede realizar a través de su rol.</p></div>
                        @if($role)
                            @can('roles.update')
                                <a href="{{ route('admin.roles.edit', $role) }}" class="shrink-0 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50">Editar rol</a>
                            @endcan
                        @endif
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @forelse($rolePermissions as $group)
                            <div class="rounded-lg border border-gray-200 p-3">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $group['label'] }}</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($group['permissions'] as $permission => $label)
                                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700" title="{{ $permission }}">{{ $label }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500">Su rol no tiene permisos asignados</span>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
