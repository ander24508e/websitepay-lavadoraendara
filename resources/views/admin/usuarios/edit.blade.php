@extends('layouts.admin')

@section('title', 'Editar usuario')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-3"><div><h2 class="text-2xl font-bold text-gray-800">Editar usuario</h2><p class="text-gray-500 text-sm">Actualiza identidad, rol y acceso.</p></div><a href="{{ route('admin.usuarios.show', $usuario) }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Cancelar</a></div>

    <form method="POST" action="{{ route('admin.usuarios.update', $usuario) }}" class="space-y-6">
        @csrf @method('PUT')
        <section class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-gray-800">Datos de acceso</h3>
            @include('admin.usuarios._form-fields', ['usuario' => $usuario])
            @can('users.deactivate')<label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', $usuario->active ? '1' : '0') === '1') class="rounded border-gray-300"> Cuenta activa</label>@endcan
        </section>

        <button type="submit" class="bg-gray-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold">Guardar cambios</button>
    </form>
</div>
@endsection
