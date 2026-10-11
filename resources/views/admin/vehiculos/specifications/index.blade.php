@extends('layouts.admin')

@section('title', 'Especificaciones de Vehículos')

@section('content')
    <div class="space-y-6 overflow-x-hidden">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-3 min-w-0">
                <a href="{{ route('admin.vehiculos.index') }}"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 hover:text-gray-800"
                    title="Volver" aria-label="Volver">
                    <x-heroicon-o-arrow-left class="h-5 w-5" />
                </a>
                <div class="min-w-0">
                    <h2 class="break-words text-xl font-bold text-gray-800 sm:text-2xl">Especificaciones de Vehículos</h2>
                    <p class="mt-1 text-sm text-gray-500">Administra combinaciones reutilizables de marca, modelo y tipo.</p>
                </div>
            </div>

            <button type="button" data-open-spec-modal
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                <x-heroicon-o-plus class="h-5 w-5" />
                Crear especificación
            </button>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-semibold">Revisa los campos marcados.</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-4 py-4 sm:px-6">
                <h3 class="font-bold text-gray-800">Especificaciones registradas</h3>
                <p class="mt-1 text-xs text-gray-400">Cada fila representa una combinación disponible para registrar vehículos y calcular precios por tipo.</p>
            </div>

            <div class="md:hidden divide-y divide-gray-100">
                @forelse ($vehicleSpecifications as $specification)
                    <article class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-800 break-words">{{ $specification->brand?->name ?? '-' }}</p>
                                <p class="text-sm text-gray-500 break-words">
                                    {{ $specification->model?->name ?? '-' }} / {{ $specification->type?->name ?? '-' }}
                                </p>
                            </div>
                            @if ($specification->active)
                                <span class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Activo</span>
                            @else
                                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Inactivo</span>
                            @endif
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <span class="text-xs text-gray-400">{{ $specification->vehicles_count }} vehículos</span>
                            @can('vehicles.specifications.delete')
                            <form method="POST" action="{{ route('admin.vehiculos.specifications.destroy', $specification) }}"
                                onsubmit="return confirm('¿Eliminar esta especificación?');">
                                @csrf
                                @method('DELETE')
                                <button class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50 disabled:opacity-30"
                                    title="Eliminar" aria-label="Eliminar" @disabled($specification->vehicles_count)>
                                    <x-heroicon-o-trash class="h-5 w-5" />
                                </button>
                            </form>
                            @endcan
                        </div>
                    </article>
                @empty
                    <div class="px-4 py-10 text-center text-gray-400">No hay especificaciones registradas.</div>
                @endforelse
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full min-w-[760px] table-fixed text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="w-[28%] px-4 py-3 text-center">Marca</th>
                            <th class="w-[28%] px-4 py-3 text-center">Modelo</th>
                            <th class="w-[22%] px-4 py-3 text-center">Tipo de vehículo</th>
                            <th class="w-[10%] px-4 py-3 text-center">Estado</th>
                            <th class="w-[7%] px-4 py-3 text-center">Vehículos</th>
                            <th class="w-[5%] px-4 py-3 text-center">Acc.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($vehicleSpecifications as $specification)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ $specification->brand?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ $specification->model?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ $specification->type?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($specification->active)
                                        <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Activo</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $specification->vehicles_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center">
                                        @can('vehicles.specifications.delete')
                                        <form method="POST" action="{{ route('admin.vehiculos.specifications.destroy', $specification) }}"
                                            onsubmit="return confirm('¿Eliminar esta especificación?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition hover:bg-red-50 disabled:opacity-30"
                                                title="Eliminar" aria-label="Eliminar" @disabled($specification->vehicles_count)>
                                                <x-heroicon-o-trash class="h-4 w-4" />
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-gray-400">No hay especificaciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div id="specificationModal" class="fixed inset-0 z-[3000] hidden items-center justify-center bg-gray-900/40 p-3 sm:p-6">
            <div class="flex max-h-[calc(100dvh-1.5rem)] w-full max-w-md flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 p-5">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Nueva especificación</h3>
                        <p class="mt-1 text-xs text-gray-400">Ingresa marca, modelo y tipo de vehículo.</p>
                    </div>
                    <button type="button" data-close-spec-modal
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700"
                        title="Cerrar" aria-label="Cerrar">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>

                @can('vehicles.specifications.create')
                <form method="POST" action="{{ route('admin.vehiculos.specifications.store') }}" class="min-h-0 flex-1 overflow-y-auto p-5">
                    @include('admin.vehiculos.specifications._form-fields', ['prefix' => 'modal'])

                    <div class="mt-5 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" data-close-spec-modal
                            class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-200">
                            Cancelar
                        </button>
                        <button class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700">
                            Crear especificación
                        </button>
                    </div>
                </form>
                @endcan
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function specificationsView() {
            const modal = document.getElementById('specificationModal');

            function openModal() {
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }

            document.querySelector('[data-open-spec-modal]')?.addEventListener('click', openModal);
            document.querySelectorAll('[data-close-spec-modal]').forEach((button) => button.addEventListener('click', closeModal));
            modal?.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) closeModal();
            });
        })();
    </script>
@endpush
