@extends('layouts.admin')

@section('title', 'Nuevo usuario')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Nuevo usuario</h2>
            <p class="text-sm text-gray-500">Crea una cuenta interna y asígnale un rol. Los clientes se registran desde el sitio web o el módulo Clientes.</p>
        </div>
        <a href="{{ route('admin.usuarios.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">Volver</a>
    </div>

    <form method="POST" action="{{ route('admin.usuarios.store') }}" class="space-y-6">
        @csrf
        <section class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm space-y-4">
            <h3 class="font-semibold text-gray-800">Datos de acceso</h3>
            @include('admin.usuarios._form-fields')
            <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', '1') === '1') class="rounded border-gray-300"> Permitir acceso inmediatamente</label>
        </section>

        <button class="bg-gray-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold disabled:opacity-50" @disabled($roles->isEmpty())>Crear usuario</button>
    </form>
</div>
@endsection
